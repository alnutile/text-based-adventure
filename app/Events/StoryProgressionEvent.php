<?php

namespace App\Events;

use App\Jobs\StoryHelperTrait;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoryProgressionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use StoryHelperTrait;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public string $session_id, public string $next_story_line, public array $previous)
    {
        $this->next_story_line = $this->prefixStory($this->next_story_line);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('story.'.$this->session_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'play';
    }
}
