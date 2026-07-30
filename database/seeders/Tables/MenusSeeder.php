<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --mode=table --tables=menus
         *
         */

        $dataTables = [
            [
                'created_at' => '2026-07-26 22:11:48',
                'deleted_at' => NULL,
                'id' => 1,
                'is_active' => 1,
                'items' => '[{"label":"Profil","url":"#profil","icon":null,"target":"_self","sort_order":0,"children":[{"label":"Sejarah","url":"\\/sejarah","icon":"history_edu","target":"_self"},{"label":"Visi & Misi","url":"\\/visi-misi","icon":"visibility","target":"_self"},{"label":"Struktur Organisasi","url":"\\/struktur-organisasi","icon":"account_tree","target":"_self"},{"label":"Wilayah Kerja","url":"\\/wilayah-kerja","icon":"location_on","target":"_self"}]},{"label":"Layanan","url":"\\/services","icon":null,"target":"_self","sort_order":1},{"label":"Berita","url":"\\/blog","icon":null,"target":"_self","sort_order":2},{"label":"Kontak","url":"\\/contact","icon":null,"target":"_self","sort_order":3},{"label":"Dokumentasi","url":"\\/documentation","icon":null,"target":"_self","sort_order":4}]',
                'location' => 'main',
                'name' => 'Main Navigation',
                'slug' => 'main-nav',
                'sort_order' => 0,
                'updated_at' => '2026-07-30 00:38:19',
            ],
            [
                'created_at' => '2026-07-30 00:20:38',
                'deleted_at' => '2026-07-30 00:22:33',
                'id' => 4,
                'is_active' => 1,
                'items' => '[{"label":"Layanan","url":"\\/services","target":"_self","sort_order":0},{"label":"Berita","url":"\\/blog","target":"_self","sort_order":1},{"label":"Kontak","url":"\\/contact","target":"_self","sort_order":2}]',
                'location' => 'main',
                'name' => 'Main Navigation',
                'slug' => 'main-navigation',
                'sort_order' => 0,
                'updated_at' => '2026-07-30 00:22:33',
            ],
            [
                'created_at' => '2026-07-30 00:53:40',
                'deleted_at' => NULL,
                'id' => 5,
                'is_active' => 1,
                'items' => '[{"label":"Layanan","url":"#","icon":null,"target":"_self","sort_order":0,"children":[{"label":"Kalibrasi Alat Kesehatan","url":"\\/services","icon":null,"target":"_self"},{"label":"Inspeksi Preventive","url":"\\/services","icon":null,"target":"_self"},{"label":"Konsultasi Teknis","url":"\\/services","icon":null,"target":"_self"},{"label":"Verifikasi Sertifikat","url":"\\/services","icon":null,"target":"_self"}]},{"label":"Perusahaan","url":"#","icon":null,"target":"_self","sort_order":1,"children":[{"label":"Tentang Kami","url":"\\/sejarah","icon":null,"target":"_self"},{"label":"Berita","url":"\\/blog","icon":null,"target":"_self"},{"label":"Sertifikat Akreditasi","url":"#","icon":null,"target":"_self"},{"label":"Hubungi Kami","url":"\\/contact","icon":null,"target":"_self"}]}]',
                'location' => 'footer',
                'name' => 'Footer Navigation',
                'slug' => 'footer-nav',
                'sort_order' => 0,
                'updated_at' => '2026-07-30 00:53:40',
            ]
        ];
        
        DB::table("menus")->insert($dataTables);
    }
}