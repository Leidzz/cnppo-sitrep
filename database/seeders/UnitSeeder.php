<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Basud MPS', 'code' => 'BASUD-MPS'],
            ['name' => 'Capalonga MPS', 'code' => 'CAPALONGA-MPS'],
            ['name' => 'Daet MPS', 'code' => 'DAET-MPS'],
            ['name' => 'San Lorenzo Ruiz MPS', 'code' => 'SLRUIZ-MPS'],
            ['name' => 'Jose Panganiban MPS', 'code' => 'JPANGANIBAN-MPS'],
            ['name' => 'Labo MPS', 'code' => 'LABO-MPS'],
            ['name' => 'Mercedes MPS', 'code' => 'MERCEDES-MPS'],
            ['name' => 'Paracale MPS', 'code' => 'PARACALE-MPS'],
            ['name' => 'San Vicente MPS', 'code' => 'SVICENTE-MPS'],
            ['name' => 'Santa Elena MPS', 'code' => 'SELENA-MPS'],
            ['name' => 'Talisay MPS', 'code' => 'TALISAY-MPS'],
            ['name' => 'Vinzons MPS', 'code' => 'VINZONS-MPS'],
            ['name' => '1st Provincial Mobile Force Company', 'code' => '1ST-PMFC'],
            ['name' => '2nd Provincial Mobile Force Company', 'code' => '2ND-PMFC'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
