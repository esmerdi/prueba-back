<?php

use App\TypeCurrency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('type_currencies')->delete();
        DB::table('type_currencies')->insert([
            'name' => 'billete',
        ]);

        DB::table('type_currencies')->insert([
            'name' => 'moneda',
        ]);

    }
}
