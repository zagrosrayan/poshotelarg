<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeAddPos extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-meli-pos',
                'name' => ' پوز ملی',
            ],
        ];
        Type::query()->insert($types);
    }
}
