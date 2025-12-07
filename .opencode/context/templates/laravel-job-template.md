# Laravel Job Template

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\{ModelName};
use App\Services\{ServiceName};
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class {JobName} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $retryUntil = 3600; // 1 hour

    /**
     * The queue the job should be sent to.
     */
    public string $queue = '{queue-name}';

    /**
     * Create a new job instance.
     */
    public function __construct(
        private {ModelName} $model
    ) {
        $this->onQueue($this->queue);
    }

    /**
     * Execute the job.
     */
    public function handle({ServiceName} $service): void
    {
        try {
            Log::info('Starting job execution', [
                'job' => self::class,
                'model_id' => $this->model->id,
                'attempt' => $this->attempts(),
            ]);

            // Check if job should still run (model might have been deleted)
            if (!$this->model->exists) {
                Log::warning('Job model no longer exists', [
                    'job' => self::class,
                    'model_id' => $this->model->id,
                ]);
                return;
            }

            // Execute main business logic
            $result = $service->{methodName}($this->model);

            // Update model status if needed
            $this->model->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            Log::info('Job completed successfully', [
                'job' => self::class,
                'model_id' => $this->model->id,
                'result' => $result,
                'duration' => $this->getJobDuration(),
            ]);

        } catch (Exception $e) {
            Log::error('Job execution failed', [
                'job' => self::class,
                'model_id' => $this->model->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update model status to indicate failure
            if ($this->model->exists) {
                $this->model->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'failed_at' => now(),
                ]);
            }

            // Re-throw exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job failed permanently', [
            'job' => self::class,
            'model_id' => $this->model->id,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Update model status to indicate permanent failure
        if ($this->model->exists) {
            $this->model->update([
                'status' => 'permanently_failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
        }

        // Send notification to administrators
        $this->notifyFailure($exception);
    }

    /**
     * Calculate job execution duration.
     */
    private function getJobDuration(): float
    {
        return microtime(true) - $this->job->getRawBody()['createdAt'] ?? 0;
    }

    /**
     * Send failure notification.
     */
    private function notifyFailure(Exception $exception): void
    {
        // Implement notification logic
        // Could send email, Slack notification, etc.
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            '{job-name}',
            'model:' . class_basename($this->model),
            'customer:' . ($this->model->customer_id ?? 'unknown'),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addSeconds($this->retryUntil);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function retryUntil(): int
    {
        return $this->backoff[$this->attempts() - 1] ?? end($this->backoff);
    }
}
```

## Batch Job Template

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\{ModelName};
use App\Services\{ServiceName};
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class {BatchJobName} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour for batch
    public string $queue = 'batch-processing';

    public function __construct(
        private Collection $models,
        private array $options = []
    ) {}

    public function handle(): void
    {
        Log::info('Starting batch job', [
            'job' => self::class,
            'model_count' => $this->models->count(),
            'options' => $this->options,
        ]);

        $jobs = $this->models->map(function ({ModelName} $model) {
            return new {SingleJobName}($model, $this->options);
        });

        $batch = Bus::batch($jobs->toArray())
            ->then(function (Batch $batch) {
                Log::info('Batch job completed successfully', [
                    'batch_id' => $batch->id,
                    'jobs_processed' => $batch->totalJobs,
                    'duration' => $batch->finishedAt->diffInSeconds($batch->createdAt),
                ]);

                // Post-batch processing
                $this->handleBatchCompletion($batch);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error('Batch job failed', [
                    'batch_id' => $batch->id,
                    'jobs_failed' => $batch->failedJobs,
                    'error' => $e->getMessage(),
                ]);

                // Handle batch failure
                $this->handleBatchFailure($batch, $e);
            })
            ->finally(function (Batch $batch) {
                Log::info('Batch job finished', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'successful_jobs' => $batch->successfulJobs,
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->onQueue($this->queue)
            ->dispatch();

        Log::info('Batch job dispatched', [
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
        ]);
    }

    private function handleBatchCompletion(Batch $batch): void
    {
        // Implement post-batch logic
        // Send notifications, update statistics, etc.
    }

    private function handleBatchFailure(Batch $batch, Throwable $e): void
    {
        // Implement batch failure handling
        // Send alerts, rollback changes, etc.
    }
}
```

## Scheduled Job Template

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\{ServiceName};
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class {ScheduledJobName} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = [300, 900, 1800]; // 5min, 15min, 30min
    public int $timeout = 1800; // 30 minutes
    public string $queue = 'scheduled';

    public function __construct(
        private array $parameters = []
    ) {}

    public function handle({ServiceName} $service): void
    {
        try {
            Log::info('Starting scheduled job', [
                'job' => self::class,
                'parameters' => $this->parameters,
                'scheduled_time' => now()->toDateTimeString(),
            ]);

            // Check if job should run (business rules, maintenance mode, etc.)
            if (!$this->shouldRun()) {
                Log::info('Scheduled job skipped', [
                    'job' => self::class,
                    'reason' => 'Business rules not met',
                ]);
                return;
            }

            // Execute scheduled task
            $result = $service->{methodName}($this->parameters);

            Log::info('Scheduled job completed', [
                'job' => self::class,
                'result' => $result,
                'duration' => $this->getJobDuration(),
            ]);

            // Send success notification if configured
            if ($this->parameters['notify_success'] ?? false) {
                $this->sendSuccessNotification($result);
            }

        } catch (Exception $e) {
            Log::error('Scheduled job failed', [
                'job' => self::class,
                'parameters' => $this->parameters,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Send failure notification
            $this->sendFailureNotification($e);

            throw $e;
        }
    }

    private function shouldRun(): bool
    {
        // Implement business rules for when job should run
        // Check maintenance mode, business hours, etc.
        return true;
    }

    private function sendSuccessNotification($result): void
    {
        // Implement success notification logic
    }

    private function sendFailureNotification(Exception $e): void
    {
        // Implement failure notification logic
    }

    private function getJobDuration(): float
    {
        return microtime(true) - $this->job->getRawBody()['createdAt'] ?? 0;
    }
}
```

## Job Dispatching Examples

### Basic Dispatch
```php
// Simple dispatch
{JobName}::dispatch($model);

// Dispatch with delay
{JobName}::dispatch($model)->delay(now()->addMinutes(5));

// Dispatch to specific queue
{JobName}::dispatch($model)->onQueue('high-priority');

// Dispatch with chain
{JobName}::dispatch($model)
    ->chain([
        new {NextJobName}($model),
        new {FinalJobName}($model),
    ]);
```

### Batch Dispatch
```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new {JobName}($model1),
    new {JobName}($model2),
    new {JobName}($model3),
])
->then(function (Batch $batch) {
    // All jobs completed successfully
})
->catch(function (Batch $batch, Throwable $e) {
    // First job failure detected
})
->finally(function (Batch $batch) {
    // Batch finished executing
})
->dispatch();
```

### Conditional Dispatch
```php
// Dispatch only if condition is met
if ($model->needsProcessing()) {
    {JobName}::dispatch($model);
}

// Dispatch with validation
try {
    $this->validateJobParameters($parameters);
    {JobName}::dispatch($model, $parameters);
} catch (ValidationException $e) {
    Log::warning('Job parameters invalid', [
        'job' => {JobName}::class,
        'errors' => $e->errors(),
    ]);
}
```

## Best Practices

### Job Design
- Make jobs idempotent (safe to retry)
- Keep jobs focused on single responsibility
- Use proper error handling and logging
- Implement appropriate retry strategies
- Set reasonable timeouts and memory limits

### Performance
- Use chunked processing for large datasets
- Optimize database queries with proper indexing
- Implement proper memory management
- Use appropriate queue priorities
- Monitor job performance metrics

### Error Handling
- Log detailed error information
- Implement proper retry logic with backoff
- Handle permanent failures gracefully
- Send notifications for critical failures
- Use dead letter queues for failed jobs

### Testing
- Write unit tests for job logic
- Test error scenarios and edge cases
- Mock external dependencies
- Test retry and failure behavior
- Use appropriate test data factories

### Monitoring
- Track job success and failure rates
- Monitor queue depth and processing times
- Set up alerts for job failures
- Log job performance metrics
- Implement job health checks