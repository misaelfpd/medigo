<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaskesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->seedFasilitas();
    $this->seedSpesialisasi();
    $this->seedFaskes();
    $this->seedFasilitasFaskes();
    $this->seedSpesialisasiFaskes();
  }

  private function seedFasilitas(): void
  {
    $path = database_path('seeders/csv/tabel_fasilitas.csv');
    if (!file_exists($path)) {
      return;
    }

    $records = $this->readCsv($path);
    $data = [];

    foreach ($records as $row) {
      if (!isset($row['id_fasilitas'], $row['nama_fasilitas'])) {
        continue;
      }

      $data[] = [
        'id_fasilitas' => (int) $row['id_fasilitas'],
        'nama_fasilitas' => trim($row['nama_fasilitas']),
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::table('fasilitas')->insertOrIgnore($chunk);
    }
  }

  private function seedSpesialisasi(): void
  {
    $path = database_path('seeders/csv/tabel_spesialisasi.csv');
    if (!file_exists($path)) {
      return;
    }

    $records = $this->readCsv($path);
    $data = [];

    foreach ($records as $row) {
      if (!isset($row['id_spesialisasi'], $row['nama_spesialisasi'])) {
        continue;
      }

<<<<<<< HEAD
      $data[] = [
        'id_spesialisasi' => (int) $row['id_spesialisasi'],
        'nama_spesialisasi' => trim($row['nama_spesialisasi']),
        'created_at' => now(),
        'updated_at' => now(),
      ];
=======
        foreach ($records as $row) {
            if (! isset($row['id_faskes'], $row['nama_faskes'])) {
                continue;
            }

            $lat = ! empty($row['latitude']) ? (float) $row['latitude'] : null;
            $lng = ! empty($row['longitude']) ? (float) $row['longitude'] : null;
            $nama = trim($row['nama_faskes']);
            $kota = trim($row['kota_kabupaten'] ?? '');

            if ($lat !== null && $lng !== null) {
                $linkMaps = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
            } else {
                $linkMaps = 'https://www.google.com/maps/search/?api=1&query='.urlencode($nama.' '.$kota);
            }

            $data[] = [
                'id_faskes' => (int) $row['id_faskes'],
                'nama_faskes' => $nama,
                'jenis_faskes' => trim($row['jenis_faskes'] ?? 'Klinik'),
                'kelas' => ! empty($row['kelas_rs']) ? trim($row['kelas_rs']) : null,
                'provinsi' => ! empty($row['provinsi']) ? trim($row['provinsi']) : 'DKI Jakarta',
                'kota_kabupaten' => $kota ?: 'Jakarta Selatan',
                'alamat_faskes' => trim($row['alamat'] ?? '-'),
                'telepon' => ! empty($row['telepon']) ? trim($row['telepon']) : null,
                'latitude' => $lat,
                'longitude' => $lng,
                'link_maps' => $linkMaps,
                '_support_bpjs' => isset($row['support_bpjs']) ? ((string) $row['support_bpjs'] === '1') : true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($data, 500) as $chunk) {
            DB::table('faskes')->insertOrIgnore($chunk);
        }
>>>>>>> 0a0b2de63d284338b317c7346c35156022ed1b67
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::table('spesialisasi')->insertOrIgnore($chunk);
    }
  }

  private function seedFaskes(): void
  {
    $path = database_path('seeders/csv/tabel_faskes.csv');
    if (!file_exists($path)) {
      return;
    }

    $records = $this->readCsv($path);
    $data = [];

    foreach ($records as $row) {
      if (!isset($row['id_faskes'], $row['nama_faskes'])) {
        continue;
      }

      $lat = !empty($row['latitude']) ? (float) $row['latitude'] : null;
      $lng = !empty($row['longitude']) ? (float) $row['longitude'] : null;
      $nama = trim($row['nama_faskes']);
      $kota = trim($row['kota_kabupaten'] ?? '');

      if ($lat !== null && $lng !== null) {
        $linkMaps = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
      } else {
        $linkMaps =
          'https://www.google.com/maps/search/?api=1&query=' . urlencode($nama . ' ' . $kota);
      }

      $data[] = [
        'id_faskes' => (int) $row['id_faskes'],
        'nama_faskes' => $nama,
        'jenis_faskes' => trim($row['jenis_faskes'] ?? 'Klinik'),
        'kelas' => !empty($row['kelas_rs']) ? trim($row['kelas_rs']) : null,
        'provinsi' => !empty($row['provinsi']) ? trim($row['provinsi']) : 'DKI Jakarta',
        'kota_kabupaten' => $kota ?: 'Jakarta Selatan',
        'alamat_faskes' => trim($row['alamat'] ?? '-'),
        'telepon' => !empty($row['telepon']) ? trim($row['telepon']) : null,
        'latitude' => $lat,
        'longitude' => $lng,
        'link_maps' => $linkMaps,
        '_support_bpjs' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::table('faskes')->insertOrIgnore($chunk);
    }
  }

  private function seedFasilitasFaskes(): void
  {
    $path = database_path('seeders/csv/tabel_faskes_fasilitas.csv');
    if (!file_exists($path)) {
      return;
    }

    $records = $this->readCsv($path);
    $data = [];

    foreach ($records as $row) {
      if (!isset($row['id_faskes'], $row['id_fasilitas'])) {
        continue;
      }

      $data[] = [
        'id_faskes' => (int) $row['id_faskes'],
        'id_fasilitas' => (int) $row['id_fasilitas'],
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::table('fasilitas_faskes')->insertOrIgnore($chunk);
    }
  }

  private function seedSpesialisasiFaskes(): void
  {
    $path = database_path('seeders/csv/tabel_faskes_spesialisasi.csv');
    if (!file_exists($path)) {
      return;
    }

    $records = $this->readCsv($path);
    $data = [];

    foreach ($records as $row) {
      if (!isset($row['id_faskes'], $row['id_spesialisasi'])) {
        continue;
      }

      $data[] = [
        'id_faskes' => (int) $row['id_faskes'],
        'id_spesialisasi' => (int) $row['id_spesialisasi'],
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    foreach (array_chunk($data, 500) as $chunk) {
      DB::table('spesialisasi_faskes')->insertOrIgnore($chunk);
    }
  }

  /**
   * Helper to read a CSV file with headers into associative array.
   *
   * @return array<int, array<string, string>>
   */
  private function readCsv(string $path): array
  {
    $rows = [];
    if (!($handle = fopen($path, 'r'))) {
      return [];
    }

    $header = null;
    while (($line = fgetcsv($handle, 4096, ',')) !== false) {
      if ($header === null) {
        // Bersihkan BOM UTF-8 jika ada pada kolom pertama header
        if (isset($line[0])) {
          $line[0] = str_replace(["\xEF\xBB\xBF", "\u{FEFF}"], '', $line[0]);
          $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', $line[0]);
        }
        $header = array_map(
          fn($col) => trim(str_replace(["\xEF\xBB\xBF", "\u{FEFF}"], '', $col)),
          $line,
        );

        continue;
      }

      if (count($line) === count($header)) {
        $rows[] = array_combine($header, $line);
      }
    }

    fclose($handle);

    return $rows;
  }
}
