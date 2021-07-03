<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Denomination extends Model
{
    protected $fillable = ['type_currency_id', 'value'];
    protected $guarded = ['id'];

    public function currency()
    {
        return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
    }
}
