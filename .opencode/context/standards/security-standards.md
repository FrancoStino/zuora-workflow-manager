# Security Standards

## Authentication & Authorization

### User Authentication
```php
// Strong password policies
class PasswordValidation
{
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'min:12',
            'confirmed',
            'regex:/[a-z]/',      // at least one lowercase
            'regex:/[A-Z]/',      // at least one uppercase
            'regex:/[0-9]/',      // at least one number
            'regex:/[@$!%*#?&]/', // at least one special character
        ];
    }
}

// Multi-factor authentication
class TwoFactorAuthentication
{
    public function enable(User $user): void
    {
        $secret = $this->generateSecret();
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
        ]);
        
        // Send QR code to user for setup
        $this->sendTwoFactorSetupEmail($user, $secret);
    }
    
    public function verify(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);
        return $this->verifyCode($secret, $code);
    }
}
```

### Role-Based Access Control
```php
// Permission definitions
class Permission
{
    const VIEW_WORKFLOWS = 'view workflows';
    const CREATE_WORKFLOWS = 'create workflows';
    const UPDATE_WORKFLOWS = 'update workflows';
    const DELETE_WORKFLOWS = 'delete workflows';
    const SYNC_WORKFLOWS = 'sync workflows';
    const VIEW_CUSTOMERS = 'view customers';
    const MANAGE_CUSTOMERS = 'manage customers';
    const VIEW_ANALYTICS = 'view analytics';
    const MANAGE_USERS = 'manage users';
}

// Policy implementation
class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::VIEW_WORKFLOWS);
    }
    
    public function view(User $user, Workflow $workflow): bool
    {
        if ($user->hasPermissionTo(Permission::VIEW_WORKFLOWS)) {
            return $user->hasPermissionTo(Permission::MANAGE_CUSTOMERS) ||
                   $user->customers()->where('customer_id', $workflow->customer_id)->exists();
        }
        
        return false;
    }
    
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::CREATE_WORKFLOWS);
    }
    
    public function update(User $user, Workflow $workflow): bool
    {
        return $this->view($user, $workflow) && 
               $user->hasPermissionTo(Permission::UPDATE_WORKFLOWS);
    }
    
    public function delete(User $user, Workflow $workflow): bool
    {
        return $this->view($user, $workflow) && 
               $user->hasPermissionTo(Permission::DELETE_WORKFLOWS);
    }
    
    public function sync(User $user, Workflow $workflow): bool
    {
        return $this->view($user, $workflow) && 
               $user->hasPermissionTo(Permission::SYNC_WORKFLOWS);
    }
}
```

## Data Protection

### Encryption at Rest
```php
class EncryptionService
{
    public function encryptSensitiveData(array $data): array
    {
        $sensitiveFields = ['client_secret', 'api_key', 'webhook_secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    public function decryptSensitiveData(array $data): array
    {
        $sensitiveFields = ['client_secret', 'api_key', 'webhook_secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                try {
                    $data[$field] = decrypt($data[$field]);
                } catch (DecryptException $e) {
                    Log::error('Failed to decrypt field', [
                        'field' => $field,
                        'error' => $e->getMessage()
                    ]);
                    $data[$field] = null;
                }
            }
        }
        
        return $data;
    }
}

// Secure model casting
class Customer extends Model
{
    protected $casts = [
        'client_secret' => 'encrypted',
        'api_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];
    
    public function getClientSecretAttribute($value): string
    {
        return decrypt($value);
    }
    
    public function setClientSecretAttribute($value): void
    {
        $this->attributes['client_secret'] = encrypt($value);
    }
}
```

### Data in Transit
```php
class SecureHttpClient
{
    private function createClient(): Client
    {
        return new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true, // SSL certificate verification
            'cert' => config('services.zuora.cert_path'),
            'headers' => [
                'User-Agent' => 'ZuoraWorkflowManager/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
    
    public function makeSecureRequest(string $method, string $url, array $options = []): Response
    {
        $client = $this->createClient();
        
        // Add security headers
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ]);
        
        return $client->request($method, $url, $options);
    }
}
```

