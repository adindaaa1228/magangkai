<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_it_wocs', function (Blueprint $table) {
            $table->id();
            // Kop / Header
            $table->string('no_ref')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->string('business_area')->nullable();
            $table->string('judul_kegiatan')->nullable();

            // Relasi opsional ke IT WO
            $table->foreignId('form_it_wo_id')->nullable()->constrained('form_it_wos')->nullOnDelete();

            // Tanggal
            $table->date('tanggal_itwo')->nullable();
            $table->date('tanggal_penyelesaian')->nullable();

            // Penanggung Jawab
            $table->string('penanggung_jawab_nama')->nullable();
            $table->string('penanggung_jawab_nipp', 50)->nullable();

            // Pelaksana Pekerjaan
            $table->string('pelaksana_nama')->nullable();
            $table->string('pelaksana_nipp', 50)->nullable();
            $table->string('pelaksana_jabatan')->nullable();

            // Lokasi & Dokumen
            $table->string('lokasi_pekerjaan')->nullable();
            $table->string('dokumen_terkait')->nullable();

            // Narasi
            $table->text('arahan_eksekusi')->nullable();
            $table->text('dampak_eksekusi')->nullable();
            $table->text('alasan_pekerjaan')->nullable();
            $table->text('laporan_hasil_pekerjaan')->nullable();
            $table->text('catatan')->nullable();

            // Biaya
            $table->decimal('biaya_valas', 18, 2)->nullable();
            $table->decimal('biaya_rp', 18, 2)->nullable();
            $table->text('catatan_biaya')->nullable();

            // Tanda Tangan
            $table->string('pengusul_nama')->nullable();
            $table->string('pengusul_jabatan')->nullable();
            $table->date('pengusul_tanggal')->nullable();

            $table->string('disetujui_nama')->nullable();
            $table->string('disetujui_jabatan')->nullable();
            $table->date('disetujui_tanggal')->nullable();

            $table->string('penanggung_jawab_ttd_nama')->nullable();
            $table->string('penanggung_jawab_ttd_jabatan')->nullable();
            $table->date('penanggung_jawab_ttd_tanggal')->nullable();

            $table->enum('status', ['draft', 'dicetak', 'selesai'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_it_wocs');
    }
};