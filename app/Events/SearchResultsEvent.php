<?php

namespace App\Events;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SearchResultsEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ResponseDto $responseDto, public string $searchPhrase)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
