<?php

namespace Database\Seeders;

use App\Models\Diskusi;
use App\Models\TanggapanDiskusi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiskusiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email_user');

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all()->keyBy('email_user');
        }

        $getUser = fn ($email) => $users->get($email)?->id_user ?? User::first()->id_user;

        $discussions = [
            [
                'author_email' => 'siti.aminah@gmail.com',
                'judul_diskusi' => 'Pertolongan pertama demam tinggi pada balita saat tengah malam',
                'isi_diskusi' => "Halo semua, tadi malam anak saya (usia 2 tahun) mendadak demam tinggi sampai 39.2 derajat Celcius. Sempat panik karena klinik faskes dekat rumah tutup. Setelah dikompres air hangat dan diberi parasetamol sirup, demamnya perlahan turun.\n\nKira-kira kapan ya kondisi demam balita harus segera dilarikan ke IGD rumah sakit? Terima kasih banyak sarannya.",
                'kategori' => 'Kesehatan Anak',
                'replies' => [
                    [
                        'email' => 'dr.sarah@medigo.id',
                        'body' => "Halo Ibu Siti, tindakan pertama yang Ibu lakukan sudah sangat tepat! Mengompres air hangat (bukan air es/dingin) pada lipatan tubuh dan memberikan parasetamol sesuai dosis berat badan adalah langkah awal yang benar.\n\nSegera bawa ke IGD jika:\n1. Demam > 39°C tidak turun setelah pemberian antipiretik\n2. Muncul kejang demam atau penurunan kesadaran\n3. Anak tampak sangat lemas, tidak mau minum susu/air\n4. Disertai sesak napas, bibir kebiruan, atau muntah terus-menerus.\n\nSemoga si kecil lekas pulih ya Bu!",
                        'child_replies' => [
                            [
                                'email' => 'siti.aminah@gmail.com',
                                'body' => 'Terima kasih banyak dr. Sarah atas penjelasannya yang sangat menenangkan. Pagi ini suhunya sudah 37.4 dan anaknya sudah mulai aktif kembali.',
                            ]
                        ]
                    ],
                    [
                        'email' => 'anisa.putri@gmail.com',
                        'body' => 'Setuju dengan dokter Sarah. Pengalaman saya dulu anak juga sempat demam 39.5, kata dokter jangan dipakaikan selimut tebal ya bun, gunakan baju tipis berbahan katun yang mudah menyerap keringat.',
                        'child_replies' => []
                    ],
                ]
            ],
            [
                'author_email' => 'dimas.pratama@gmail.com',
                'judul_diskusi' => 'Nyeri dada sebelah kiri tembus ke punggung, apakah tanda serangan jantung?',
                'isi_diskusi' => "Selamat siang rekan-rekan. Pagi ini dada sebelah kiri saya terasa seperti tertindih beban berat dan rasanya menjalar sampai ke punggung belakang dan bahu kiri.\n\nApakah ini gejala serangan jantung atau bisa karena asam lambung (GERD)? Gejala pembedanya seperti apa ya dok?",
                'kategori' => 'Kesehatan Jantung',
                'replies' => [
                    [
                        'email' => 'dr.andi@medigo.id',
                        'body' => "Siang Mas Dimas. Nyeri dada kiri dengan sensasi tertekan/tertindih beban berat yang menjalar ke punggung, leher, atau lengan kiri adalah TANDA BAHAYA (Red Flag) serangan jantung.\n\nJangan menunggu atau berspekulasi apakah itu GERD. Segera kunjungi IGD rumah sakit terdekat detik ini juga untuk dilakukan pemeriksaan rekam jantung (EKG) dan enzim troponin. Waktu sangat berharga dalam penanganan kegawatdaruratan jantung!",
                        'child_replies' => []
                    ],
                    [
                        'email' => 'fajar.nugroho@outlook.com',
                        'body' => 'Mas Dimas langsung minta antar keluarga atau panggil ambulans sekarang juga ke IGD RS terdekat ya mas. Jangan nyetir kendaraan sendiri!',
                        'child_replies' => []
                    ],
                ]
            ],
            [
                'author_email' => 'dewi.lestari@gmail.com',
                'judul_diskusi' => 'Prosedur rujukan berjenjang BPJS Kesehatan dari Puskesmas ke Rumah Sakit',
                'isi_diskusi' => "Halo semuanya, mau tanya alur rujukan BPJS Kesehatan saat ini. Jika dari Faskes Tingkat Pertama (FKTP Puskesmas) ingin minta rujukan ke dokter spesialis di RSUD, dokumen apa saja yang wajib dibawa dan berapa lama masa berlaku surat rujukannya ya?",
                'kategori' => 'Kesehatan Umum',
                'replies' => [
                    [
                        'email' => 'reza.rahardian@yahoo.com',
                        'body' => "Halo Mbak Dewi, cukup bawa e-KTP dan kartu KIS/BPJS digital di aplikasi Mobile JKN. Di Puskesmas nanti diperiksa dokter umum dulu.\n\nJika butuh penanganan spesialis, dokter akan menerbitkan Surat Rujukan Online. Masa berlaku rujukan sekarang 90 hari (3 bulan) dan otomatis terdata di sistem online RSUD tujuan.",
                        'child_replies' => [
                            [
                                'email' => 'dewi.lestari@gmail.com',
                                'body' => 'Wah terima kasih banyak infonya Mas Reza, sangat jelas dan membantu!',
                            ]
                        ]
                    ],
                    [
                        'email' => 'bayu.samudra@gmail.com',
                        'body' => 'Tambahan juga mbak, setelah dapat nomor rujukan dari Puskesmas, mbak bisa langsung ambil nomor antrean poli di RS lewat menu Pendaftaran Pelayanan di aplikasi Mobile JKN, jadi tidak perlu antre dari subuh di rumah sakit.',
                        'child_replies' => []
                    ],
                ]
            ],
            [
                'author_email' => 'fajar.nugroho@outlook.com',
                'judul_diskusi' => 'Tips menjaga kadar HbA1c dan gula darah puasa tetap stabil bagi diabetesi',
                'isi_diskusi' => "Halo teman-teman pejuang diabetes. Hasil cek lab terakhir saya menunjukkan HbA1c 7.8% dan gula darah puasa 145 mg/dL. Dokter menyarankan perbaikan pola makan dan olahraga rutin. Adakah tips menu makanan sehari-hari yang mengenyangkan tapi aman dari lonjakan gula darah?",
                'kategori' => 'Penyakit Dalam',
                'replies' => [
                    [
                        'email' => 'dr.budi@medigo.id',
                        'body' => "Halo Pak Fajar. Target HbA1c untuk penderita diabetes umumnya di bawah 7.0%. Beberapa tips praktis:\n1. Ganti nasi putih dengan karbohidrat kompleks berserat tinggi (nasi merah, kentang rebus beserta kulit, shirataki, atau oatmeal).\n2. Utamakan sayuran hijau sebagai pembuka makan untuk memperlambat penyerapan glukosa.\n3. Lakukan aktivitas fisik sedang seperti jalan cepat 30 menit per hari, minimal 5 hari seminggu.\n4. Minum obat antidiabetes atau insulin teratur sesuai resep dokter.",
                        'child_replies' => []
                    ],
                    [
                        'email' => 'siti.aminah@gmail.com',
                        'body' => 'Ayah saya juga diabetes Pak Fajar, sejak rutin jalan pagi dan hindari minuman manis kemasan, gula darah puasanya turun stabil di angka 105-115 mg/dL. Semangat ya pak!',
                        'child_replies' => []
                    ],
                ]
            ],
            [
                'author_email' => 'anisa.putri@gmail.com',
                'judul_diskusi' => 'Pembersihan karang gigi (Scaling) menggunakan fasilitas BPJS Kesehatan',
                'isi_diskusi' => "Halo dokter dan teman-teman, apakah layanan pembersihan karang gigi (scaling) bisa ditanggung oleh BPJS Kesehatan di faskes tingkat 1 (klinik/puskesmas)? Apakah ada batasan berapa kali dalam setahun?",
                'kategori' => 'Kesehatan Gigi',
                'replies' => [
                    [
                        'email' => 'drg.maya@medigo.id',
                        'body' => "Halo Mbak Anisa. Layanan scaling gigi ditanggung oleh BPJS Kesehatan di FKTP (Puskesmas atau Klinik Pratama yang bekerja sama dengan dokter gigi) sebanyak 1 kali dalam 1 tahun atas indikasi medis (seperti adanya gingivitis/radang gusi akibat penumpukan karang gigi). Silakan datang langsung ke faskes terdaftar ya.",
                        'child_replies' => [
                            [
                                'email' => 'anisa.putri@gmail.com',
                                'body' => 'Terima kasih banyak dokter Maya atas informasinya, sangat bermanfaat!',
                            ]
                        ]
                    ],
                    [
                        'email' => 'dimas.pratama@gmail.com',
                        'body' => 'Bulan lalu saya scaling di Puskesmas kecamatan pakai BPJS, dokternya sangat teliti dan tidak ada biaya tambahan apapun.',
                        'child_replies' => []
                    ],
                ]
            ],
            [
                'author_email' => 'reza.rahardian@yahoo.com',
                'judul_diskusi' => 'Pentingnya Medical Check-Up (MCU) rutin bagi usia produktif 25-40 tahun',
                'isi_diskusi' => "Banyak penyakit kronis seperti hipertensi, kolesterol tinggi, dan perlemakan hati yang tidak bergejala sama sekali di awal (silent killer). Menurut rekan-rekan dokter, paket atau parameter lab apa saja yang minimal dicek setahun sekali?",
                'kategori' => 'Gaya Hidup & Nutrisi',
                'replies' => [
                    [
                        'email' => 'dr.budi@medigo.id',
                        'body' => "Untuk usia produktif 25-40 tahun, parameter penting yang perlu dievaluasi berkala:\n1. Tekanan darah dan indeks massa tubuh (IMT)\n2. Profil lipid darah (Kolesterol Total, HDL, LDL, Trigliserida)\n3. Gula darah puasa (GDP)\n4. Fungsi ginjal (Kreatinin, Ureum) dan Asam Urat\n5. Rontgen thorax (terutama perokok aktif/pasif).\n\nDeteksi dini jauh lebih hemat dan menyelamatkan jiwa daripada mengobati komplikasi.",
                        'child_replies' => []
                    ],
                    [
                        'email' => 'rina.wulandari@gmail.com',
                        'body' => 'Sekarang di Puskesmas juga ada skrining kesehatan gratis setahun sekali lewat program BPJS, hasilnya langsung bisa dikonsultasikan dengan dokter umum.',
                        'child_replies' => []
                    ],
                ]
            ],
        ];

        foreach ($discussions as $d) {
            $authorId = $getUser($d['author_email']);

            $diskusi = Diskusi::firstOrCreate(
                [
                    'judul_diskusi' => $d['judul_diskusi'],
                ],
                [
                    'id_user' => $authorId,
                    'isi_diskusi' => $d['isi_diskusi'],
                    'kategori' => $d['kategori'],
                    'status_diskusi' => 'active',
                    'created_at' => now()->subDays(rand(1, 10)),
                    'updated_at' => now(),
                ]
            );

            // Seed replies
            foreach ($d['replies'] as $rep) {
                $repUserId = $getUser($rep['email']);

                $tanggapan = TanggapanDiskusi::firstOrCreate(
                    [
                        'id_diskusi' => $diskusi->id_diskusi,
                        'id_user' => $repUserId,
                        'isi_tanggapan' => $rep['body'],
                    ],
                    [
                        'id_parent' => null,
                        'created_at' => now()->subDays(rand(0, 3)),
                        'updated_at' => now(),
                    ]
                );

                // Nested reply
                if (! empty($rep['child_replies'])) {
                    foreach ($rep['child_replies'] as $child) {
                        $childUserId = $getUser($child['email']);
                        TanggapanDiskusi::firstOrCreate(
                            [
                                'id_diskusi' => $diskusi->id_diskusi,
                                'id_user' => $childUserId,
                                'isi_tanggapan' => $child['body'],
                            ],
                            [
                                'id_parent' => $tanggapan->id_tanggapan,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }
}
