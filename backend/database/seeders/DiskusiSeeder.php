<?php

namespace Database\Seeders;

use App\Models\Diskusi;
use App\Models\Faskes;
use App\Models\TanggapanDiskusi;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        // Ambil beberapa faskes untuk ditautkan sebagai faskes mention
        $sampleRs = Faskes::where('jenis_faskes', 'like', '%Rumah Sakit%')->orderBy('id_faskes')->take(5)->get();
        $samplePuskesmas = Faskes::where('jenis_faskes', 'like', '%Puskesmas%')->orderBy('id_faskes')->take(3)->get();
        $sampleKlinik = Faskes::where('jenis_faskes', 'like', '%Klinik%')->orderBy('id_faskes')->take(3)->get();

        $faskesRs1 = $sampleRs->get(0)?->id_faskes;
        $faskesRs2 = $sampleRs->get(1)?->id_faskes;
        $faskesRs3 = $sampleRs->get(2)?->id_faskes;
        $faskesPkm1 = $samplePuskesmas->get(0)?->id_faskes;
        $faskesKlinik1 = $sampleKlinik->get(0)?->id_faskes;

        $discussions = [
            // 1. Kategori: Ulasan Rumah Sakit (Merujuk ke Faskes RS)
            [
                'author_email' => 'siti.aminah@gmail.com',
                'judul_diskusi' => 'Pengalaman rawat inap dan keramahan perawat di rumah sakit rujukan',
                'isi_diskusi' => "Minggu lalu ibu saya sempat dirawat selama 4 hari karena demam berdarah (DBD). Pelayanan di IGD sangat cepat tanggap, dokter spesialis penyakit dalam visit tepat waktu setiap pagi, dan suster di lantai 3 sangat ramah serta informatif.\n\nFasilitas kamar rawat inap bersih dan ber-AC dingin. Sangat merekomendasikan rumah sakit ini bagi rekan-rekan yang membutuhkan rujukan faskes di Jabodetabek.",
                'kategori' => 'Ulasan Rumah Sakit',
                'id_faskes_mention' => $faskesRs1,
                'replies' => [
                    [
                        'email' => 'dr.budi@medigo.id',
                        'body' => "Terima kasih ulasannya Bu Siti. Senang mendengar ibunda tertangani dengan baik. Pasien DBD memang memerlukan pemantauan ketat trombosit dan hidrasi cairan per jam. Semoga ibunda sehat selalu!",
                        'child_replies' => [
                            [
                                'email' => 'siti.aminah@gmail.com',
                                'body' => 'Terima kasih banyak dr. Budi atas perhatiannya!',
                            ]
                        ]
                    ],
                    [
                        'email' => 'dimas.pratama@gmail.com',
                        'body' => 'Bulan lalu ayah saya juga dirawat di sini. Proses klaim asuransi dan administrasi keluarnya juga cepat tidak sampai 1 jam.',
                        'child_replies' => []
                    ],
                ]
            ],

            // 2. Kategori: BPJS (Merujuk ke Puskesmas)
            [
                'author_email' => 'dewi.lestari@gmail.com',
                'judul_diskusi' => 'Prosedur rujukan berjenjang BPJS Kesehatan dari FKTP ke Poli Spesialis',
                'isi_diskusi' => "Halo semuanya, mau berbagi pengalaman alur rujukan BPJS Kesehatan terbaru. Dari Faskes Tingkat Pertama (FKTP Puskesmas), sekarang sudah full online via PCare. Pasien diperiksa dokter umum dulu, lalu surat rujukan online diterbitkan otomatis dan bisa dicek di aplikasi Mobile JKN.\n\nMasa berlaku rujukan adalah 90 hari (3 bulan). Sangat mudah dan praktis tanpa antre berulang kali.",
                'kategori' => 'BPJS',
                'id_faskes_mention' => $faskesPkm1,
                'replies' => [
                    [
                        'email' => 'reza.rahardian@yahoo.com',
                        'body' => "Betul sekali Mbak Dewi. Setelah terbit rujukan online dari faskes 1, kita bisa langsung ambil nomor antrean poli lewat menu Pendaftaran Pelayanan di aplikasi Mobile JKN.",
                        'child_replies' => [
                            [
                                'email' => 'dewi.lestari@gmail.com',
                                'body' => 'Iya mas, jadi tidak perlu datang subuh-subuh lagi ke loket rumah sakit.',
                            ]
                        ]
                    ],
                    [
                        'email' => 'dr.sarah@medigo.id',
                        'body' => "Edukasi yang sangat bagus. Selama indikasi medis terpenuhi, FKTP akan merujuk ke RS tipe C atau B terdekat yang memiliki dokter spesialis terkait.",
                        'child_replies' => []
                    ],
                ]
            ],

            // 3. Kategori: Rekomendasi RS (Merujuk ke Faskes RS)
            [
                'author_email' => 'dimas.pratama@gmail.com',
                'judul_diskusi' => 'Rekomendasi rumah sakit dengan fasilitas NICU dan dokter anak terbaik',
                'isi_diskusi' => "Selamat siang rekan-rekan MediGO. Istri saya saat ini hamil 32 minggu dengan kondisi kehamilan risiko tinggi. Dokter obgyn menyarankan melahirkan di rumah sakit yang memiliki fasilitas NICU (Neonatal Intensive Care Unit) lengkap.\n\nApakah ada rekomendasi rumah sakit dengan dokter spesialis anak konsultan perinatologi yang berpengalaman dan fasilitas NICU memadai? Mohon infonya ya.",
                'kategori' => 'Rekomendasi RS',
                'id_faskes_mention' => $faskesRs2,
                'replies' => [
                    [
                        'email' => 'dr.sarah@medigo.id',
                        'body' => "Siang Mas Dimas. Rumah sakit ini memiliki fasilitas NICU level 3 dengan tim konsultan perinatologi siaga 24 jam. Sangat tepat untuk rujukan persalinan risiko tinggi. Segera konsultasikan dan buat rencana persalinan bersama dokter spesialis yang bertugas ya.",
                        'child_replies' => []
                    ],
                    [
                        'email' => 'anisa.putri@gmail.com',
                        'body' => 'Tahun lalu keponakan saya lahir prematur di minggu ke-33 dan dirawat di NICU RS ini selama 2 minggu. Tim dokternya sangat komunikatif memberikan update harian ke keluarga.',
                        'child_replies' => []
                    ],
                ]
            ],

            // 4. Kategori: Tips Kesehatan
            [
                'author_email' => 'fajar.nugroho@outlook.com',
                'judul_diskusi' => 'Tips menjaga kadar gula darah puasa dan HbA1c stabil bagi diabetesi',
                'isi_diskusi' => "Halo teman-teman pejuang diabetes. Hasil cek lab terakhir saya menunjukkan HbA1c 7.8% dan gula darah puasa 145 mg/dL. Setelah konsultasi gizi, ada beberapa tips praktis yang saya terapkan:\n1. Mengganti nasi putih dengan nasi merah atau shirataki.\n2. Mengonsumsi sayuran hijau terlebih dahulu sebelum lauk dan karbohidrat.\n3. Jalan santai 30 menit setiap hari.\n\nAdakah tips menu makanan lainnya yang rekan-rekan terapkan sehari-hari?",
                'kategori' => 'Tips Kesehatan',
                'id_faskes_mention' => null,
                'replies' => [
                    [
                        'email' => 'dr.budi@medigo.id',
                        'body' => "Langkah yang luar biasa Pak Fajar! Metode 'sayur dulu baru nasi' (food sequencing) terbukti klinis memperlambat lonjakan glukosa darah pascamakan. Jangan lupa cukupi kebutuhan tidur 7-8 jam per hari karena kurang tidur dapat memicu resistensi insulin.",
                        'child_replies' => []
                    ],
                    [
                        'email' => 'siti.aminah@gmail.com',
                        'body' => 'Saya juga rutin minum rebusan kayu manis tanpa gula, sangat membantu tubuh lebih segar di pagi hari.',
                        'child_replies' => []
                    ],
                ]
            ],

            // 5. Kategori: BPJS (Pembersihan Karang Gigi di Faskes Tingkat 1)
            [
                'author_email' => 'anisa.putri@gmail.com',
                'judul_diskusi' => 'Pembersihan karang gigi (Scaling) gratis menggunakan BPJS Kesehatan',
                'isi_diskusi' => "Bagi yang belum tahu, layanan pembersihan karang gigi (scaling) ditanggung oleh BPJS Kesehatan di FKTP (Puskesmas atau Klinik Pratama) sebanyak 1 kali dalam setahun atas indikasi medis dokter gigi.\n\nTadi pagi saya baru selesai scaling di klinik rekanan faskes 1, prosesnya sekitar 30 menit, dokternya sangat teliti, dan 100% bebas biaya tambahan.",
                'kategori' => 'BPJS',
                'id_faskes_mention' => $faskesKlinik1,
                'replies' => [
                    [
                        'email' => 'drg.maya@medigo.id',
                        'body' => "Tepat sekali Mbak Anisa. Scaling sangat penting untuk mencegah radang gusi (gingivitis) dan periodontitis yang bisa membuat gigi goyang. Manfaatkan hak layanan kesehatan preventif ini setahun sekali ya.",
                        'child_replies' => [
                            [
                                'email' => 'anisa.putri@gmail.com',
                                'body' => 'Terima kasih banyak dokter Maya atas edukasinya!',
                            ]
                        ]
                    ],
                ]
            ],

            // 6. Kategori: Umum (Pelayanan IGD 24 Jam dan Kesiapan Darurat)
            [
                'author_email' => 'bayu.samudra@gmail.com',
                'judul_diskusi' => 'Pelayanan IGD 24 jam dan ketersediaan ambulans gawat darurat',
                'isi_diskusi' => "Kemarin malam tetangga kami mendadak sesak napas berat dan kami menghubungi nomor hotline darurat faskes ini. Tim ambulans tiba dalam waktu 15 menit lengkap dengan tabung oksigen dan paramedis terlatih.\n\nPenanganan triase di IGD sangat cepat dan tanggap tanpa birokrasi berbelit. Sangat bersyukur faskes di Jabodetabek semakin responsif untuk kegawatdaruratan.",
                'kategori' => 'Umum',
                'id_faskes_mention' => $faskesRs3,
                'replies' => [
                    [
                        'email' => 'dr.andi@medigo.id',
                        'body' => "Penyelamatan pasien sesak napas akut sangat tergantung pada kecepatan 'Golden Hour'. Menyimpan nomor kontak ambulans IGD faskes terdekat di ponsel keluarga adalah langkah bijak yang sangat kami rekomendasikan.",
                        'child_replies' => []
                    ],
                ]
            ],

            // 7. Kategori: Rekomendasi RS (Konsultasi Dokter Spesialis Mata)
            [
                'author_email' => 'reza.rahardian@yahoo.com',
                'judul_diskusi' => 'Rekomendasi klinik mata dan spesialis bedah katarak di Jabodetabek',
                'isi_diskusi' => "Ayah saya divonis katarak imatur di kedua mata dan disarankan operasi Phacoemulsification (tanpa jahitan). Apakah ada yang punya pengalaman operasi katarak di rumah sakit rujukan ini? Bagaimana proses pemulihan pasca operasinya?",
                'kategori' => 'Rekomendasi RS',
                'id_faskes_mention' => $faskesRs1,
                'replies' => [
                    [
                        'email' => 'dr.budi@medigo.id',
                        'body' => "Metode Phacoemulsification adalah standar emas saat ini. Operasi hanya memakan waktu 15-20 menit per mata dengan pembiusan lokal (tetes mata). Pasien bisa langsung pulang hari itu juga (one-day care). Pemulihan penglihatan biasanya optimal dalam 3-7 hari.",
                        'child_replies' => []
                    ],
                ]
            ],
        ];

        foreach ($discussions as $d) {
            $authorId = $getUser($d['author_email']);

            $diskusi = Diskusi::updateOrCreate(
                [
                    'judul_diskusi' => $d['judul_diskusi'],
                ],
                [
                    'id_user' => $authorId,
                    'isi_diskusi' => $d['isi_diskusi'],
                    'kategori' => $d['kategori'],
                    'id_faskes_mention' => $d['id_faskes_mention'] ?? null,
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
