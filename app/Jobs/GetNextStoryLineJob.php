<?php

namespace App\Jobs;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Events\ModerationFailed;
use Alnutile\LaravelChatgpt\Facades\ModerationClientFacade;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use App\Events\StoryProgressionEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class GetNextStoryLineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use StoryHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public string $sessionId, public string $play)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $previous = Cache::get($this->sessionId);
        $validatedPlay = str($this->play)
            ->trim()
            ->toString();
        $validatedPlay = $this->prefixPlayer($validatedPlay);

        $moderationOk = ModerationClientFacade::checkOk($validatedPlay);

        if ($moderationOk == false) {
            logger('Moderation Failed', [$validatedPlay]);
            ModerationFailed::dispatch(request());
        } else {
            $zippedArray = data_get($previous, 'zipped', []);
            $genre = data_get($previous, 'genre');
            $context = implode('\n', $zippedArray);
            $prefix = sprintf('Using the context and the following answer what is the next part of this text based %s text adventure for the human to choose from here is the context: %s', $genre, $context);
            /** @var ResponseDto $nextPartOfStory */
            /** @phpstan-ignore-next-line */
            $nextPartOfStory = TextClientFacade::setTemperature(0.7)
                ->setStop($this->stops())
                ->addPrefix($prefix)
                ->text(str($validatedPlay)
                    ->append(' what happens next?')->toString());

            $nextPartOfStory = optional(Arr::first($nextPartOfStory->messages))
                ->message;

            logger('NextPartOfStory', [$nextPartOfStory]);

            if ($nextPartOfStory === null) {
                GetNextStoryLineJob::dispatch($this->sessionId,
                    $this->play);
            } else {
                $nextPartOfStory = $this->prefixStory(str($nextPartOfStory)
                    ->stripTags()->toString());

                $story[] = $nextPartOfStory;

                $play[] = $validatedPlay;

                if (! empty($zippedArray)) {
                    $zippedArray[] = $validatedPlay;
                }

                if (! in_array($nextPartOfStory, $zippedArray)) {
                    $zippedArray[] = $nextPartOfStory;
                }

                Cache::set($this->sessionId, [
                    'genre' => $genre,
                    'story' => $story,
                    'player' => $play,
                    'zipped' => $zippedArray,
                ]);

                $previous = Cache::get($this->sessionId);

                StoryProgressionEvent::dispatch($this->sessionId,
                    $nextPartOfStory,
                    $previous);
            }
        }
    }
}
