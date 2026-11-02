<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_lama', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->unsignedBigInteger('legacy_id')->unique();
            $table->string('label')->nullable();
            $table->string('nama_file');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('ukuran')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_lama');
    }
};
