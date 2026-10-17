<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'super_admin' => 'City-wide system administrator',
            'barangay_admin' => 'Administrator assigned to one barangay',
            'citizen' => 'Registered disaster information contributor',
        ] as $name => $description) {
            Role::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
