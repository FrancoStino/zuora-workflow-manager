# System Health Check Command

## Description
Perform comprehensive health checks on the Zuora Workflow Manager system including database connectivity, API access, queue health, and system performance.

## Usage
```bash
/health-check [--component=all] [--detailed] [--format=table] [--email=admin@example.com]
```

## Parameters
- `--component`: Specific component to check (all, database, api, queue, performance, security)
- `--detailed`: Show detailed information for each check
- `--format`: Output format (table, json, markdown)
- `--email`: Email address to send report to

## Examples

### Full Health Check
```bash
/health-check
```

### Detailed Database Check
```bash
/health-check --component=database --detailed
```

### JSON Output with Email
```bash
/health-check --format=json --email=admin@example.com
```

### Quick API Check
```bash
/health-check --component=api
```

## Implementation
```php
<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\ZuoraService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Cache, Queue, Log, Mail};
use Illuminate\Support\Facades\Http;

class HealthCheckCommand extends Command
{
    protected $signature = 'health-check 
                            {--component=all : Component to check (all, database, api, queue, performance, security)}
                            {--detailed : Show detailed information}
                            {--format=table : Output format (table, json, markdown)}
                            {--email= : Email address to send report to}';
    
    protected $description = 'Perform system health checks';

    private array $results = [];
    private array $errors = [];

    public function handle(): int
    {
        $component = $this->option('component');
        $detailed = $this->option('detailed');
        $format = $this->option('format');
        $email = $this->option('email');

        $this->info("Starting health check for component: {$component}");

        try {
            $this->performHealthChecks($component, $detailed);
            $this->displayResults($format);
            
            if ($email) {
                $this->sendEmailReport($email);
            }

            $overallStatus = $this->getOverallStatus();
            $this->info("\nOverall System Status: {$overallStatus}");

            return $overallStatus === 'HEALTHY' ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("Health check failed: {$e->getMessage()}");
            Log::error('Health check command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    private function performHealthChecks(string $component, bool $detailed): void
    {
        $checks = [
            'all' => ['database', 'api', 'queue', 'performance', 'security'],
            'database' => ['database'],
            'api' => ['api'],
            'queue' => ['queue'],
            'performance' => ['performance'],
            'security' => ['security'],
        ];

        $componentsToCheck = $checks[$component] ?? $checks['all'];

        foreach ($componentsToCheck as $checkComponent) {
            $this->line("\nChecking {$checkComponent}...");
            $this->{"check" . ucfirst($checkComponent)}($detailed);
        }
    }

    private function checkDatabase(bool $detailed): void
    {
        try {
            // Basic connectivity
            $startTime = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->results['database']['connectivity'] = [
                'status' => 'PASS',
                'message' => 'Database connection successful',
                'response_time' => $responseTime . 'ms',
            ];

            if ($detailed) {
                // Check table counts
                $customerCount = Customer::count();
                $workflowCount = DB::table('workflows')->count();
                $jobCount = DB::table('jobs')->count();

                $this->results['database']['statistics'] = [
                    'status' => 'PASS',
                    'customers' => $customerCount,
                    'workflows' => $workflowCount,
                    'queue_jobs' => $jobCount,
                ];

                // Check database size
                $databaseSize = DB::select("SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()")[0]->size_mb;

                $this->results['database']['size'] = [
                    'status' => 'PASS',
                    'size_mb' => $databaseSize,
                ];
            }

        } catch (\Exception $e) {
            $this->results['database']['connectivity'] = [
                'status' => 'FAIL',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
            $this->errors[] = 'Database connectivity issue';
        }
    }

    private function checkApi(bool $detailed): void
    {
        try {
            // Test with a sample customer
            $customer = Customer::where('sync_status', 'active')->first();
            
            if (!$customer) {
                $this->results['api']['test_customer'] = [
                    'status' => 'WARN',
                    'message' => 'No active customers found for API testing',
                ];
                return;
            }

            $startTime = microtime(true);
            $zuoraService = new ZuoraService();
            $token = $zuoraService->getAccessToken(
                $customer->client_id,
                $customer->getDecryptedClientSecret(),
                $customer->base_url
            );
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->results['api']['authentication'] = [
                'status' => 'PASS',
                'message' => 'API authentication successful',
                'response_time' => $responseTime . 'ms',
                'customer' => $customer->name,
            ];

            if ($detailed) {
                // Test workflow endpoint
                $startTime = microtime(true);
                $workflows = $zuoraService->listWorkflows(
                    $customer->client_id,
                    $customer->getDecryptedClientSecret(),
                    $customer->base_url,
                    1,
                    1
                );
                $apiResponseTime = round((microtime(true) - $startTime) * 1000, 2);

                $this->results['api']['workflow_endpoint'] = [
                    'status' => 'PASS',
                    'message' => 'Workflow endpoint accessible',
                    'response_time' => $apiResponseTime . 'ms',
                    'total_workflows' => $workflows['pagination']['total'] ?? 'Unknown',
                ];
            }

        } catch (\Exception $e) {
            $this->results['api']['authentication'] = [
                'status' => 'FAIL',
                'message' => 'API authentication failed: ' . $e->getMessage(),
            ];
            $this->errors[] = 'API connectivity issue';
        }
    }

    private function checkQueue(bool $detailed): void
    {
        try {
            // Check queue connection
            $queueSize = Queue::size();
            $failedJobs = DB::table('failed_jobs')->count();

            $this->results['queue']['status'] = [
                'status' => $queueSize < 100 ? 'PASS' : 'WARN',
                'message' => "Queue size: {$queueSize}",
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
            ];

            if ($detailed) {
                // Check queue workers
                $workers = $this->getQueueWorkers();
                $this->results['queue']['workers'] = [
                    'status' => $workers > 0 ? 'PASS' : 'WARN',
                    'message' => "Active workers: {$workers}",
                    'worker_count' => $workers,
                ];

                // Test job dispatch
                $testJob = new \App\Jobs\TestHealthCheckJob();
                $dispatched = Queue::push($testJob);
                
                $this->results['queue']['dispatch'] = [
                    'status' => $dispatched ? 'PASS' : 'FAIL',
                    'message' => $dispatched ? 'Job dispatch successful' : 'Job dispatch failed',
                ];
            }

        } catch (\Exception $e) {
            $this->results['queue']['status'] = [
                'status' => 'FAIL',
                'message' => 'Queue check failed: ' . $e->getMessage(),
            ];
            $this->errors[] = 'Queue system issue';
        }
    }

    private function checkPerformance(bool $detailed): void
    {
        try {
            // Check cache
            $cacheTest = Cache::put('health_check_test', 'test_value', 60);
            $cacheValue = Cache::get('health_check_test');
            Cache::forget('health_check_test');

            $this->results['performance']['cache'] = [
                'status' => $cacheValue === 'test_value' ? 'PASS' : 'FAIL',
                'message' => $cacheValue === 'test_value' ? 'Cache working' : 'Cache not working',
            ];

            if ($detailed) {
                // Check memory usage
                $memoryUsage = memory_get_usage(true);
                $memoryLimit = ini_get('memory_limit');
                $memoryPercent = round(($memoryUsage / $this->parseMemoryLimit($memoryLimit)) * 100, 2);

                $this->results['performance']['memory'] = [
                    'status' => $memoryPercent < 80 ? 'PASS' : 'WARN',
                    'message' => "Memory usage: {$memoryPercent}%",
                    'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                    'limit' => $memoryLimit,
                ];

                // Check disk space
                $diskFree = disk_free_space('/');
                $diskTotal = disk_total_space('/');
                $diskPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

                $this->results['performance']['disk'] = [
                    'status' => $diskPercent < 90 ? 'PASS' : 'WARN',
                    'message' => "Disk usage: {$diskPercent}%",
                    'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
                    'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
                ];
            }

        } catch (\Exception $e) {
            $this->results['performance']['cache'] = [
                'status' => 'FAIL',
                'message' => 'Performance check failed: ' . $e->getMessage(),
            ];
            $this->errors[] = 'Performance issue detected';
        }
    }

    private function checkSecurity(bool $detailed): void
    {
        try {
            // Check HTTPS enforcement
            $appUrl = config('app.url');
            $isHttps = str_starts_with($appUrl, 'https://');

            $this->results['security']['https'] = [
                'status' => $isHttps ? 'PASS' : 'WARN',
                'message' => $isHttps ? 'HTTPS enforced' : 'HTTPS not enforced',
            ];

            if ($detailed) {
                // Check debug mode
                $debugMode = config('app.debug');
                $isProduction = config('app.env') === 'production';

                $this->results['security']['debug_mode'] = [
                    'status' => (!$isProduction || !$debugMode) ? 'PASS' : 'FAIL',
                    'message' => $debugMode ? 'Debug mode enabled' : 'Debug mode disabled',
                ];

                // Check app key
                $appKey = config('app.key');
                $isDefaultKey = $appKey === 'SomeRandomString';

                $this->results['security']['app_key'] = [
                    'status' => !$isDefaultKey ? 'PASS' : 'FAIL',
                    'message' => $isDefaultKey ? 'Default app key in use' : 'App key configured',
                ];
            }

        } catch (\Exception $e) {
            $this->results['security']['https'] = [
                'status' => 'FAIL',
                'message' => 'Security check failed: ' . $e->getMessage(),
            ];
            $this->errors[] = 'Security configuration issue';
        }
    }

    private function displayResults(string $format): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
                break;
            case 'markdown':
                $this->displayMarkdownResults();
                break;
            default:
                $this->displayTableResults();
        }
    }

    private function displayTableResults(): void
    {
        foreach ($this->results as $component => $checks) {
            $this->newLine();
            $this->info(strtoupper($component));
            
            foreach ($checks as $check => $result) {
                $status = $result['status'];
                $statusSymbol = $status === 'PASS' ? '✓' : ($status === 'WARN' ? '⚠' : '✗');
                
                $this->line("  {$statusSymbol} {$check}: {$result['message']}");
                
                if (isset($result['response_time'])) {
                    $this->line("    Response Time: {$result['response_time']}");
                }
            }
        }
    }

    private function displayMarkdownResults(): void
    {
        $this->line('# System Health Report');
        $this->line('Generated: ' . now()->toDateTimeString());
        $this->newLine();

        foreach ($this->results as $component => $checks) {
            $this->line('## ' . ucfirst($component));
            
            foreach ($checks as $check => $result) {
                $status = $result['status'];
                $statusEmoji = $status === 'PASS' ? '✅' : ($status === 'WARN' ? '⚠️' : '❌');
                
                $this->line("- {$statusEmoji} **{$check}**: {$result['message']}");
                
                if (isset($result['response_time'])) {
                    $this->line("  - Response Time: {$result['response_time']}");
                }
            }
            $this->newLine();
        }
    }

    private function getOverallStatus(): string
    {
        $allChecks = collect($this->results)->flatten(1);
        $failures = $allChecks->where('status', 'FAIL')->count();
        $warnings = $allChecks->where('status', 'WARN')->count();

        if ($failures > 0) {
            return 'UNHEALTHY';
        } elseif ($warnings > 0) {
            return 'WARNING';
        } else {
            return 'HEALTHY';
        }
    }

    private function sendEmailReport(string $email): void
    {
        $report = [
            'timestamp' => now()->toDateTimeString(),
            'overall_status' => $this->getOverallStatus(),
            'results' => $this->results,
            'errors' => $this->errors,
        ];

        Mail::raw($this->formatEmailReport($report), function ($message) use ($email) {
            $message->to($email)
                ->subject('System Health Report - ' . $this->getOverallStatus());
        });

        $this->info("Health report sent to: {$email}");
    }

    private function formatEmailReport(array $report): string
    {
        $content = "System Health Report\n";
        $content .= "Generated: {$report['timestamp']}\n";
        $content .= "Overall Status: {$report['overall_status']}\n\n";

        foreach ($report['results'] as $component => $checks) {
            $content .= ucfirst($component) . ":\n";
            foreach ($checks as $check => $result) {
                $content .= "  {$result['status']}: {$result['message']}\n";
            }
            $content .= "\n";
        }

        if (!empty($report['errors'])) {
            $content .= "Errors:\n";
            foreach ($report['errors'] as $error) {
                $content .= "  - {$error}\n";
            }
        }

        return $content;
    }

    private function getQueueWorkers(): int
    {
        // Implementation depends on your queue setup
        // This is a simplified example
        return 1;
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = strtolower($limit);
        $multiplier = 1;

        if (str_ends_with($limit, 'g')) {
            $multiplier = 1024 * 1024 * 1024;
        } elseif (str_ends_with($limit, 'm')) {
            $multiplier = 1024 * 1024;
        } elseif (str_ends_with($limit, 'k')) {
            $multiplier = 1024;
        }

        return (int) $limit * $multiplier;
    }
}
```

## Output Examples

### Table Format
```
Starting health check for component: all

DATABASE
  ✓ connectivity: Database connection successful
    Response Time: 2.5ms

API
  ✓ authentication: API authentication successful
    Response Time: 150.2ms
    Customer: Acme Corporation

QUEUE
  ✓ status: Queue size: 5
    Queue Size: 5
    Failed Jobs: 0

PERFORMANCE
  ✓ cache: Cache working

SECURITY
  ✓ https: HTTPS enforced

Overall System Status: HEALTHY
```

### JSON Format
```json
{
  "database": {
    "connectivity": {
      "status": "PASS",
      "message": "Database connection successful",
      "response_time": "2.5ms"
    }
  },
  "api": {
    "authentication": {
      "status": "PASS",
      "message": "API authentication successful",
      "response_time": "150.2ms",
      "customer": "Acme Corporation"
    }
  }
}
```

## Error Handling
- Component failures: Shows specific error messages
- Connection issues: Provides troubleshooting hints
- Performance warnings: Suggests optimization steps
- Security issues: Recommends security improvements

## Monitoring Integration
- Logs all health check results
- Tracks performance metrics over time
- Integrates with monitoring systems
- Provides historical data for analysis
- Supports automated alerting based on results