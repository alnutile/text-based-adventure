<?php

namespace Tests\Feature\Http\Controllers;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Events\ModerationFailed;
use Alnutile\LaravelChatgpt\Facades\ModerationClientFacade;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class WelcomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome() {
        $this->get(route("home"))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component("Welcome")
            ->has("session_id")
        );
    }

    public function test_makes_request_for_genre() {
        $data = get_fixture('example.json');
        $dto = new ResponseDto($data);
        TextClientFacade::shouldReceive('setTemperature->text')->once()->andReturn($dto);

        $this->get(route("home", ['genre' => 'fantasy']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component("Welcome")
                ->has("session_id")
                ->has("genre")
                ->has("story")
            );
    }

    public function test_next_story_line()
    {
        Event::fake();
        $data = get_fixture('example.json');
        $dto = new ResponseDto($data);
        TextClientFacade::shouldReceive('setTemperature->addPrefix->text')->once()->andReturn($dto);
        ModerationClientFacade::shouldReceive('checkOk')->andReturnTrue();
        $data = $this->post(route('player'), ['play' => 'foo'])
            ->assertStatus(200)
            ->json();

        $this->assertArrayHasKey('next_story_line', $data);
        $this->assertArrayHasKey('previous', $data);
        $this->assertNotNull(data_get($data, 'previous.story'));
        $this->assertNotNull(data_get($data, 'previous.player'));
    }

    public function test_mod_failed()
    {
        Event::fake();
        TextClientFacade::shouldReceive('text')->never();
        ModerationClientFacade::shouldReceive('checkOk')->once()->andReturnFalse();
        $this->post(route('search'), ['search' => 'foo'])->assertStatus(200);
        Event::assertDispatched(ModerationFailed::class);
    }
}
