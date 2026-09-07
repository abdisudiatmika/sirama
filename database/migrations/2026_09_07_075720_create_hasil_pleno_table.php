<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_pleno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->enum('status', ['Direkognisi', 'Tidak Direkognisi'])->nullable();
            $table->text('catatan_pleno')->nullable();
            $table->foreignId('ditetapkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_pleno')->nullable();
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'matakuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_pleno');
    }
};
