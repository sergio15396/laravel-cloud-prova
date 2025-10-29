<?php

namespace App\Console\Commands;

use App\Models\Pokemon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPokemons extends Command
{
    protected $signature = 'app:import-pokemons {--all} {--limit=100} {--images} {--evolution}';

    protected $description = 'Import Pokémons from PokeAPI into the database (optionally all) and save images.';

    public function handle(): int
    {
        $importAll = (bool) $this->option('all');
        $limit = (int) $this->option('limit');
        $downloadImages = (bool) $this->option('images');

        if ($importAll) {
            $this->info('Fetching ALL Pokémon from PokeAPI (this may take a while)...');
        } else {
            $this->info("Fetching first {$limit} Pokémon from PokeAPI...");
        }

        $endpoint = 'https://pokeapi.co/api/v2/pokemon';
        // Gen 1 only: first 151
        $next = $importAll ? $endpoint.'?limit=151&offset=0' : $endpoint.'?limit='.$limit.'&offset=0';

        $totalImported = 0;
        // Enable evolution by default for better filtering; can be disabled by omitting the option in future if needed
        $evolutionEnabled = (bool) ($this->option('evolution') ?: true);
        $evoCache = [];

        while ($next) {
            $listResponse = Http::get($next);
            if (!$listResponse->successful()) {
                $this->error('Failed to fetch Pokémon list: '.$next);
                break;
            }

            $results = $listResponse->json('results') ?? [];
            $bar = $this->output->createProgressBar(count($results));
            $bar->start();

            // Fetch details concurrently for this page
            $responses = Http::pool(function ($pool) use ($results) {
                foreach ($results as $i => $entry) {
                    $pool->as('d'.$i)->get($entry['url']);
                }
            });

            foreach ($results as $i => $entry) {
                $detailResponse = $responses['d'.$i] ?? null;
                if (!$detailResponse || !$detailResponse->successful()) {
                    $bar->advance();
                    continue;
                }

                $detail = $detailResponse->json();
                $apiId = (int) ($detail['id'] ?? 0);
                $name = (string) ($detail['name'] ?? '');
                $imageUrl = (string) ($detail['sprites']['other']['official-artwork']['front_default']
                    ?? $detail['sprites']['front_default']
                    ?? '');
                $types = $detail['types'] ?? [];
                $primaryType = isset($types[0]['type']['name']) ? (string) $types[0]['type']['name'] : null;
                $secondaryType = isset($types[1]['type']['name']) ? (string) $types[1]['type']['name'] : null;
                $height = isset($detail['height']) ? (int) $detail['height'] : null;
                $weight = isset($detail['weight']) ? (int) $detail['weight'] : null;
                $baseExp = isset($detail['base_experience']) ? (int) $detail['base_experience'] : null;
                $speciesName = (string) ($detail['species']['name'] ?? '');

                // Evolution stage via species -> evolution chain traversal
                $evolutionStage = null;
                if ($evolutionEnabled && !empty($detail['species']['url'])) {
                    $speciesUrl = (string) $detail['species']['url'];
                    $speciesResponse = Http::get($speciesUrl);
                    if ($speciesResponse->successful()) {
                        $evoUrl = (string) ($speciesResponse->json('evolution_chain.url') ?? '');
                        if ($evoUrl !== '') {
                            if (!array_key_exists($evoUrl, $evoCache)) {
                                $evoResp = Http::get($evoUrl);
                                $evoCache[$evoUrl] = $evoResp->successful() ? $evoResp->json('chain') : null;
                            }
                            $chain = $evoCache[$evoUrl];
                            if ($chain) {
                                $evolutionStage = self::findEvolutionStageFor($chain, $name, 1);
                            }
                        }
                    }
                }

                if ($apiId <= 0 || $name === '' || $imageUrl === '') {
                    $bar->advance();
                    continue;
                }

                $pokemon = Pokemon::query()->updateOrCreate(
                    ['api_id' => $apiId],
                    [
                        'name' => $name,
                        'image_url' => $imageUrl,
                        'primary_type' => $primaryType,
                        'secondary_type' => $secondaryType,
                        'height' => $height,
                        'weight' => $weight,
                        'base_experience' => $baseExp,
                        'evolution_stage' => $evolutionStage,
                        'species_name' => $speciesName,
                    ]
                );

                if ($downloadImages && $imageUrl !== '') {
                    $img = Http::get($imageUrl);
                    if ($img->successful()) {
                        $mime = $img->header('Content-Type', 'image/png');
                        $pokemon->update([
                            'image_blob' => $img->body(),
                            'image_mime' => $mime,
                        ]);
                    }
                }

                $totalImported++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // For Gen 1 only, do not follow next pages when --all
            if ($importAll) {
                break;
            }

            $next = $listResponse->json('next');
        }

        $this->info('Done. Imported/updated: '.$totalImported);

        return self::SUCCESS;
    }

    private static function findEvolutionStageFor(?array $chain, string $targetName, int $currentStage): ?int
    {
        if (!$chain) {
            return null;
        }

        $name = (string) ($chain['species']['name'] ?? '');
        if ($name === $targetName) {
            return $currentStage;
        }

        $evolvesTo = $chain['evolves_to'] ?? [];
        foreach ($evolvesTo as $child) {
            $stage = self::findEvolutionStageFor($child, $targetName, $currentStage + 1);
            if ($stage !== null) {
                return $stage;
            }
        }

        return null;
    }
}


