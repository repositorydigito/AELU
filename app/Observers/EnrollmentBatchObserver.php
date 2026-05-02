<?php

namespace App\Observers;

use App\Models\EnrollmentBatch;
use Illuminate\Support\Facades\Cache;

class EnrollmentBatchObserver
{
    public function created(EnrollmentBatch $batch): void
    {
        $this->flushFilterCaches();
    }

    public function updated(EnrollmentBatch $batch): void
    {
        $this->flushFilterCaches();
    }

    public function deleted(EnrollmentBatch $batch): void
    {
        $this->flushFilterCaches();
    }

    private function flushFilterCaches(): void
    {
        Cache::forget('enrollment_batches:filter:creators');
        Cache::forget('enrollment_batches:filter:periods');
    }
}
