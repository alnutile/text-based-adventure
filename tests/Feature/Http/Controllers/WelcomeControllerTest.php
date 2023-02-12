<?php

namespace Tests\Feature\Http\Controllers;

use App\Jobs\GetNextStoryLineJob;
use App\Jobs\StartStoryJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome()
    {
        $this->get(route('home'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('prop_session_id')
        );
    }

    public function test_makes_request_for_genre()
    {
        Event::fake();
        Queue::fake();
        $this->post(route('startStory', ['genre' => 'fantasy']));
        Queue::assertPushed(StartStoryJob::class);
    }

    public function test_next_story_line()
    {
        Event::fake();
        Queue::fake();
        $data = $this->post(route('player'), ['play' => 'foo'])
            ->assertStatus(200)
            ->json();
        Queue::assertPushed(GetNextStoryLineJob::class);
    }
}
