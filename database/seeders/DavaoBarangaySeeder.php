<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class DavaoBarangaySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Poblacion', 'district' => '1st District'],
            ['name' => 'Matina Crossing', 'district' => '1st District'],
            ['name' => 'Talomo', 'district' => '1st District'],
            ['name' => 'Bangkal', 'district' => '1st District'],
            ['name' => 'Buhangin', 'district' => '2nd District'],
            ['name' => 'Sasa', 'district' => '2nd District'],
            ['name' => 'Bucana', 'district' => '1st District'],
            ['name' => 'Marilog', 'district' => '3rd District'],
            ['name' => 'Agdao', 'district' => '1st District'],
            ['name' => 'Toril', 'district' => '3rd District'],
        ] as $barangay) {
            Barangay::updateOrCreate(['name' => $barangay['name']], $barangay);
        }
    }
}
