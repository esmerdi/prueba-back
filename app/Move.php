<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    protected $fillable = ['move', 'denomination_id'];
    protected $guarded = ['id'];

    public function denomination()
    {
        return $this->belongsTo(Denomination::class);
    }

}
