<?php

namespace Tests\Feature\Models;

use Alnutile\LaravelChatgpt\DTOs\ResponseDto;
use App\Models\SearchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory()
    {
        $model = SearchResult::factory()->create();
        $this->assertNotNull($model->search_string);
    }

    public function test_get_model_results()
    {
        $model = SearchResult::factory()->create();
        $results = SearchResult::where('search_string', $model->search_string)->first();
        $dto = new ResponseDto($results->results);
        $this->assertNotEmpty($dto->messages);
    }
}
