<?php

namespace App\Jobs;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use App\Events\StoryProgressionEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class StartStoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use StoryHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public string $sessionId,
                                public string $genre)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $question = sprintf('I would like to have you start a text adventure game in the genre of %s can you please give me the start of the story so the player can choose the next step',
            $this->genre);

        /** @var ResponseDto $startOfStory */
        /** @phpstan-ignore-next-line */
        $startOfStory = TextClientFacade::setTemperature(0.7)
            ->setStop($this->stops())
            ->text($question);

        $startOfStory = optional(Arr::first($startOfStory->messages))->message;

        $startOfStory = str($startOfStory)->whenStartsWith('?', function ($item) {
            return $item->replaceFirst('?', '');
        })->stripTags()->toString();

        Cache::set($this->sessionId, [
            'genre' => $this->genre,
            'story' => [
                $this->prefixStory($startOfStory),
            ],
            'player' => [],
            'zipped' => [
                $this->prefixStory($startOfStory),
            ],
        ]);

        $previous = Cache::get($this->sessionId);

        StoryProgressionEvent::dispatch($this->sessionId, $startOfStory, $previous);
    }
}
