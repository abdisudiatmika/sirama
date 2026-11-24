<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A scheme lists every course of its curriculum (they all appear on the RPL
 * forms), but only some can be recognised; the rest must be taken in class
 * ("WAJIB" in the prodi's sheet) and always print as not recognised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skema_matakuliah', function (Blueprint $table) {
            $table->boolean('direkognisi')->default(true)->after('matakuliah_id');
        });
    }

    public function down(): void
    {
        Schema::table('skema_matakuliah', function (Blueprint $table) {
            $table->dropColumn('direkognisi');
        });
    }
};
