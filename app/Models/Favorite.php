<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = [
        'city',
        'country',
        'temperature',
        'condition',
        'icon',
    ];
}
