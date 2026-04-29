<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = [
            "name" => "admin",
            "email" => "admin@gmail.com",
            "password" => Hash::make("admin123"),
            "email_verified_at" => Carbon::now(),
        ];

        $admin = User::create($admin);
        $admin->assignRole("admin");

        $adminVet = [
            "name" => "adminVet",
            "email" => "adminvet@gmail.com",
            "password" => Hash::make("adminvet123"),
            "email_verified_at" => Carbon::now(),
        ];

        $adminVet = User::create($adminVet);
        $adminVet->assignRole("admin_vet");

        $client = [
            "name" => "client",
            "email" => "client@gmail.com",
            "password" => Hash::make("client123"),
            "email_verified_at" => Carbon::now(),
        ];

        $client = User::create($client);
        $client->assignRole("client");

        $clientVet = [
            "name" => "clientVet",
            "email" => "clientvet@gmail.com",
            "password" => Hash::make("clientvet123"),
            "email_verified_at" => Carbon::now(),
        ];

        $clientVet = User::create($clientVet);
        $clientVet->assignRole("client_vet");
    }
}
