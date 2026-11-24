<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks scores the admin entered on an assessor's behalf (Excel import from
 * the student panel), so they can be told apart from the assessor's own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->foreignId('diinput_oleh')->nullable()->after('nilai_akhir')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diinput_oleh');
        });
    }
};
