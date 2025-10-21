<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Score extends Model
{
    protected $table = 'score';

    protected $fillable = [
        'sentence_id',
        'session_id',
        'name',
        'words_per_minute',
        'accuracy_percentage',
        'time_taken',
        'submitted',
    ];

    protected $casts = [
        'words_per_minute' => 'float',
        'accuracy_percentage' => 'integer',
        'time_taken' => 'integer',
        'submitted' => 'boolean',
    ];

    public static function getLastSubmittedScoreForSession(string $sessionId): ?Score
    {
        return self::query()
            ->where('session_id', $sessionId)
            ->where('submitted', true)
            ->orderByDesc('created_at')
            ->first();
    }

    public static function getLastUnsubmittedScoreForSession(string $sessionId): ?Score
    {
        return self::query()
            ->where('session_id', $sessionId)
            ->where('submitted', false)
            ->orderByDesc('created_at')
            ->first();
    }

    #[Scope]
    protected function topTen(Builder $query): void
    {
        $query->where('submitted', true)
            ->orderByDesc('words_per_minute')
            ->orderBy('time_taken')
            ->limit(10);
    }

    public static function createWithCacheCheck($scoreToSubmit, $name): void
    {
        if (!$scoreToSubmit) return;

        $scoreToSubmit->submitted = true;
        $scoreToSubmit->name = $name;
        $scoreToSubmit->save();

        $topTen = Score::query()->topTen()->pluck('words_per_minute');

        if ($topTen->count() < 10) {
            Cache::forget('leaderboard_top10');
            return;
        }

        $lastTopValue = $topTen->last();
        if ($scoreToSubmit->words_per_minute >= $lastTopValue) {
            logger()->info('Score made it to top 10 leaderboard, clearing cache.');
            Cache::forget('leaderboard_top10');
        }
    }
}
