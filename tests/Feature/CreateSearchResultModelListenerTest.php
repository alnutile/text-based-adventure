<?php

namespace Tests\Feature;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use App\Events\SearchResultsEvent;
use App\Listeners\CreateSearchResultModelListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSearchResultModelListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_model()
    {
        $data = get_fixture('example.json');
        $dto = new ResponseDto($data);
        $event = new SearchResultsEvent($dto, 'foo bar');
        $this->assertDatabaseCount('search_results', 0);
        $listner = new CreateSearchResultModelListener();
        $listner->handle($event);
        $this->assertDatabaseCount('search_results', 1);
    }
}
