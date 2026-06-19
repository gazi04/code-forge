<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued variant of the framework's email-verification notification.
 *
 * Sending the verification mail out-of-request keeps registration from
 * 500-ing (and leaving a half-created user) when the mail transport is slow,
 * down, or rate-limited — the queue retries instead of crashing the request.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
