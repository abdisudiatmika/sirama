<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_lampiran_lama', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_mk_id')->constrained('pengajuan_mk')->cascadeOnDelete();
            $table->foreignId('lampiran_lama_id')->constrained('lampiran_lama')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pengajuan_mk_id', 'lampiran_lama_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_lampiran_lama');
    }
};
