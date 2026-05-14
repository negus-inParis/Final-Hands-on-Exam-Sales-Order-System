<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Jay-ar Quipit',
            'email' => 'jayar.quipit@exam.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Jan Michael H. Apayor',
            'email' => 'janmichael.apayor@exam.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Bato Dela Rosa',
            'email' => 'bato.delarosa@exam.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Johnny Sins',
            'email' => 'johnny.sins@exam.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Gattouzz',
            'email' => 'gattouzz@exam.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            ProductSeeder::class,
        ]);
    }
}
