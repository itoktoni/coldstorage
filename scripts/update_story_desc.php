<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Activity;

$activity = Activity::where('slug', 'like', 'anak-penolong-%')->orderByDesc('id')->first();

if (!$activity) {
    echo "Activity not found\n";
    exit(1);
}

$activity->update([
    'desc' => 'Buat komik anak 6 halaman dengan layout sama di setiap gambar, ukuran identik 400x600px. Halaman pertama sebagai cover cerita, dan halaman terakhir untuk kesimpulan dan moral. Cerita tentang Riko, anak yang menolong temannya Tono di taman bermain. Gaya ilustrasi anak-anak yang ceria dan hangat, warna-warna cerah, karakter ekspresif. Halaman 1 (cover): Riko tersenyum di taman bermain. Halaman 2: Riko mendengar tangisan, melihat Tono terjatuh dari ayunan. Halaman 3: Riko berlari membantu Tono dengan perawatan pertama. Halaman 4: Riko membersihkan luka Tono dengan hati-hati. Halaman 5: Riko mengantar Tono pulang ke rumah. Halaman 6 (penutup): Riko dan Tono bermain bersama, orang tua Tono berterima kasih, pesan moral tentang kebaikan hati. Semua halaman konsisten: latar taman/rumah, karakter Riko dan Tono berwarna cerah, nuansa cerita anak yang menyenangkan.',
]);

echo "Updated desc for activity id: " . $activity->id . "\n";
echo "New desc: " . $activity->desc . "\n";
