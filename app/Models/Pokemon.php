<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    use HasFactory;

    protected $table = 'pokemons';

    protected $fillable = [
        'api_id',
        'name',
        'image_url',
        'image_path',
        'image_blob',
        'image_mime',
        'primary_type',
        'secondary_type',
        'height',
        'weight',
        'base_experience',
        'evolution_stage',
        'species_name',
    ];
}


