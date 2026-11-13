<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Frontend tidak menyimpan data sendiri (user, faskes, forum ada di backend).
     * Database frontend hanya dipakai untuk session, cache, dan queue.
     */
    public function run(): void
    {
        //
    }
}
