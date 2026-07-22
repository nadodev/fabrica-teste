<?php

use App\Models\User;
use App\Modules\Administration\Domain\AdminPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('allows a limited administrator to see only explicitly granted capabilities', function () {
    $administrator = limitedAdministrator([AdminPermission::CatalogView]);

    $this->actingAs($administrator)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.is_super_admin', false)
            ->where('auth.user.permissions', [
                AdminPermission::CatalogView->value,
                AdminPermission::DashboardView->value,
            ]));

    $this->actingAs($administrator)->get(route('admin.products.create'))->assertForbidden();
    $this->actingAs($administrator)->get(route('admin.orders.index'))->assertForbidden();
    $this->actingAs($administrator)->get(route('admin.settings.edit'))->assertForbidden();
});

it('records unauthorized mutation attempts without storing submitted data', function () {
    $administrator = limitedAdministrator([AdminPermission::CatalogView]);

    $this->actingAs($administrator)->post(route('admin.products.store'), [
        'sku' => 'SEGREDO-001',
        'name' => 'Conteudo que nao deve ir para auditoria',
        'price' => '10,00',
        'status' => 'draft',
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertForbidden();

    $audits = DB::table('admin_audit_logs')->where('action', 'admin.products.store')->get();
    expect($audits->pluck('outcome')->all())->toBe(['attempted', 'rejected']);
    foreach ($audits as $audit) {
        expect((string) $audit->metadata)->not->toContain('SEGREDO-001')
            ->and((string) $audit->metadata)->not->toContain('Conteudo que nao deve ir para auditoria');
    }
});

it('records the attempt and completion of an authorized mutation', function () {
    $administrator = limitedAdministrator([AdminPermission::CatalogView, AdminPermission::CatalogManage]);

    $this->actingAs($administrator)->post(route('admin.products.store'), [
        'sku' => 'AUDIT-001',
        'name' => 'Produto auditado',
        'price' => '49,90',
        'status' => 'draft',
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertRedirect();

    $this->assertDatabaseHas('catalog_products', ['sku' => 'AUDIT-001']);
    expect(DB::table('admin_audit_logs')->where('action', 'admin.products.store')->pluck('outcome')->all())
        ->toBe(['attempted', 'completed']);
});

it('promotes only a verified customer and expands dependent view permissions', function () {
    $owner = User::factory()->admin()->create();
    $customer = User::factory()->create(['email' => 'operador@example.com']);

    $this->actingAs($owner)->post(route('admin.administrators.store'), [
        'email' => $customer->email,
        'permissions' => [AdminPermission::CatalogManage->value],
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $customer->id,
        'is_admin' => true,
        'is_super_admin' => false,
    ]);
    $this->assertDatabaseHas('admin_user_permissions', [
        'user_id' => $customer->id,
        'permission' => AdminPermission::DashboardView->value,
        'granted_by' => $owner->id,
    ]);
    $this->assertDatabaseHas('admin_user_permissions', [
        'user_id' => $customer->id,
        'permission' => AdminPermission::CatalogView->value,
    ]);
    $this->assertDatabaseHas('admin_user_permissions', [
        'user_id' => $customer->id,
        'permission' => AdminPermission::CatalogManage->value,
    ]);

    $unverified = User::factory()->unverified()->create(['email' => 'pendente@example.com']);
    $this->actingAs($owner)->post(route('admin.administrators.store'), [
        'email' => $unverified->email,
        'permissions' => [],
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertSessionHasErrors('email');
    expect($unverified->fresh()->is_admin)->toBeFalse();
});

it('prevents self-demotion and protects the owner account', function () {
    $owner = User::factory()->admin()->create();

    $this->actingAs($owner)->delete(
        route('admin.administrators.destroy', $owner->id),
        [],
        ['Idempotency-Key' => (string) Str::uuid()],
    )->assertRedirect()->assertSessionHasErrors('administrator');

    expect($owner->fresh()->is_admin)->toBeTrue()
        ->and($owner->fresh()->is_super_admin)->toBeTrue();
});

it('updates limited permissions but never delegates owner management', function () {
    $owner = User::factory()->admin()->create();
    $target = limitedAdministrator([AdminPermission::OrdersView]);

    $this->actingAs($owner)->put(route('admin.administrators.update', $target->id), [
        'permissions' => [AdminPermission::InventoryManage->value],
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseMissing('admin_user_permissions', [
        'user_id' => $target->id,
        'permission' => AdminPermission::OrdersView->value,
    ]);
    $this->assertDatabaseHas('admin_user_permissions', [
        'user_id' => $target->id,
        'permission' => AdminPermission::InventoryView->value,
    ]);
    $this->assertDatabaseHas('admin_user_permissions', [
        'user_id' => $target->id,
        'permission' => AdminPermission::InventoryManage->value,
    ]);

    $this->actingAs($owner)->put(route('admin.administrators.update', $target->id), [
        'permissions' => [AdminPermission::AdministratorsManage->value],
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertSessionHasErrors('permissions');
    $this->assertDatabaseMissing('admin_user_permissions', [
        'user_id' => $target->id,
        'permission' => AdminPermission::AdministratorsManage->value,
    ]);
});

it('shows sanitized audit history instead of raw application logs', function () {
    $owner = User::factory()->admin()->create();
    DB::table('admin_audit_logs')->insert([
        'id' => (string) Str::uuid(),
        'actor_user_id' => $owner->id,
        'action' => 'admin.settings.update',
        'subject_type' => null,
        'subject_id' => null,
        'outcome' => 'completed',
        'http_status' => 302,
        'ip_hash' => hash('sha256', '127.0.0.1'),
        'user_agent' => 'Test',
        'metadata' => json_encode(['method' => 'POST'], JSON_THROW_ON_ERROR),
        'created_at' => now(),
    ]);

    $this->actingAs($owner)->get(route('admin.operations'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/operations')
            ->where('auditEntries.0.action', 'admin.settings.update')
            ->missing('logs'));
});

it('prunes audit history according to the configured retention period', function () {
    config()->set('security.admin_audit_retention_days', 365);
    $oldId = (string) Str::uuid();
    $recentId = (string) Str::uuid();
    foreach ([[$oldId, now()->subDays(366)], [$recentId, now()->subDays(364)]] as [$id, $createdAt]) {
        DB::table('admin_audit_logs')->insert([
            'id' => $id,
            'actor_user_id' => null,
            'action' => 'admin.test',
            'subject_type' => null,
            'subject_id' => null,
            'outcome' => 'completed',
            'http_status' => 200,
            'ip_hash' => null,
            'user_agent' => null,
            'metadata' => null,
            'created_at' => $createdAt,
        ]);
    }

    $this->artisan('admin:audit-prune')->assertSuccessful();

    $this->assertDatabaseMissing('admin_audit_logs', ['id' => $oldId]);
    $this->assertDatabaseHas('admin_audit_logs', ['id' => $recentId]);
});

it('revokes administrative access and active sessions atomically', function () {
    $owner = User::factory()->admin()->create();
    $target = limitedAdministrator([AdminPermission::OrdersView]);
    DB::table('sessions')->insert([
        'id' => 'session-target-admin',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($owner)->delete(
        route('admin.administrators.destroy', $target->id),
        [],
        ['Idempotency-Key' => (string) Str::uuid()],
    )->assertRedirect()->assertSessionHas('success');

    expect($target->fresh()->is_admin)->toBeFalse()
        ->and($target->fresh()->is_super_admin)->toBeFalse();
    $this->assertDatabaseMissing('admin_user_permissions', ['user_id' => $target->id]);
    $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
});

it('keeps administrator management exclusive to the owner even with a forged permission row', function () {
    $administrator = limitedAdministrator([AdminPermission::AdministratorsManage]);

    $this->actingAs($administrator)->get(route('admin.administrators.index'))->assertForbidden();
});

/** @param list<AdminPermission> $permissions */
function limitedAdministrator(array $permissions): User
{
    $administrator = User::factory()->create([
        'is_admin' => true,
        'is_super_admin' => false,
    ]);
    $values = [AdminPermission::DashboardView, ...$permissions];
    foreach (array_unique(array_map(static fn (AdminPermission $permission): string => $permission->value, $values)) as $permission) {
        DB::table('admin_user_permissions')->insert([
            'user_id' => $administrator->id,
            'permission' => $permission,
            'granted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return $administrator;
}
