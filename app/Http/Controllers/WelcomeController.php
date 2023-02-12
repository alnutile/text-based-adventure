<?php

namespace App\Http\Controllers;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Events\ModerationFailed;
use Alnutile\LaravelChatgpt\Facades\ModerationClientFacade;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class WelcomeController extends Controller
{

    public function home()
    {
        $results = null;

        $session_id = request()->session()->token();

        if($genre = request()->genre) {
            $question = sprintf("I would like to have you start a text adventure game in the genre of %s can you please give me the start of the story",
                $genre);

            /** @var ResponseDto $results */
            $results = TextClientFacade::setTemperature(0.7)
                ->text($question);

            $results = optional(Arr::first($results->messages))->message;

            $results = str($results)->stripTags()->toString();

            Cache::set($session_id, [
                "genre" => $genre,
                "story" => [],
                "player" => [],
            ]);
        }

        return Inertia::render('Welcome', [
            'session_id' => $session_id,
            'genre' => $genre,
            'story' => $results
        ]);
    }

    public function player()
    {

        $validated = request()->validate([
            'play' => 'required|max:10000|',
        ]);

        $session_id = request()->session()->token();

        $previous = Cache::get($session_id);

        $validatedPlay = str($validated['play'])->trim()->toString();

        $moderationOk = ModerationClientFacade::checkOk(request()->search);

        if ($moderationOk == false) {
            ModerationFailed::dispatch(request());
        } else {
            /** @var ResponseDto $results */
            /** @phpstan-ignore-next-line */
            $results = TextClientFacade::setTemperature(0.7)
                ->addPrefix('Can you give me a TL;DR based on the content that follows:')
                ->text($validatedPlay);

            $results = optional(Arr::first($results->messages))->message;

            $results = str($results)->stripTags()->toString();

            $genre = data_get($previous, 'genre');
            $story = data_get($previous, 'story', []);
            $play = data_get($previous, 'player', []);

            $story[] = $results;
            $play[] = $validatedPlay;

            Cache::set($session_id, [
                "genre" => $genre,
                "story" => $story,
                "player" => $play,
            ]);

            $previous = Cache::get($session_id);
        }

        return response()->json([
            'next_story_line' => $results,
            'previous' => $previous
        ], 200);
    }
}
