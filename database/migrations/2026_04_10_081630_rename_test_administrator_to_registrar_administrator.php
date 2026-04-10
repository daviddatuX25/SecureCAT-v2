<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'test_administrator')
            ->update([
                'name' => 'registrar_administrator',
                'display_name' => 'Registrar Administrator',
                'description' => 'Guidance office, inputs scores and releases results.',
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'registrar_administrator')
            ->update([
                'name' => 'test_administrator',
                'display_name' => 'Test Administrator',
                'description' => 'Guidance office, inputs scores and releases consultations.',
            ]);
    }
};
