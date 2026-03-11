<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Potong Rambut Reguler', 'price' => 30000, 'duration' => 30],
            ['name' => 'Potong Rambut Kids', 'price' => 25000, 'duration' => 25],
            ['name' => 'Cukur Jenggot', 'price' => 15000, 'duration' => 15],
            ['name' => 'Catok Rambut', 'price' => 20000, 'duration' => 20],
            ['name' => 'Creambath', 'price' => 50000, 'duration' => 45],
            ['name' => 'Hair Tattoo', 'price' => 35000, 'duration' => 30],
            ['name' => 'Komplit (Potong + Cukur)', 'price' => 40000, 'duration' => 45],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}