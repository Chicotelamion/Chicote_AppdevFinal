<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    protected $fillable = [
        'city',
        'country',
        'temperature',
        'condition',
        'icon',
    ];
}
