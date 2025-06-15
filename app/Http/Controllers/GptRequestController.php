<?php

namespace App\Http\Controllers;

use App\Models\GptRequest;
use Illuminate\Http\Request;
use App\Jobs\ProcessGptRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class GptRequestController extends Controller
{
    public function index()
    {
        $requests = GptRequest::latest()->get();
        Log::info('GPT Requests:', ['requests' => $requests->toArray()]);
        
        return Inertia::render('Gpt/Index', [
            'requests' => $requests
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000'
        ]);

        $gptRequest = GptRequest::create([
            'prompt' => $validated['prompt'],
            'status' => 'processing',
            'user_id' => auth()->user()->id
        ]);

        ProcessGptRequest::dispatch($gptRequest);

        return back();
    }

    public function show(GptRequest $gptRequest)
    {
        return Inertia::render('Gpt/Show', [
            'request' => $gptRequest
        ]);
    }

    public function getRequests()
    {
        return Inertia::render('Gpt/Index', [
            'requests' => GptRequest::latest()->get()
        ]);
    }
} 