<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    /** @use HasFactory<\Database\Factories\TripFactory> */
    use HasFactory;

    protected $guarded = [];


    function to_station() {
        return $this->belongsTo(Station::class, 'to', 'code');
    }
    function from_station() {
        return $this->belongsTo(Station::class, 'from', 'code');
    }
}
