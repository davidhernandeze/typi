<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('score', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sentence_id')->constrained('sentences');
            $table->string('session_id', 255);
            $table->string('name', 255);
            $table->decimal('words_per_minute', 8, 2);
            $table->unsignedTinyInteger('accuracy_percentage');
            $table->unsignedInteger('time_taken'); // milliseconds
            $table->boolean('submitted')->default(false);
            $table->index('words_per_minute');
            $table->index('submitted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score');
    }
};
