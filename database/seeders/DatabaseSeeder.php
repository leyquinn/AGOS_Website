<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, DavaoBarangaySeeder::class]);

        User::updateOrCreate(
            ['email' => 'superadmin@agos.davao.gov.ph'],
            [
                'role_id' => Role::where('name', 'super_admin')->value('id'),
                'first_name' => 'AGOS',
                'last_name' => 'Super Admin',
                'phone_number' => '+639000000000',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
