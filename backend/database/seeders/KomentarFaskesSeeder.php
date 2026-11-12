<?php

namespace Database\Seeders;

use App\Models\Faskes;
use App\Models\Komentar;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KomentarFaskesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role_user', 'user')->get();
        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::where('role_user', 'user')->get();
        }

        // Ambil faskes representatif (RS Utama, RSUD, Puskesmas, Klinik)
        $targetIds = [55, 63, 64, 53, 1, 2, 3, 4, 6, 7];
        $faskesList = Faskes::whereIn('id_faskes', $targetIds)->get();

        if ($faskesList->isEmpty()) {
            $faskesList = Faskes::limit(10)->get();
        }

        $reviewTemplates = [
            [
                'rating' => 5,
                'isi' => 'Pelayanan IGD sangat sigap dan cepat tanggap. Dokter jaga serta para perawat melayani dengan ramah dan cekatan meskipun saya datang saat dini hari. Fasilitasnya bersih dan tertata baik.',
            ],
            [
                'rating' => 5,
                'isi' => 'Sistem antrean BPJS Kesehatan sudah terintegrasi digital, jadi tidak perlu menunggu seharian. Dokter spesialisnya sangat komunikatif dan memberikan penjelasan diagnosa secara rinci.',
            ],
            [
                'rating' => 4,
                'isi' => 'Pelayanan medis dan pengambilan obat di bagian farmasi relatif cepat. Ruang tunggunya bersih dan ber-AC dingin. Sedikit catatan hanya area parkir mobil yang agak padat di pagi hari.',
            ],
            [
                'rating' => 5,
                'isi' => 'Sangat puas berobat di sini. Tenaga medis ramah pada lansia, perawat membantu mengarahkan alur poli dengan sabar. Layanan laboratorium juga cepat keluar hasilnya.',
            ],
            [
                'rating' => 4,
                'isi' => 'Poli spesialisnya lengkap dan dokter sangat teliti memeriksa keluhan. Prosedur rujukan BPJS berjalan mulus tanpa kendala administrasi yang rumit.',
            ],
            [
                'rating' => 5,
                'isi' => 'Klinik rawat jalan nyaman dan bersih. Alat-alat pemeriksaan penunjang medis modern. Sangat direkomendasikan bagi warga sekitar yang membutuhkan layanan kesehatan berkualitas.',
            ],
        ];

        $userIndex = 0;
        foreach ($faskesList as $faskes) {
            // Berikan 2 - 3 ulasan per faskes terpilih
            $reviewsToSeed = rand(2, 3);
            for ($i = 0; $i < $reviewsToSeed; $i++) {
                $user = $users[$userIndex % $users->count()];
                $template = $reviewTemplates[($userIndex + $i) % count($reviewTemplates)];

                Komentar::firstOrCreate(
                    [
                        'id_user' => $user->id_user,
                        'id_faskes' => $faskes->id_faskes,
                    ],
                    [
                        'rating' => $template['rating'],
                        'isi_komentar' => $template['isi'],
                        'created_at' => now()->subDays(rand(1, 14)),
                        'updated_at' => now(),
                    ]
                );

                $userIndex++;
            }
        }
    }
}
