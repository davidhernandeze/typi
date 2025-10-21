<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sentence extends Model
{
    protected  $fillable = ['text', 'word_count', 'character_count'];

    public static function getRandom(): ?Sentence
    {
        return Sentence::query()->inRandomOrder()->first();
    }
}
