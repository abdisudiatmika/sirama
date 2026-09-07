<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biodata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->enum('status', ['Belum Menikah', 'Menikah', 'Lainnya']);
            $table->string('kebangsaan')->default('Indonesia');
            $table->text('alamat_rumah');
            $table->string('kode_pos', 10)->nullable();
            $table->string('telp_rumah_hp');
            $table->text('alamat_kantor')->nullable();
            $table->string('telp_kantor')->nullable();
            $table->string('email');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biodata');
    }
};
