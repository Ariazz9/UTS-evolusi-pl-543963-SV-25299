<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password123'),
            ]
        );

        $materials = [
            [
                'name' => 'Semen Tiga Roda 50kg',
                'category' => 'Semen & Mortar',
                'unit' => 'Sak',
                'price' => 68000,
                'stock' => 150,
                'description' => 'Semen Portland tipe 1 standar SNI untuk pekerjaan struktur beton dan plesteran dinding.',
            ],
            [
                'name' => 'Besi Beton Polos 10mm SNI',
                'category' => 'Besi & Baja',
                'unit' => 'Batang',
                'price' => 75000,
                'stock' => 200,
                'description' => 'Besi tulangan konstruksi standar SNI panjang 12 meter toleransi resmi.',
            ],
            [
                'name' => 'Cat Tembok Dulux Catylac Putih 5kg',
                'category' => 'Cat & Pelapis',
                'unit' => 'Pail',
                'price' => 145000,
                'stock' => 45,
                'description' => 'Cat dinding interior bermutu tinggi dengan teknologi Chroma Brite anti pudar.',
            ],
            [
                'name' => 'Pipa PVC Rucika AW 1/2 Inch',
                'category' => 'Pipa & Plumbing',
                'unit' => 'Batang',
                'price' => 32000,
                'stock' => 90,
                'description' => 'Pipa paralon saluran air bersih bertekanan panjang 4 meter kualitas JIS.',
            ],
            [
                'name' => 'Keramik Lantai Roman 40x40 Bianco',
                'category' => 'Keramik & Granit',
                'unit' => 'Dus',
                'price' => 88000,
                'stock' => 65,
                'description' => 'Keramik glazed tile motif marmer putih glossy per dus isi 6 keping (0.96 m2).',
            ],
            [
                'name' => 'Triplek Meranti 12mm 4x8',
                'category' => 'Kayu & Triplek',
                'unit' => 'Lembar',
                'price' => 165000,
                'stock' => 40,
                'description' => 'Plywood kayu lapis meranti tebal 12mm ukuran 122x244cm untuk bekisting dan plafon.',
            ],
        ];

        foreach ($materials as $item) {
            \App\Models\Material::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
