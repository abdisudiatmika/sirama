<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendidikan_terakhir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('nama_sekolah');
            $table->text('alamat_sekolah')->nullable();
            $table->unsignedSmallInteger('tahun_lulus_sekolah');
            $table->string('nama_pt')->nullable();
            $table->string('jurusan_prodi')->nullable();
            $table->enum('program', ['D3', 'D4', 'S1', 'S2'])->nullable();
            $table->unsignedSmallInteger('tahun_lulus_pt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendidikan_terakhir');
    }
};
