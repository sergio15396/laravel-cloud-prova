<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->binary('image_blob')->nullable()->after('image_url');
            $table->string('image_mime')->nullable()->after('image_blob');
            $table->string('primary_type')->nullable()->after('image_mime');
            $table->string('secondary_type')->nullable()->after('primary_type');
            $table->unsignedInteger('height')->nullable()->after('secondary_type');
            $table->unsignedInteger('weight')->nullable()->after('height');
            $table->unsignedInteger('base_experience')->nullable()->after('weight');
            $table->unsignedTinyInteger('evolution_stage')->nullable()->after('base_experience');
            $table->string('species_name')->nullable()->after('evolution_stage');
        });
    }

    public function down(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->dropColumn([
                'image_blob',
                'image_mime',
                'primary_type',
                'secondary_type',
                'height',
                'weight',
                'base_experience',
                'evolution_stage',
                'species_name',
            ]);
        });
    }
};