## Input Validation & Sanitization

### Request Validation
```php
class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo(Permission::CREATE_WORKFLOWS);
    }
    
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_\.]+$/', // alphanumeric, spaces, hyphens, underscores, dots
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
                'filter' => 'sanitize_string', // custom filter
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    if (!$this->user()->canManageCustomer($value)) {
                        $fail('You do not have permission to manage this customer.');
                    }
                },
            ],
            'zuora_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/', // alphanumeric, hyphens, underscores
            ],
            'state' => [
                'required',
                'string',
                'in:Active,Inactive,Draft',
            ],
        ];
    }
    
    public function sanitize(): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            return $value;
        }, $this->validated());
    }
}
```

### XSS Prevention
```php
class XSSProtection
{
    public static function sanitize(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function sanitizeArray(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return self::sanitize($value);
            } elseif (is_array($value)) {
                return self::sanitizeArray($value);
            }
            return $value;
        }, $data);
    }
    
    public static function cleanHtml(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}

// Middleware for XSS protection
class XSSProtectionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isJson()) {
            $input = $request->json()->all();
            $sanitized = XSSProtection::sanitizeArray($input);
            $request->json()->replace($sanitized);
        } else {
            $input = $request->all();
            $sanitized = XSSProtection::sanitizeArray($input);
            $request->merge($sanitized);
        }
        
        return $next($request);
    }
}
```

## API Security

### Rate Limiting
```php
class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter()->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return $this->buildResponse($key, $maxAttempts);
        }
        
        $this->limiter()->hit($key, $decayMinutes);
        
        $response = $next($request);
        
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $this->limiter()->attempts($key)),
            'X-RateLimit-Reset' => $this->limiter()->availableIn($key),
        ]);
        
        return $response;
    }
    
    private function buildResponse(string $key, int $maxAttempts): Response
    {
        $seconds = $this->limiter()->availableIn($key);
        
        return response()->json([
            'success' => false,
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $seconds,
        ], 429);
    }
}

// Apply rate limiting to API routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('workflows', WorkflowApiController::class);
    Route::post('customers/{customer}/sync', [CustomerApiController::class, 'sync']);
});
```

### API Authentication
```php
class APIAuthentication
{
    public function authenticate(Request $request): ?User
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return null;
        }
        
        try {
            $payload = JWT::decode($token, config('app.jwt_secret'), ['HS256']);
            
            $user = User::find($payload->sub);
            
            if (!$user || !$user->isActive()) {
                return null;
            }
            
            // Check token expiration
            if ($payload->exp < time()) {
                return null;
            }
            
            return $user;
        } catch (Exception $e) {
            Log::warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 10) . '...',
            ]);
            
            return null;
        }
    }
    
    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + (60 * 60), // 1 hour
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ];
        
        return JWT::encode($payload, config('app.jwt_secret'));
    }
}
```

## Security Monitoring & Auditing

### Security Event Logging
```php
class SecurityLogger
{
    public function logAuthenticationAttempt(string $email, bool $success, string $ip = null): void
    {
        SecurityLog::create([
            'event_type' => 'authentication_attempt',
            'email' => $email,
            'success' => $success,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
        
        if (!$success) {
            $this->checkForBruteForce($email, $ip);
        }
    }
    
    public function logDataAccess(User $user, string $resource, int $resourceId): void
    {
        SecurityLog::create([
            'event_type' => 'data_access',
            'user_id' => $user->id,
            'resource' => $resource,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
    
    public function logSuspiciousActivity(string $description, array $context = []): void
    {
        SecurityLog::create([
            'event_type' => 'suspicious_activity',
            'description' => $description,
            'context' => $context,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
        
        // Send alert to security team
        $this->sendSecurityAlert($description, $context);
    }
    
    private function checkForBruteForce(string $email, ?string $ip): void
    {
        $attempts = SecurityLog::where('event_type', 'authentication_attempt')
            ->where('email', $email)
            ->where('success', false)
            ->where('created_at', '>', now()->subMinutes(15))
            ->count();
        
        if ($attempts >= 5) {
            $this->logSuspiciousActivity('Brute force attack detected', [
                'email' => $email,
                'ip' => $ip,
                'attempts' => $attempts,
            ]);
            
            // Block IP temporarily
            $this->blockIP($ip, now()->addHour());
        }
    }
}
```

