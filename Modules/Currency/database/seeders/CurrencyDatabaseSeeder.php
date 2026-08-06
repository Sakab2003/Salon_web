<?php

namespace Modules\Currency\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Currency\Models\Currency;

class CurrencyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $data = [
            [
                'currency_name' => 'Franc CFA',
                'currency_symbol' => 'FCFA',
                'currency_code' => 'XOF',
                'currency_position' => 'right_with_space',
                'no_of_decimal' => 0,
                'thousand_separator' => ' ',
                'decimal_separator' => ',',
                'is_primary' => 1,
            ],
        ];

        foreach ($data as $key => $value) {
            Currency::create($value);
        }

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
