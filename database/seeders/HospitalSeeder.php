<?php

namespace Database\Seeders;

use App\Models\Hospital;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $hospitals = [
            ['name' => 'De La Salle University Medical Center', 'city' => 'Dasmariñas', 'lat' => 14.3294, 'lng' => 120.9367, 'capacity_status' => 'available'],
            ['name' => 'Dasmariñas City Medical Center', 'city' => 'Dasmariñas', 'lat' => 14.3290, 'lng' => 120.9410, 'capacity_status' => 'limited'],
            ['name' => 'Divine Grace Medical Center', 'city' => 'Dasmariñas', 'lat' => 14.3447, 'lng' => 120.9489, 'capacity_status' => 'available'],
        ];

        foreach ($hospitals as $h) {
            Hospital::updateOrCreate(['name' => $h['name']], $h + [
                'facility_type' => 'hospital', 'province' => 'Cavite', 'is_er_open' => true, 'is_active' => true,
            ]);
        }
    }
}
