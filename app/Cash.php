<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    protected $fillable = ['quantity', 'denomination_id'];
    protected $guarded = ['id'];
    protected $table = 'cash';

    public function denomination()
    {
        return $this->belongsTo(Denomination::class);
    }
}
