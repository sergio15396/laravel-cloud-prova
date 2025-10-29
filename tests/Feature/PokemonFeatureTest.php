<?php

declare(strict_types=1);

use App\Models\Pokemon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('imports first 3 pokemons from API and saves image blobs', function () {
    Http::fake([
        'pokeapi.co/api/v2/pokemon*' => function ($request) {
            if (str_contains((string) $request->url(), 'limit=')) {
                return Http::response([
                    'results' => [
                        ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                        ['name' => 'ivysaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/2/'],
                        ['name' => 'venusaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/3/'],
                    ],
                ], 200);
            }

            $id = (int) trim(parse_url((string) $request->url(), PHP_URL_PATH) ?? '', '/');

            return Http::response([
                'id' => $id,
                'name' => match ($id) {
                    1 => 'bulbasaur',
                    2 => 'ivysaur',
                    3 => 'venusaur',
                    default => 'unknown',
                },
                'species' => [
                    'name' => match ($id) {
                        1 => 'bulbasaur', 2 => 'ivysaur', 3 => 'venusaur', default => 'unknown'
                    },
                    'url' => 'https://pokeapi.co/api/v2/pokemon-species/'.$id.'/',
                ],
                'types' => [
                    ['slot' => 1, 'type' => ['name' => 'grass']],
                    ['slot' => 2, 'type' => ['name' => 'poison']],
                ],
                'height' => 7,
                'weight' => 69,
                'base_experience' => 64,
                'sprites' => [
                    'front_default' => 'https://img.example/pokemon-'.$id.'.png',
                ],
            ], 200);
        },
        'pokeapi.co/api/v2/pokemon-species/*' => Http::response([
            'evolution_chain' => [
                'url' => 'https://pokeapi.co/api/v2/evolution-chain/1/',
            ],
        ], 200),
        'pokeapi.co/api/v2/evolution-chain/*' => Http::response([
            'chain' => [
                'species' => ['name' => 'bulbasaur'],
                'evolves_to' => [[
                    'species' => ['name' => 'ivysaur'],
                    'evolves_to' => [[
                        'species' => ['name' => 'venusaur'],
                        'evolves_to' => [],
                    ]],
                ]],
            ],
        ], 200),
        'img.example/*' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png']),
    ]);

    $this->artisan('app:import-pokemons --limit=3 --images')
        ->assertExitCode(0);

    expect(Pokemon::count())->toBe(3);
    expect(Pokemon::whereNotNull('image_blob')->count())->toBe(3);
});


