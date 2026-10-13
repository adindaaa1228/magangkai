<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_it_wos', function (Blueprint $table) {
            $table->string('penanggung_jawab_jabatan')->nullable()->after('penanggung_jawab_nipp');
        });

        Schema::table('form_it_wocs', function (Blueprint $table) {
            $table->string('penanggung_jawab_jabatan')->nullable()->after('penanggung_jawab_nipp');
        });
    }

    public function down(): void
    {
        Schema::table('form_it_wos', function (Blueprint $table) {
            $table->dropColumn('penanggung_jawab_jabatan');
        });

        Schema::table('form_it_wocs', function (Blueprint $table) {
            $table->dropColumn('penanggung_jawab_jabatan');
        });
    }
};