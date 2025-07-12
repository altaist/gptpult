<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Events\JobTimedOut;
use App\Listeners\QueueEventListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        JobQueued::class => [
            [QueueEventListener::class, 'handleJobQueued'],
        ],
        JobProcessing::class => [
            [QueueEventListener::class, 'handleJobProcessing'],
        ],
        JobProcessed::class => [
            [QueueEventListener::class, 'handleJobProcessed'],
        ],
        JobFailed::class => [
            [QueueEventListener::class, 'handleJobFailed'],
        ],
        JobExceptionOccurred::class => [
            [QueueEventListener::class, 'handleJobExceptionOccurred'],
        ],
        JobRetryRequested::class => [
            [QueueEventListener::class, 'handleJobRetryRequested'],
        ],
        JobTimedOut::class => [
            [QueueEventListener::class, 'handleJobTimedOut'],
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
} 