<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mk_asal', function (Blueprint $table) {
            $table->unsignedTinyInteger('sks_asal')->nullable()->after('nama_mk_asal');
            $table->string('nilai_huruf_asal', 3)->nullable()->after('sks_asal');
        });
    }

    public function down(): void
    {
        Schema::table('mk_asal', function (Blueprint $table) {
            $table->dropColumn(['sks_asal', 'nilai_huruf_asal']);
        });
    }
};
