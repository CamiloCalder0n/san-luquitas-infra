<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de prueba del portal (todos ficticios).
     */
    public function run(): void
    {
        $this->call(NovedadSeeder::class);
    }
}
