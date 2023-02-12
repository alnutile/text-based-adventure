<?php

namespace Tests\Feature;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use Alnutile\LaravelChatgpt\Events\ModerationFailed;
use Alnutile\LaravelChatgpt\Facades\ModerationClientFacade;
use Alnutile\LaravelChatgpt\Facades\TextClientFacade;
use App\Jobs\GetNextStoryLineJob;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GetNextStoryLineJobTest extends TestCase
{
    public function test_runs_job()
    {
        Event::fake();
        $data = get_fixture('example.json');
        $dto = new ResponseDto($data);
        TextClientFacade::shouldReceive('setTemperature->addPrefix->text')->once()->andReturn($dto);
        ModerationClientFacade::shouldReceive('checkOk')->once()->andReturnTrue();
        $job = new GetNextStoryLineJob(
            'foobar', 'I choose foo'
        );
        $job->handle();
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
