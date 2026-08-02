<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        // $this->call(CmsSeeder::class);
        // $this->call(ContainerFieldSeeder::class);
        // $this->call(MenuSeeder::class);
        // $this->call(\Database\Seeders\Tables\MenusSeeder::class);
        $this->call(WmsSeeder::class);
    }
}
