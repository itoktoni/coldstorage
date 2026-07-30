<?php

namespace Database\Seeders;

use App\Models\ContentEntry;
use App\Models\ContentType;
use Illuminate\Database\Seeder;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create frontend_section content type ──
        $sectionType = ContentType::firstOrCreate(
            ['slug' => 'frontend_section'],
            [
                'name'       => 'Frontend Section',
                'is_active'  => true,
                'menu_icon'  => 'widgets',
            ]
        );

        // ── 2. Delete existing entries by slug (handles soft-deletes) ──
        $allSlugs = array_merge(['homepage', 'slider', 'certifications', 'verification', 'services', 'cta', 'competency', 'news', 'clients', 'footer', 'hero', 'about', 'contact', 'cta-banner', 'footer-navigation', 'legal-compliance', 'search']);
        ContentEntry::whereIn('slug', $allSlugs)->forceDelete();
        $homepageType = ContentType::where('slug', 'homepage')->first();
        if ($homepageType) {
            ContentEntry::where('content_type_id', $homepageType->id)->forceDelete();
        }
        ContentEntry::where('content_type_id', $sectionType->id)->forceDelete();

        // ── 3. Create homepage content type ──
        $homepageType = ContentType::firstOrCreate(
            ['slug' => 'homepage'],
            [
                'name'       => 'Homepage',
                'is_active'  => true,
                'menu_icon'  => 'home',
            ]
        );

        // ── 4. Define section data ──
        $sliderData = [
            [
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBd8xKBdkjsLHWibHkNIx5Nn4N8Z-446sgDnskdWs7Rbio7ON1Zjmf1qg0xtT9v5g48WtqcAkljp642B287Sk0xjGOpqa7Zs1rD89wSum2TB0uMrzJKqt_Vd-gVdSSDHa5dLQGz9ecLgIRGTXFRALl0fEUilDx8phdo6z9fC6Z-_w3K1oDO0uFsG8lIdeohBqvI3FzuJyOJqpaFCkOdLibJR0guo1MSJuWIK7OYzoz96j3ra0ydC_cwaJYEWn2HZqJp83MWZnuigdZ1',
                'text'        => '<strong>Presisi Mutlak untuk Standar Kesehatan Global.</strong> Kalibrasi terakreditasi untuk alat kesehatan vital di Indonesia.',
                'button_text' => 'Mulai Sekarang',
                'button_url'  => '/layanan',
            ],
            [
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCiT2ICMT31yHvNTlw3DoKD-EtcQ8QSParinKwYZsDYy7LJyDVzi0SQbN-KuVBYsHG4RRuo68xFNAv-WMZM6mO33uvNUwOPMx_P3Wb9r8__3dXheQyf4626kp12aCig05lNhjbfJR97GoBSojdEpysX03xzIgBcDv7jyPuN_Fx_93m98JjfMU1zrXb2PhsftTvPbTOBj5q3WcJA2oiPAkXDwUCyEvl5UBjNdOGNIQOdqtd3tE-5LMoqameG9Xr2YzI4AdUulHQqYDj1',
                'text'        => '<strong>Standar Nasional & Internasional.</strong> Memastikan setiap alat medis bekerja dalam batas toleransi yang presisi.',
                'button_text' => 'Pelajari Lebih Lanjut',
                'button_url'  => '/tentang-kami',
            ],
        ];

        $certificationsData = [
            [
                'title'       => 'KAN LK-002-IDN',
                'description' => 'Akreditasi nasional untuk institusi pengujian fasilitas kesehatan sesuai standar nasional dan internasional.',
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAni7KlIuMfqfnEQ_xYG7xGURljqIBBhN1LZSLAetRSJWwY9fTZm2sjPZQcrynMPEjeJhf9PxYdTlcZfLo9BS0W5QTKEh4hprmUOag6MxXdivQL6qpl-ayH0Y_wSQNUizWjUNeR836PhR1DOnv3Ep0UAG61AQ1QA9_h_ZGhkgJ8cwZBjpv_DoeR8S6BzTwE6GxYlPdte8fRkoU6aS7W6F89XcG_4WfUA67cyD4HSp5Wqk8s3xtNDgcqsZzeb3_wIPV7iw3R5vOF5Vq5',
            ],
            [
                'title'       => 'ISO 17025',
                'description' => 'Standar kompetensi lembaga pengujian fasilitas kesehatan untuk memastikan ketelitian teknis dan mutu layanan.',
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCHXcQ4pYaxFlXnw4afuReAYyYozJTtqq1_xu7PIXXwz3jmwNQb8iFN1kpLHG_1XQL__NYC5-X5TOwNUMARBpCRByGF41PqvqkctH66qWwAqXtle339tKsYTEQIl-1GgN-3ABOFxeS4wQbxcm2mDmewwc6u31v9OLjgpnESB-mTu4kfD6t2MZ7U27kW4vNzsR6bMi0XYY-LJoF9z3pLH5LUd4ml7ya1b-snpleH-4RfKchIaPcnQ6MR5rHrC5AwarmqN0DP3ElTrma6',
            ],
            [
                'title'       => 'Ketelitian Tinggi',
                'description' => 'Ketelitian mutlak melalui standar yang tertraceability untuk setiap alat yang dikalibrasi.',
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBpZmIBi2QvqRC46sGLZQP7BvqUcnWA_XbRqAONaeGEjT967DViGQ2s_kA2ZXKn_Bl0x6f9R7aoB0MH1LmaIkPepUH4iMGyFaMOy0DQyh0Tas5zKY3bwRga6GpRcZCryfwP3N993SsgQHc-iiDBP0Sr8jOhBNlVimtzXvZxzRM-cxDeEBSD9gV4hysMhutAZWwlerM01lWFkBgCaCJ4VtojG6eUT9ZMytCPaCocpgs4rQ9hvLhMpnkdjiX7g_M8HOiAYRUQiH-cCmJM',
            ],
        ];

        $verificationData = [
            [
                'title'       => 'Portal Verifikasi Sertifikat',
                'description' => 'Verifikasi keaslian sertifikat kalibrasi alat kesehatan Anda menggunakan database akreditasi kami yang terintegrasi.',
                'button_text' => 'Verifikasi Sekarang',
                'button_url'  => '/verifikasi',
            ],
        ];

        $servicesData = [
            [
                'title'       => 'Kalibrasi Alat Kesehatan',
                'description' => 'Pengukuran dan penyesuaian ketelitian alat kesehatan sesuai standar nasional dan internasional.',
                'button_text' => 'Pelajari Lebih Lanjut',
                'button_url'  => '/layanan/kalibrasi',
            ],
            [
                'title'       => 'Inspeksi Preventive',
                'description' => 'Pemeriksaan berkala untuk memastikan alat kesehatan berfungsi optimal dan aman digunakan.',
                'button_text' => 'Pelajari Lebih Lanjut',
                'button_url'  => '/layanan/inspeksi',
            ],
            [
                'title'       => 'Konsultasi Teknis',
                'description' => 'Bimbingan ahli untuk pengelolaan dan pemeliharaan alat kesehatan di fasilitas Anda.',
                'button_text' => 'Pelajari Lebih Lanjut',
                'button_url'  => '/layanan/konsultasi',
            ],
        ];

        $ctaData = [
            [
                'title'        => 'Tingkatkan Keandalan Fasilitas Anda',
                'description'  => 'Bekerja sama dengan ECM untuk audit teknis dan siklus kalibrasi yang menjamin ketersediaan alat dan ketelitian maksimal.',
                'button_text'  => 'Konsultasi Sekarang',
                'button_url'   => '/kontak',
                'button2_text' => 'Lihat Katalog Layanan',
                'button2_url'  => '/layanan',
            ],
        ];

        $competencyData = [
            [
                'title'       => 'Tim Profesional Bersertifikat',
                'description' => 'Didukung oleh engineer Metrology bersertifikat KAN dan ISO dengan pengalaman puluhan tahun.',
                'icon'        => null,
            ],
            [
                'title'       => 'Peralatan Kalibrasi Kelas Dunia',
                'description' => 'Menggunakan standar referensi yang terkalibrasi hingga ketelitian 0.01% dengan traceability ke BIPM.',
                'icon'        => null,
            ],
            [
                'title'       => 'Jangkauan Luas & Respons Cepat',
                'description' => 'Melayani seluruh wilayah Indonesia dengan jadwal fleksibel dan respons 24 jam untuk keadaan darurat.',
                'icon'        => null,
            ],
            [
                'title'       => 'Sertifikat Digital Terintegrasi',
                'description' => 'Penerbitan sertifikat digital yang dapat diverifikasi secara online untuk keaslian dan status akreditasi.',
                'icon'        => null,
            ],
        ];

        $newsData = [
            [
                'title'       => 'Panduan Mutu Kalibrasi Alat Kesehatan 2024',
                'description' => 'ECM merilis panduan terbaru untuk standar kalibrasi sesuai regulasi Kemenkes.',
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBd8xKBdkjsLHWibHkNIx5Nn4N8Z-446sgDnskdWs7Rbio7ON1Zjmf1qg0xtT9v5g48WtqcAkljp642B287Sk0xjGOpqa7Zs1rD89wSum2TB0uMrzJKqt_Vd-gVdSSDHa5dLQGz9ecLgIRGTXFRALl0fEUilDx8phdo6z9fC6Z-_w3K1oDO0uFsG8lIdeohBqvI3FzuJyOJqpaFCkOdLibJR0guo1MSJuWIK7OYzoz96j3ra0ydC_cwaJYEWn2HZqJp83MWZnuigdZ1',
                'url'         => '/berita/panduan-kalibrasi-2024',
                'date'        => '2024',
            ],
            [
                'title'       => 'Regulasi Terbaru: Pedoman Kemenkes 2024',
                'description' => 'Bagaimana mandat baru untuk alat terapi mempengaruhi penyedia layanan kesehatan.',
                'image'       => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCiT2ICMT31yHvNTlw3DoKD-EtcQ8QSParinKwYZsDYy7LJyDVzi0SQbN-KuVBYsHG4RRuo68xFNAv-WMZM6mO33uvNUwOPMx_P3Wb9r8__3dXheQyf4626kp12aCig05lNhjbfJR97GoBSojdEpysX03xzIgBcDv7jyPuN_Fx_93m98JjfMU1zrXb2PhsftTvPbTOBj5q3WcJA2oiPAkXDwUCyEvl5UBjNdOGNIQOdqtd3tE-5LMoqameG9Xr2YzI4AdUulHQqYDj1',
                'url'         => '/berita/regulasi-kemenkes-2024',
                'date'        => '2024',
            ],
        ];

        $clientsData = [
            ['name' => 'National Hospital', 'material_icon' => 'apartment', 'icon' => null],
            ['name' => 'BioMed Research Lab', 'material_icon' => 'science', 'icon' => null],
            ['name' => "St. Mary's Medical", 'material_icon' => 'medical_services', 'icon' => null],
            ['name' => 'Global Health Institute', 'material_icon' => 'public', 'icon' => null],
            ['name' => 'Apex Diagnostics', 'material_icon' => 'biotech', 'icon' => null],
            ['name' => 'Mayo Regional Clinic', 'material_icon' => 'local_hospital', 'icon' => null],
            ['name' => 'Siloam Hospitals', 'material_icon' => 'biotech', 'icon' => null],
            ['name' => 'Pondok Indah Group', 'material_icon' => 'science', 'icon' => null],
            ['name' => 'RS Cipto', 'material_icon' => 'apartment', 'icon' => null],
            ['name' => 'Medistra Hospital', 'material_icon' => 'health_and_safety', 'icon' => null],
        ];

        $footerData = [
            [
                'title'    => 'Bersama Membangun Standar Kesehatan Nasional',
                'subtitle' => 'Partner Resmi Pemerintah untuk Akreditasi & Kalibrasi IPFK',
            ],
        ];

        // ── 5. Create section entries with meta column ──
        $allSections = [
            'slider'        => $sliderData,
            'certifications' => $certificationsData,
            'verification'  => $verificationData,
            'services'      => $servicesData,
            'cta'           => $ctaData,
            'competency'    => $competencyData,
            'news'          => $newsData,
            'clients'       => $clientsData,
            'footer'        => $footerData,
        ];

        $order = 0;
        foreach ($allSections as $slug => $data) {
            ContentEntry::create([
                'content_type_id' => $sectionType->id,
                'title'           => ucfirst($slug),
                'slug'            => $slug,
                'status'          => 'published',
                'menu_order'      => $order++,
                'meta'            => ['section_data' => $data],
            ]);
        }

        // ── 6. Create Homepage entry with page_builder referencing section slugs ──
        $home = ContentEntry::create([
            'content_type_id' => $homepageType->id,
            'title'           => 'Homepage - ECM',
            'slug'            => 'homepage',
            'status'          => 'published',
            'published_at'    => now(),
            'menu_order'      => 0,
        ]);

        $home->setMeta('page_builder', [
            ['_layout' => 'slider'],
            ['_layout' => 'certifications'],
            ['_layout' => 'verification'],
            ['_layout' => 'services'],
            ['_layout' => 'cta'],
            ['_layout' => 'competency'],
            ['_layout' => 'news'],
            ['_layout' => 'clients'],
            ['_layout' => 'footer'],
        ]);

        $this->command->info("Homepage created with 9 sections using frontend_section approach.");
    }
}