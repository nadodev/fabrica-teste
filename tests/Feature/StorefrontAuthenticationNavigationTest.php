<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('shares the current storefront identity for guests customers and administrators', function () {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('auth.user', null));

    $customer = User::factory()->create(['is_admin' => false]);
    $this->actingAs($customer)->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.email', $customer->email)
        ->where('auth.user.is_admin', false));

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.email', $admin->email)
        ->where('auth.user.is_admin', true));
});

it('invalidates customer and administrator sessions on logout', function () {
    $customer = User::factory()->create(['is_admin' => false]);
    $this->actingAs($customer)->post(route('cliente.logout'))->assertRedirect(route('home'));
    $this->assertGuest();

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->post(route('admin.logout'))->assertRedirect(route('home'));
    $this->assertGuest();
});
