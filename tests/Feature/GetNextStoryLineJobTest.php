<?php

namespace Tests\Feature;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Events\ModerationFailed;
use Alnutile\LaravelChatgpt\Facades\ModerationClientFacade;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use App\Jobs\GetNextStoryLineJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GetNextStoryLineJobTest extends TestCase
{
    public function test_runs_job()
    {
        Event::fake();
        $data = get_fixture('example.json');
        $dto = new ResponseDto($data);
        TextClientFacade::shouldReceive('setTemperature->addPrefix->text')->twice()->andReturn($dto);
        ModerationClientFacade::shouldReceive('checkOk')->twice()->andReturnTrue();
        $job = new GetNextStoryLineJob(
            'foobar', 'I choose foo'
        );
        $job->handle();

        $job = new GetNextStoryLineJob(
            'foobar', 'I choose foo'
        );
        $job->handle();

        $previous = Cache::get('foobar');

        $this->assertEquals('Story Section:Once upon a time, in a distant kingdom, there lived a brave and noble knight. He had been sent on a quest by the King to explore a faraway land and bring back news of what he encountered. Armed with only his sword and shield, the knight set forth on his journey. Along the way, he encountered many strange creatures and encountered many magical and mysterious places. But most of all, he encountered danger at every turn. What will the knight discover on his quest?',
            $previous['zipped'][0]);
        $this->assertEquals('Player Chose:I choose foo',
            $previous['zipped'][1]);
    }

    public function test_mod_failed()
    {
        Event::fake();
        TextClientFacade::shouldReceive('text')->never();
        ModerationClientFacade::shouldReceive('checkOk')->once()->andReturnFalse();
        $job = new GetNextStoryLineJob(
            'foobar', 'I choose foo'
        );
        $job->handle();
        Event::assertDispatched(ModerationFailed::class);
    }
}
