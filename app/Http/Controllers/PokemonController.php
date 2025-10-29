<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PokemonController extends Controller
{
    public function index(Request $request): View
    {
        $query = Pokemon::query();

        if ($request->has('evolution')) {
            $raw = $request->query('evolution');
            if (is_numeric($raw)) {
                $evolution = (int) $raw;
                $query->where('evolution_stage', $evolution);
            }
        }

        if ($type = $request->string('type')->trim()->lower()) {
            if ($type->toString() !== '' && $type->toString() !== 'all') {
            $query->where(function ($q) use ($type) {
                $q->where('primary_type', $type)
                    ->orWhere('secondary_type', $type);
            });
            }
        }

        // Show only Generation 1 (first 151)
        $query->where('api_id', '<=', 151);

        $pokemons = $query->orderBy('api_id')->paginate(20)->withQueryString();

        return view('pokemons.index', compact('pokemons'));
    }

}


