<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_it_wos', function (Blueprint $table) {
            $table->string('no_dokumen')->nullable()->after('id');
            $table->string('tanggal_dokumen')->nullable()->after('no_dokumen');
            $table->string('versi_dokumen')->nullable()->after('tanggal_dokumen');
            $table->string('halaman', 20)->nullable()->after('versi_dokumen');
        });

        Schema::table('form_it_wocs', function (Blueprint $table) {
            $table->string('no_dokumen')->nullable()->after('id');
            $table->string('tanggal_dokumen')->nullable()->after('no_dokumen');
            $table->string('versi_dokumen')->nullable()->after('tanggal_dokumen');
            $table->string('halaman', 20)->nullable()->after('versi_dokumen');
        });
    }

    public function down(): void
    {
        Schema::table('form_it_wos', function (Blueprint $table) {
            $table->dropColumn(['no_dokumen', 'tanggal_dokumen', 'versi_dokumen', 'halaman']);
        });

        Schema::table('form_it_wocs', function (Blueprint $table) {
            $table->dropColumn(['no_dokumen', 'tanggal_dokumen', 'versi_dokumen', 'halaman']);
        });
    }
};