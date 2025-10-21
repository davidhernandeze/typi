<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SentenceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $records = [];

        for ($i = 0; $i < 20; $i++) {
            $wordTotal = $faker->numberBetween(100, 200);
            $text = $faker->sentence($wordTotal, true);

            $wordCount = count(preg_split('/\s+/', trim($text)));
            $charCount = mb_strlen($text);

            $records[] = [
                'text' => $text,
                'word_count' => $wordCount,
                'character_count' => $charCount,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('sentences')->insert($records);
    }
}
