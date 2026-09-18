<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NocUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Fahmi', 'username' => 'fahmi'],
            ['name' => 'Jalil', 'username' => 'jalil'],
            ['name' => 'Aldy',  'username' => 'aldy'],
            ['name' => 'Adit',  'username' => 'adit'],
            ['name' => 'Rizky', 'username' => 'rizky'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'name'     => $u['name'],
                    'email'    => $u['username'] . '@noc.inkalum.com',
                    'password' => Hash::make('bismillah'),
                ]
            );
        }
    }
}
