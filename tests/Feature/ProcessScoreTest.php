<?php

use App\Models\Sentence;
use App\Models\Score;

it('creates a Score from valid process payload', function () {
    $sentence = Sentence::query()->create([
        'text' => 'abc.',
        'word_count' => 1,
        'character_count' => 4,
    ]);

    $start = 1760991353125;
    $events = [
        ['key' => 'a', 'ts' => $start + 100],
        ['key' => 'b', 'ts' => $start + 300],
        ['key' => 'c', 'ts' => $start + 600],
        ['key' => '.', 'ts' => $start + 900],
    ];

    $payload = [
        'sentence_id' => $sentence->id,
        'name' => 'dav',
        'events' => $events,
        'started_at' => $start,
        'finished_at' => $start + 1000,
        'duration_ms' => 1000,
    ];

    $response = $this->postJson('/process', $payload);

    $response->assertJsonStructure(['score', 'new_high_score']);

    $this->assertDatabaseCount('score', 1);

    $score = Score::query()->first();
    expect($score->sentence_id)->toBe($sentence->id);
    expect($score->submitted)->toBeFalse();
});

it('returns 422 when events do not complete the sentence', function () {
    $sentence = Sentence::query()->create([
        'text' => 'abc',
        'word_count' => 1,
        'character_count' => 3,
    ]);

    $start = 5000;
    $events = [
        ['key' => 'a', 'ts' => $start + 50],
        ['key' => 'b', 'ts' => $start + 150],
    ];

    $payload = [
        'sentence_id' => $sentence->id,
        'name' => 'dav',
        'events' => $events,
        'started_at' => $start,
        'finished_at' => $start + 500,
        'duration_ms' => 500,
    ];

    $response = $this->postJson('/process', $payload);

    $response->assertStatus(422)
        ->assertJson(['message' => 'Unprocessable data']);
});
