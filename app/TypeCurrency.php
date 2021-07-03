<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TypeCurrency extends Model
{
    protected $fillable = ['name'];
    protected $guarded = ['id'];
    protected $table = 'type_currencies';
}
