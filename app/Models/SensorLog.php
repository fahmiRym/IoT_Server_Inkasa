<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'temp',
        'hum',
        'smoke',
        'fire',
        'smoke1',
        'smoke2',
        'smoke3',
        'flame1',
        'flame2',
    ];

    protected $casts = [
        'temp'   => 'float',
        'hum'    => 'float',
        'smoke'  => 'integer',
        'smoke1' => 'integer',
        'smoke2' => 'integer',
        'smoke3' => 'integer',
        'fire'   => 'boolean',
        'flame1' => 'boolean',
        'flame2' => 'boolean',
    ];
}
