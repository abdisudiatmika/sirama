<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pernyataan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswa')->cascadeOnDelete();
            $table->boolean('disetujui')->default(false);
            $table->string('tempat')->nullable();
            $table->date('tanggal')->nullable();
            $table->timestamp('waktu_setuju')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pernyataan');
    }
};
