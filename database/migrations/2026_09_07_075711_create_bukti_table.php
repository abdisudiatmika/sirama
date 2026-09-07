<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->enum('kategori', ['formal', 'informal']);
            $table->string('jenis_bukti');
            $table->string('judul');
            $table->string('penerbit')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('url');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti');
    }
};
