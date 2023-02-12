<?php

namespace App\Http\Controllers;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Events\ModerationFailed;
use Alnutile\LaravelChatgpt\Facades\ModerationClientFacade;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function home()
    {
        $results = null;

        $session_id = request()->session()->token();

        if ($genre = request()->genre) {
            $question = sprintf('I would like to have you start a text adventure game in the genre of %s can you please give me the start of the story so the player can choose the next step',
                $genre);

            /** @var ResponseDto $results */
            /** @phpstan-ignore-next-line */
            $results = TextClientFacade::setTemperature(0.7)
                ->text($question);

            $results = optional(Arr::first($results->messages))->message;

            $results = str($results)->whenStartsWith('?', function ($item) {
                return $item->replaceFirst('?', '');
            })->stripTags()->toString();

            Cache::set($session_id, [
                'genre' => $genre,
                'story' => [
                    $results,
                ],
                'player' => [],
                'zipped' => [
                    $this->prefixStory($results),
                ],
            ]);
        }

        return Inertia::render('Welcome', [
            'session_id' => $session_id,
            'genre' => $genre,
            'story' => $results,
        ]);
    }

    protected function prefixStory($story): string
    {
        return str($story)->prepend('Story Section:')->toString();
    }

    protected function prefixPlayer($story): string
    {
        return str($story)->prepend('Player Chose:')->toString();
    }

    public function startOver()
    {
        $session_id = request()->session()->token();
        Cache::forget($session_id);

        return redirect()->route('home');
    }

    public function player()
    {
        $validated = request()->validate([
            'play' => 'required',
        ]);

        try {
            $results = null;
            $session_id = request()->session()->token();
            $previous = Cache::get($session_id);
            $validatedPlay = str($validated['play'])
                ->trim()
                ->toString();
            $validatedPlay = $this->prefixPlayer($validatedPlay);
            $moderationOk = ModerationClientFacade::checkOk($validatedPlay);

            if ($moderationOk == false) {
                ModerationFailed::dispatch(request());
            } else {
                $zippedArray = data_get($previous, 'zipped', []);
                $genre = data_get($previous, 'genre');
                $playArray = data_get($previous, 'player', []);
                $storyArray = data_get($previous, 'story', []);
                $context = implode('\n', $zippedArray);
                $prefix = sprintf('Using the context and the following answer what is the next part of this text based %s text adventure for the player to choose from here is the context %s', $genre, $context);
                logger('Prefix', [$prefix]);
                logger('Context', [$context]);
                logger('Play', [$validatedPlay]);
                logger('Zippped', $zippedArray);
                /** @var ResponseDto $results */
                /** @phpstan-ignore-next-line */
                $results = TextClientFacade::setTemperature(0.7)
                    ->addPrefix($prefix)
                    ->text(str($validatedPlay)->append(' what happens next?')->toString());

                $results = optional(Arr::first($results->messages))->message;

                $results = str($results)->stripTags()->toString();

                $story[] = $results;
                $play[] = $validatedPlay;
                $zippedArray[] = $this->prefixStory($results);
                $zippedArray[] = $validatedPlay;

                Cache::set($session_id, [
                    'genre' => $genre,
                    'story' => $story,
                    'player' => $play,
                    'zipped' => $zippedArray,
                ]);

                $previous = Cache::get($session_id);
            }

            return response()->json([
                'next_story_line' => $results,
                'previous' => $previous,
            ], 200);
        } catch (\Exception $e) {
            logger($e->getMessage());
            logger($e);

            return response()->json([], 422);
        }
    }
}
