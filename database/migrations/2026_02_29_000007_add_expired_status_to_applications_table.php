<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL enum, we need to recreate the column with the new allowed value.
        Schema::table('applications', function (Blueprint $table) {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending','accepted','rejected','expired') DEFAULT 'pending'");
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending','accepted','rejected') DEFAULT 'pending'");
        });
    }
};

