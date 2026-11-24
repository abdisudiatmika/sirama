<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            // Path on the private "local" disk; printed on the assessor's forms.
            $table->string('tanda_tangan')->nullable()->after('no_hp');
        });
    }

    public function down(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            $table->dropColumn('tanda_tangan');
        });
    }
};
