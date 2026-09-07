<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mk_asal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_mk_id')->constrained('pengajuan_mk')->cascadeOnDelete();
            $table->string('kode_asal');
            $table->string('nama_mk_asal');
            $table->text('cpmk_asal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mk_asal');
    }
};
