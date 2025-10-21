<?php

use App\Models\Score;
use App\Models\Sentence;
use Illuminate\Support\Facades\Cache;

it('clears leaderboard cache when a new submitted score enters top 10', function () {
    $sentence = Sentence::query()->create([
        'text' => 'hello world',
        'word_count' => 2,
        'character_count' => 11,
    ]);

    for ($i = 10; $i >= 1; $i--) {
        Score::query()->create([
            'sentence_id' => $sentence->id,
            'session_id' => 'seed-session-'.$i,
            'name' => 'Seed '.$i,
            'words_per_minute' => $i * 10.0,
            'accuracy_percentage' => 100,
            'time_taken' => 1000 + $i,
            'submitted' => true,
        ]);
    }

    $toSubmit = Score::query()->create([
        'sentence_id' => $sentence->id,
        'session_id' => 'player-session',
        'name' => '',
        'words_per_minute' => 15.0,
        'accuracy_percentage' => 100,
        'time_taken' => 900,
        'submitted' => false,
    ]);

    Cache::spy();

    Score::createWithCacheCheck($toSubmit, 'Alice');

    $fresh = $toSubmit->fresh();
    expect($fresh->submitted)->toBeTrue();
    expect($fresh->name)->toBe('Alice');

    Cache::shouldHaveReceived('forget')
        ->once()
        ->with('leaderboard_top10');
});
