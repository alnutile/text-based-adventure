<?php

namespace App\Listeners;

use App\Events\SearchResultsEvent;
use App\Models\SearchResult;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateSearchResultModelListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\SearchResultsEvent  $event
     * @return void
     */
    public function handle(SearchResultsEvent $event)
    {
        SearchResult::create(
            ['search_string' => $event->searchPhrase, 'results' => $event->responseDto]
        );
    }
}
