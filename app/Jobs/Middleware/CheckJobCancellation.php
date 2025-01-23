<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class CheckJobCancellation
{
    public function handle($job, Closure $next)
    {
        $jobId = $job->jobProgressId ?? null;

        if ($jobId && Cache::get("job_cancelled_{$jobId}")) {
            return;
        }

        return $next($job);
    }
}
