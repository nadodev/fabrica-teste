<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $token = DB::table('shipping_settings')->where('id', 1)->value('token');
        if (is_string($token) && trim($token) !== '') {
            DB::table('shipping_settings')->where('id', 1)->update([
                'token' => Crypt::encryptString(trim((string) preg_replace('/^Bearer\s+/i', '', trim($token)))),
            ]);
        }
    }

    public function down(): void
    {
        $token = DB::table('shipping_settings')->where('id', 1)->value('token');
        if (! is_string($token) || trim($token) === '') {
            return;
        }

        try {
            $plain = Crypt::decryptString($token);
        } catch (Throwable) {
            return;
        }

        DB::table('shipping_settings')->where('id', 1)->update(['token' => $plain]);
    }
};
