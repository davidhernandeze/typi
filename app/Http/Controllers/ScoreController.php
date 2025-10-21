<?php

namespace App\Http\Controllers;

use App\Models\Score;
use App\Models\Sentence;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ScoreController extends Controller
{
    public function process(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'sentence_id' => ['required', 'integer', 'exists:sentences,id'],
            'events' => ['required', 'array', 'min:1'],
            'events.*.ts' => ['required', 'integer'],
            'events.*.key' => ['sometimes'],
            'started_at' => ['required', 'integer'],
            'finished_at' => ['required', 'integer', 'gte:started_at'],
            'duration_ms' => ['required', 'integer', 'gte:0'],
        ]);

        $sentence = Sentence::query()->findOrFail($data['sentence_id']);

        $events = $data['events'];

        $errors = 0;
        $charIndex = 0;
        $eventIndex = 0;
        $eventsCount = count($events);
        $chars = preg_split('//u', $sentence->text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        while ($charIndex < count($chars)) {
            if ($eventIndex >= $eventsCount) {
                return response()->json(['message' => 'Unprocessable data'], 422);
            }

            $event = $events[$eventIndex];

            $key = $event['key'] ?? ' ';

            if (strlen($key) > 1) {
                $eventIndex++;
                continue;
            }

            $expected = $chars[$charIndex];

            if ($key === $expected) {
                $charIndex++;
                $eventIndex++;
                continue;
            }

            $errors++;
            $eventIndex++;
        }

        $timeTakenMs = $data['finished_at'] - $data['started_at'];

        $totalChars = count($chars);
        $correctChars = max(0, $totalChars - $errors);
        $accuracy = (int)round(($correctChars / $totalChars) * 100);

        $minutes = $timeTakenMs / 60000.0;
        $grossWPM = $minutes > 0 ? ($totalChars / 5.0) / $minutes : 0.0;

        $score = Score::create([
            'sentence_id' => $sentence->id,
            'session_id' => $request->session()->getId(),
            'name' => '',
            'words_per_minute' => round($grossWPM, 2),
            'accuracy_percentage' => $accuracy,
            'time_taken' => $timeTakenMs,
            'submitted' => false,
        ]);

        $newHighScore = true;
        $previousScore = Score::getLastSubmittedScoreForSession($request->session()->getId());
        if ($previousScore) {
            if ($previousScore->words_per_minute >= $score->words_per_minute) {
                $newHighScore = false;
            }
        }

        return response()
            ->json([
                'score' => $score->only(['id', 'words_per_minute', 'accuracy_percentage', 'time_taken']),
                'new_high_score' => $newHighScore,
            ]);
    }

    public function leaderboard(): \Illuminate\Http\JsonResponse
    {
        $cacheKey = 'leaderboard_top10';
        $top = Cache::rememberForever($cacheKey, function () {
            return Score::query()
                ->topTen()
                ->get(['name', 'words_per_minute'])
                ->map(function ($row) {
                    return [
                        'name' => $row->name ?? 'Anonymous',
                        'score' => (float)$row->words_per_minute,
                    ];
                });
        });

        return response()->json(['data' => $top]);
    }

    public function store(Request $request): Response
    {
        $sessionId = $request->session()->getId();

        if (!$sessionId) {
            return response()->noContent();
        }

        $previousScore = Score::getLastSubmittedScoreForSession($sessionId);
        if ($previousScore) {
            $previousScore->submitted = false;
            $previousScore->save();
        }

        $scoreToSubmit = Score::getLastUnsubmittedScoreForSession($sessionId);
        Score::createWithCacheCheck($scoreToSubmit, $request->input('name'));

        return response('ok');
    }
}
