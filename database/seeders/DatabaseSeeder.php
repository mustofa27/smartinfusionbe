<?php

namespace Database\Seeders;

use App\Models\Organization;
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
        $organization = Organization::firstOrCreate(
            ['code' => 'POLTERA'],
            [
                'name' => 'Poltera',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'email' => 'mustofaahmad@poltera.ac.id',
            ],
            [
                'name' => 'Mustofa Ahmad',
                'password' => Hash::make('password'),
                'role' => 'super-admin',
                'phone' => null,
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'email' => 'infokom@poltera.ac.id',
            ],
            [
                'name' => 'Infokom Poltera',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => null,
                'is_active' => true,
            ],
        );
    }
}
