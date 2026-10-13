<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FormTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $templates = [
            [
                'nama' => 'Formulir IT Work Order',
                'kategori' => 'Terbatas',
                'route_name' => 'form-it-wo.index',
                'no_dokumen' => 'FR.SM/TI/031.002/10-2020',
                'tanggal_dokumen' => '12 Oktober 2020',
                'versi_dokumen' => '002-2020',
            ],
            [
                'nama' => 'Formulir IT Work Order Completion',
                'kategori' => 'Terbatas',
                'route_name' => 'form-it-woc.index',
                'no_dokumen' => 'FR.SM/TI/031.003/10-2020',
                'tanggal_dokumen' => '12 Oktober 2020',
                'versi_dokumen' => '002-2020',
            ],
        ];

        foreach ($templates as $template) {
            FormTemplate::updateOrCreate(
                ['nama' => $template['nama']],
                $template
            );
        }

        // Hapus sisa template formulir lain yang sudah tidak dipakai
        FormTemplate::whereNotIn('nama', array_column($templates, 'nama'))->delete();
    }
}