### Vulnerability Scanning
```php
class VulnerabilityScanner
{
    public function scanDependencies(): array
    {
        $process = new Process(['composer', 'audit']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            return [
                'status' => 'error',
                'message' => 'Failed to run security audit',
                'output' => $process->getErrorOutput(),
            ];
        }
        
        $output = $process->getOutput();
        $vulnerabilities = $this->parseAuditOutput($output);
        
        if (!empty($vulnerabilities)) {
            $this->sendVulnerabilityAlert($vulnerabilities);
        }
        
        return [
            'status' => 'success',
            'vulnerabilities' => $vulnerabilities,
            'scanned_at' => now()->toISOString(),
        ];
    }
    
    public function scanCodeQuality(): array
    {
        $results = [];
        
        // Run static analysis
        $phpstan = $this->runPHPStan();
        $results['phpstan'] = $phpstan;
        
        // Run security linter
        $securityLinter = $this->runSecurityLinter();
        $results['security_linter'] = $securityLinter;
        
        // Check for hardcoded secrets
        $secrets = $this->scanForSecrets();
        $results['secrets'] = $secrets;
        
        return $results;
    }
    
    private function scanForSecrets(): array
    {
        $secrets = [];
        $patterns = [
            '/password\s*=\s*["\'][^"\']+["\']/',
            '/api_key\s*=\s*["\'][^"\']+["\']/',
            '/secret\s*=\s*["\'][^"\']+["\']/',
            '/token\s*=\s*["\'][^"\']+["\']/',
        ];
        
        $files = $this->getPHPFiles();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $secrets[] = [
                        'file' => $file,
                        'pattern' => $pattern,
                        'line' => $this->findLineNumber($content, $pattern),
                    ];
                }
            }
        }
        
        return $secrets;
    }
}
```

## Security Configuration

### Secure Headers
```php
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('Content-Security-Policy', $this->getCSPHeader());
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $this->getPermissionsPolicy());
        
        return $response;
    }
    
    private function getCSPHeader(): string
    {
        return "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data: https:; " .
               "font-src 'self'; " .
               "connect-src 'self' https://rest.zuora.com; " .
               "frame-ancestors 'none';";
    }
    
    private function getPermissionsPolicy(): string
    {
        return 'geolocation=(), ' .
               'microphone=(), ' .
               'camera=(), ' .
               'payment=(), ' .
               'usb=(), ' .
               'magnetometer=(), ' .
               'gyroscope=(), ' .
               'accelerometer=()';
    }
}
```

### Environment Security
```php
class EnvironmentSecurity
{
    public function validateEnvironment(): array
    {
        $issues = [];
        
        // Check if in debug mode in production
        if (config('app.debug') && config('app.env') === 'production') {
            $issues[] = 'Debug mode is enabled in production environment';
        }
        
        // Check if default encryption key is being used
        if (config('app.key') === 'SomeRandomString') {
            $issues[] = 'Default encryption key is being used';
        }
        
        // Check if HTTPS is enforced in production
        if (config('app.env') === 'production' && !config('app.force_ssl')) {
            $issues[] = 'HTTPS is not enforced in production';
        }
        
        // Check database connection security
        if (!config('database.connections.mysql.ssl')) {
            $issues[] = 'Database SSL connection is not enabled';
        }
        
        return $issues;
    }
    
    public function secureEnvironmentVariables(): void
    {
        // Ensure sensitive environment variables are not exposed
        $sensitiveKeys = [
            'APP_KEY',
            'DB_PASSWORD',
            'ZUORA_CLIENT_SECRET',
            'MAIL_PASSWORD',
            'AWS_SECRET_ACCESS_KEY',
        ];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($_ENV[$key])) {
                unset($_ENV[$key]);
                $_SERVER[$key] = '********';
            }
        }
    }
}
```