<?php

namespace App\Jobs;

trait StoryHelperTrait
{
    protected function prefixStory($story): string
    {
        return str($story)->prepend('AI:')->toString();
    }

    protected function prefixPlayer($story): string
    {
        return str($story)->prepend('Human:')->toString();
    }
}
