<?php /** @var \Illuminate\Support\Collection|\App\Models\Pokemon[] $pokemons */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokémon</title>
    <link rel="icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b1020; }
    </style>
</head>
<body class="text-white">
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-center mb-12">
        <div class="flex items-center gap-3">
            <img src="https://upload.wikimedia.org/wikipedia/commons/9/98/International_Pok%C3%A9mon_logo.svg" alt="Pokémon" class="h-60 w-auto">
        </div>
    </div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <span class="text-white/60 text-sm">Evolution:</span>
            <?php $baseUrl = route('pokemons.index'); $q = request()->query(); ?>
            <?php $link = function(array $changes) use ($baseUrl, $q) {
                $query = array_merge($q, $changes);
                // Reset pagination whenever filters change
                unset($query['page']);
                foreach ($query as $k => $v) { if ($v === null) unset($query[$k]); }
                $qs = http_build_query($query);
                return $qs ? $baseUrl.'?'.$qs : $baseUrl;
            }; ?>
            <a class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-sm <?= request()->has('evolution')? '': 'ring-1 ring-white/40' ?>" href="<?= e($link(['evolution' => null])) ?>">All</a>
            <a class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-sm <?= request('evolution')==='1'?'ring-1 ring-white/40':'' ?>" href="<?= e($link(['evolution' => 1])) ?>">1</a>
            <a class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-sm <?= request('evolution')==='2'?'ring-1 ring-white/40':'' ?>" href="<?= e($link(['evolution' => 2])) ?>">2</a>
            <a class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-sm <?= request('evolution')==='3'?'ring-1 ring-white/40':'' ?>" href="<?= e($link(['evolution' => 3])) ?>">3</a>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-white/60 text-sm">Type:</span>
            <a class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-sm <?= request()->has('type')? '': 'ring-1 ring-white/40' ?>" href="<?= e($link(['type' => null])) ?>">All</a>
            <?php foreach (['fire','water','grass','electric','dragon','fairy','normal'] as $type): ?>
            <a class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-sm capitalize <?= request('type')===$type?'ring-1 ring-white/40':'' ?>" href="<?= e($link(['type' => $type])) ?>"><?= e($type) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(route('pokemons.index')) ?>" class="px-2 py-1 rounded bg-blue-600 hover:bg-blue-500 text-sm">Clear filters</a>
        </div>
        <div class="ml-auto">
            <a href="/" class="text-blue-300 hover:text-blue-200">Back to Home</a>
        </div>
    </div>
    

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($pokemons as $pokemon): ?>
            <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                <div class="mb-3">
                    <h2 class="font-semibold capitalize">#<?= e($pokemon->api_id) ?> · <?= e($pokemon->name) ?></h2>
                    <div class="text-xs text-white/60 capitalize">
                        <?php if ($pokemon->primary_type): ?>
                            Type: <?= e($pokemon->primary_type) ?><?php if ($pokemon->secondary_type): ?> / <?= e($pokemon->secondary_type) ?><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="aspect-square bg-white/5 rounded flex items-center justify-center overflow-hidden">
                    <?php
                        $src = '';
                        if ($pokemon->image_blob && $pokemon->image_mime) {
                            $src = 'data:'.e($pokemon->image_mime).';base64,'.base64_encode($pokemon->image_blob);
                        } elseif ($pokemon->image_url) {
                            $src = $pokemon->image_url;
                        }
                    ?>
                    <?php if ($src): ?>
                        <img src="<?= $src ?>" alt="<?= e($pokemon->name) ?>" class="w-40 h-40 object-contain">
                    <?php else: ?>
                        <span class="text-white/40 text-sm">No image</span>
                    <?php endif; ?>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm text-white/70">
                    <div><dt class="text-white/50">Height</dt><dd><?= e($pokemon->height ?? '—') ?></dd></div>
                    <div><dt class="text-white/50">Weight</dt><dd><?= e($pokemon->weight ?? '—') ?></dd></div>
                    <div><dt class="text-white/50">Base XP</dt><dd><?= e($pokemon->base_experience ?? '—') ?></dd></div>
                    <div><dt class="text-white/50">Evolution</dt><dd><?= e($pokemon->evolution_stage ?? '—') ?></dd></div>
                </dl>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pokemons->isEmpty()): ?>
        <p class="text-white/70 mt-8">No Pokémon found. Import them first.</p>
    <?php endif; ?>
    <div class="mt-8">
        <?= $pokemons->onEachSide(1)->links() ?>
    </div>

    <div class="mt-8 text-sm text-white/60">
        Data from PokeAPI (<a class="underline" href="https://pokeapi.co/" target="_blank" rel="noreferrer">https://pokeapi.co/</a>)
    </div>
</div>
</body>
</html>


