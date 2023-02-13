<?php

namespace App\Jobs;

trait StoryHelperTrait
{
    protected function prefixStory($story): string
    {
        if (! str($story)->contains('AI:')) {
            return str($story)->prepend(' AI:')->toString();
        }

        return $story;
    }

    protected function prefixPlayer($story): string
    {
        if (! str($story)->contains('Human:')) {
            return str($story)->prepend(' Human:')->toString();
        }

        return $story;
    }

    protected function stops()
    {
        return [' Human:', ' AI:'];
    }
}
