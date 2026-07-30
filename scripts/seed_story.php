<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Activity;

$item = [
    'type' => 'storytelling',
    'title' => 'Anak Penolong',
    'slug' => 'anak-penolong-' . uniqid(),
    'desc' => 'Buat komik anak 6 halaman dengan layout sama di setiap gambar, ukuran identik 400x600px. Halaman pertama sebagai cover cerita, dan halaman terakhir untuk kesimpulan dan moral. Cerita tentang Riko, anak yang menolong temannya Tono di taman bermain. Gaya ilustrasi anak-anak yang ceria dan hangat, warna-warna cerah, karakter ekspresif. Halaman 1 (cover): Riko tersenyum di taman bermain. Halaman 2: Riko mendengar tangisan, melihat Tono terjatuh dari ayunan. Halaman 3: Riko berlari membantu Tono dengan perawatan pertama. Halaman 4: Riko membersihkan luka Tono dengan hati-hati. Halaman 5: Riko mengantar Tono pulang ke rumah. Halaman 6 (penutup): Riko dan Tono bermain bersama, orang tua Tono berterima kasih, pesan moral tentang kebaikan hati. Semua halaman konsisten: latar taman/rumah, karakter Riko dan Tono berwarna cerah, nuansa cerita anak yang menyenangkan.',
    'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=400&h=600&fit=crop',
    'moral' => 'Menolong orang lain tanpa pamrih adalah perbuatan terpuji.',
    'ages' => [2,3,4,5,6,7,8,9,10,11],
    'skills' => ['peduli_sesama','empati','bersyukur'],
    'data' => [
        'pages' => [
            ['num'=>1,'image'=>'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=400&h=600&fit=crop','text'=>'Riko sedang bermain di taman ketika mendengar suara tangisan.'],
            ['num'=>2,'image'=>'https://images.unsplash.com/photo-1518709766631-a6a7f45921c3?w=400&h=600&fit=crop','text'=>'Anak kecil bernama Tono terjatuh dari ayunan dan lututnya berdarah.'],
            ['num'=>3,'image'=>'https://images.unsplash.com/photo-1474511320723-9a56873571b7?w=400&h=600&fit=crop','text'=>'Riko segera berlari mendekati Tono. Kau baik-baik saja?'],
            ['num'=>4,'image'=>'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=400&h=600&fit=crop','text'=>'Riko mengambil air dan membersihkan luka Tono dengan hati-hati.'],
            ['num'=>5,'image'=>'https://images.unsplash.com/photo-1502082553048-f009c37129b9?w=400&h=600&fit=crop','text'=>'Riko mengantarkan Tono ke rumahnya dan memberitahu orang tuanya.'],
            ['num'=>6,'image'=>'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=400&h=600&fit=crop','text'=>'Orang tua Tono berterima kasih. Riko pulang dengan hati senang.'],
        ],
    ],
    'sort_order' => 0,
    'active' => true,
    'views' => 0,
    'status' => 'approved',
];

$activity = Activity::create($item);
print_r($activity->toArray());
