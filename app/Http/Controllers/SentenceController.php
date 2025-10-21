<?php

namespace App\Http\Controllers;

use App\Models\Sentence;
use Illuminate\Http\Request;

class SentenceController extends Controller
{
    public function getRandom(): ?Sentence
    {
        return Sentence::query()->inRandomOrder()->first();
    }
}
