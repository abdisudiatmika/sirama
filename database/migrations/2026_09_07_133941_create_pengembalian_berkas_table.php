<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // History of admin-prodi reopenings (F-MHS-31 / §9 "Dikembalikan") —
        // kept as its own log rather than a single column on `mahasiswa` so
        // the reason for each return stays visible even after the file is
        // resubmitted and returned again later.
        Schema::create('pengembalian_berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->text('alasan');
            $table->foreignId('dikembalikan_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian_berkas');
    }
};
