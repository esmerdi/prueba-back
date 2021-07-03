<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DenominationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('denominations')->delete();
        DB::table('denominations')->insert([
            ['type_currency_id' => 1, 'value' => 100000], 
            ['type_currency_id' => 1, 'value' => 50000],
            ['type_currency_id' => 1, 'value' => 20000],
            ['type_currency_id' => 1, 'value' => 10000],
            ['type_currency_id' => 1, 'value' => 5000],
            ['type_currency_id' => 1, 'value' => 2000],
            ['type_currency_id' => 1, 'value' => 1000],
            ['type_currency_id' => 2, 'value' => 500],
            ['type_currency_id' => 2, 'value' => 200],
            ['type_currency_id' => 2, 'value' => 100],
            ['type_currency_id' => 2, 'value' => 50],
        ]);

    }
}
