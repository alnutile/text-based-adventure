<?php

namespace App\Http\Controllers;

use App\Jobs\GetNextStoryLineJob;
use App\Jobs\StartStoryJob;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function home()
    {
        $session_id = request()->session()->token();

        return Inertia::render('Welcome', [
            'prop_session_id' => $session_id,
        ]);
    }

    public function startStory()
    {
        $session_id = request()->session()->token();

        $validated = request()->validate([
            'genre' => ['required'],
        ]);

        StartStoryJob::dispatch($session_id, $validated['genre']);

        return response()->json('OK', 200);
    }

    public function player()
    {
        $session_id = request()->session()->token();

        $validated = request()->validate([
            'play' => 'required',
        ]);

        GetNextStoryLineJob::dispatch($session_id, $validated['play']);

        return response()->json('OK', 200);
    }

    public function startOver()
    {
        $session_id = request()->session()->token();
        Cache::forget($session_id);

        return redirect()->route('home');
    }
}
