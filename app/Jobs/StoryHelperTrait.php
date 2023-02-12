<?php

namespace App\Jobs;

trait StoryHelperTrait
{
    protected function prefixStory($story): string
    {
        return str($story)->prepend('Story Section:')->toString();
    }

    protected function prefixPlayer($story): string
    {
        return str($story)->prepend('Player Chose:')->toString();
    }
}
