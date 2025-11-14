<div align="center">

# Zuora Workflow Manager

![Zuora Workflows Logo](public/images/logo.svg)

[![PHP Version](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-4.2-F03A5F?style=for-the-badge&logo=laravel&logoColor=white)](https://filamentphp.com/)
[![Lando](https://img.shields.io/badge/Lando-Dev%20Environment-000000?style=for-the-badge&logo=docker&logoColor=white)](https://lando.dev/)
[![MariaDB](https://img.shields.io/badge/MariaDB-11.4-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Redis](https://img.shields.io/badge/Redis-7.0-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io/)
[![Nginx](https://img.shields.io/badge/Nginx-Latest-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org/)
[![License](https://img.shields.io/badge/License-MIT-4CAF50?style=for-the-badge&logoColor=white)](LICENSE)
[![Latest Release](https://img.shields.io/badge/v0.6.0-2196F3?style=for-the-badge&logoColor=white)](https://github.com/FrancoStino/zuora-laravel/releases)
[![Status](https://img.shields.io/badge/Status-Active-00BCD4?style=for-the-badge&logoColor=white)](https://github.com/FrancoStino/zuora-laravel)

</div>

A powerful web application for synchronizing, viewing, and managing Zuora workflows directly from your Filament admin
dashboard. Built with modern Laravel architecture featuring automated sync jobs, real-time dashboards, and comprehensive
workflow management.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [API Integration](#api-integration)
- [Monitoring & Troubleshooting](#monitoring--troubleshooting)
- [Performance](#performance)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### 🔄 Automatic Synchronization

- **Hourly Sync**: All customers are automatically synchronized with Zuora API on a scheduled interval
- **Manual Sync**: Dedicated "Sync Workflows" button for immediate synchronization from the customer page
- **Intelligent Pagination**: Supports up to 50 workflows per page with automatic loop-through
- **Stale Data Management**: Automatically removes workflows no longer present in Zuora

### 📊 [Filament](https://filamentphp.com/) Dashboard Integration

- **Rich Workflow Visualization**: Comprehensive table with search, sorting, and filtering capabilities
- **Advanced Filters**: Filter by workflow state (Active/Inactive)
- **Dynamic Columns**:
    - Zuora Workflow ID
    - Workflow Name
    - Status badge with color coding
    - Creation and update timestamps
    - Last synchronization timestamp
- **Workflow Download**: Direct download button for exporting workflows from Zuora

### ⚙️ Background Job Processing

- **Queue-Based Architecture**: All synchronization operations run as background jobs without blocking the UI
- **Automatic Retry Logic**: 3 retry attempts with 60-second backoff intervals
- **Comprehensive Logging**: Tracks detailed statistics (created, updated, deleted workflows)
- **[Redis](https://redis.io/) Caching**: Optional caching layer for improved performance

### 🔐 Enterprise-Grade Features

- **OAuth 2.0 Token Management**: Secure authentication with 1-hour token caching
- **Error Handling**: Robust exception handling with detailed logging
- **Data Consistency**: ACID-compliant synchronization with [MariaDB](https://mariadb.org/) foreign key constraints
- **[Filament Shield](https://github.com/BezhansallehCMS/filament-shield) Integration**: Role-based access control (RBAC) for admin operations

---

## Requirements

| Requirement | Version | Link |
|-------------|---------|------|
| [PHP](https://www.php.net/) | 8.4+ | [php.net](https://www.php.net/) |
| [Laravel](https://laravel.com) | 12.0+ | [laravel.com](https://laravel.com) |
| [Lando](https://lando.dev) | Latest | [lando.dev](https://lando.dev) |
| [Docker](https://www.docker.com/) | 20.0+ | [docker.com](https://www.docker.com/) |
| [Node.js](https://nodejs.org/) | 18.0+ | [nodejs.org](https://nodejs.org/) |

### Containerized Stack (via Lando)

| Component | Version | Link |
|-----------|---------|------|
| [Nginx](https://nginx.org/) | Latest | [nginx.org](https://nginx.org/) |
| [MariaDB](https://mariadb.org/) | 11.4 | [mariadb.org](https://mariadb.org/) |
| [Redis](https://redis.io/) | 7.0 | [redis.io](https://redis.io/) |
| [PHP-FPM](https://www.php.net/manual/en/install.fpm.php) | 8.4 | [php.net/fpm](https://www.php.net/manual/en/install.fpm.php) |
| [Xdebug](https://xdebug.org/) | Latest | [xdebug.org](https://xdebug.org/) |

### PHP Dependencies

**Admin & Framework**:

- [`filament/filament`](https://filamentphp.com/) (4.2+) - Modern admin dashboard
- [`laravel/framework`](https://laravel.com) (12.0+) - Core framework
- [`bezhansalleh/filament-shield`](https://github.com/BezhansallehCMS/filament-shield) (4.0+) - Role-based access control
- [`dutchcodingcompany/filament-socialite`](https://github.com/DutchCodingCompany/filament-socialite) (3.0+) - OAuth integration

**Frontend**:

- [Tailwind CSS](https://tailwindcss.com/) (4.0+)
- [Vite](https://vitejs.dev/) (7.0+)
- [Alpine.js](https://alpinejs.dev/) (via Filament)

---

## Installation

### Option A: Using [Lando](https://lando.dev) (Recommended)

[Lando](https://lando.dev) provides a containerized development environment with [PHP 8.4](https://www.php.net/), [MariaDB 11.4](https://mariadb.org/), [Nginx](https://nginx.org/), and [Redis](https://redis.io/) pre-configured. It eliminates "works on my machine" problems by using [Docker](https://www.docker.com/) containers.

**Step 1: Clone the Repository**

```bash
git clone https://github.com/FrancoStino/zuora-laravel.git
cd zuora-laravel
```

**Step 2: Start Lando**

```bash
lando start
```

This will automatically:

- Start PHP 8.4, MariaDB 11.4, Nginx, and Redis containers
- Run `composer install`
- Configure the development environment

**Step 3: Setup Environment**

```bash
# Copy environment file
cp .env.example .env

# Generate application key
lando artisan key:generate

# Run migrations
lando artisan migrate

# Install frontend dependencies
lando npm install

# Build frontend assets
lando npm run build
```

**Step 4: Access the Dashboard**

Navigate to `https://zuora-workflows.lndo.site/admin` and create your admin account.

**Step 5: Configure Customer Zuora Credentials**

After login:

1. Go to **Customers** in the sidebar
2. Create a new customer
3. Enter Zuora API credentials:
    - **Client ID**: Your Zuora OAuth client ID
    - **Client Secret**: Your Zuora OAuth client secret
    - **Base URL**: `https://api.zuora.com/v1` (or your Zuora environment URL)
4. Save the customer
5. Click **Sync Workflows** to sync workflows from Zuora

**Available Lando Commands**

```bash
# Run artisan commands
lando artisan migrate
lando artisan tinker
lando artisan queue:work

# Run tests with PHPUnit
lando test

# Code style formatting with Pint
lando pint

# View logs
lando logs -f

# Database access
lando mariadb

# Service management
lando start    # Start services
lando stop     # Stop services
lando restart  # Restart services
lando destroy  # Remove containers and data
```

**Lando URL Reference**

- **Web Dashboard**: `https://zuora-workflows.lndo.site`
- **Admin Panel**: `https://zuora-workflows.lndo.site/admin`
- **API**: Available at `https://zuora-workflows.lndo.site/api`
- **Database**: `mariadb` (hostname for local connections)

---

### Option B: Manual Installation

**Step 1: Clone the Repository**

```bash
git clone https://github.com/FrancoStino/zuora-laravel.git
cd zuora-laravel
```

**Step 2: Install Dependencies**

```bash
# PHP dependencies
composer install

# Frontend dependencies
npm install
```

**Step 3: Configure Environment**

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

**Step 4: Configure Zuora Credentials**

Edit `.env` and add your Zuora API credentials:

```env
ZUORA_API_BASE_URL=https://api.zuora.com/v1
ZUORA_CLIENT_ID=your_client_id_here
ZUORA_CLIENT_SECRET=your_client_secret_here
```

**Step 5: Run Database Migrations**

```bash
php artisan migrate
```

**Step 6: Build Frontend Assets**

```bash
npm run build
```

**Step 7: Start Services**

**Development Mode (Recommended)**

```bash
composer run dev
```

This command concurrently starts:

- Laravel development server (port 8000)
- Queue worker (processes background jobs)
- Log viewer (pail)
- Vite development server

**Or start services manually**

```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Scheduler
php artisan schedule:work

# Terminal 4: Vite dev server
npm run dev
```

**Step 8: Access the Dashboard**

Navigate to `http://localhost:8000/admin` and log in with your admin credentials.

---

## Configuration

### Queue Driver Setup

The application uses the database queue driver by default. The `.env.example` file specifies:

```env
QUEUE_CONNECTION=database
```

To change to [Redis](https://redis.io/) or another [Laravel](https://laravel.com/docs/queues) queue driver:

```env
QUEUE_CONNECTION=redis  # or sync, sqs, etc.
```

See [Laravel Queue Documentation](https://laravel.com/docs/queues) for more driver options.

### Scheduler Configuration

Modify sync frequency in `app/Console/Kernel.php`:

```php
// Default: every hour
$schedule->call(function () {
    Customer::all()->each(fn (Customer $customer) => 
        SyncCustomerWorkflows::dispatch($customer)
    );
})->hourly();

// Every 30 minutes
->everyThirtyMinutes();

// Every 15 minutes
->every(15)->minutes();

// Custom interval (every 5 minutes)
->everyFiveMinutes();
```

Then start the scheduler:

```bash
# Using Lando
lando artisan schedule:work

# Or manually
php artisan schedule:work
```

### Zuora API Configuration

Zuora credentials are stored **per-customer in the database**, not in environment variables. This enables multi-tenant
support.

**For each customer:**

1. Login to the admin dashboard
2. Navigate to **Customers**
3. Create/Edit a customer and enter:
     - **Client ID**: [Zuora OAuth 2.0 Client ID](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Client_Authentication)
     - **Client Secret**: [Zuora OAuth 2.0 Client Secret](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Client_Authentication)
     - **Base URL**: `https://api.zuora.com/v1` (see [Zuora API Endpoints](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#API_Endpoints))

The application automatically handles:

- [OAuth 2.0](https://tools.ietf.org/html/rfc6749) token generation and caching (1-hour TTL)
- [Paginated requests](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#Pagination) (max 50 items per page)
- Error handling and retry logic for failed syncs

---

## Usage

### Web Interface

1. **Access Admin Dashboard**: Navigate to `https://zuora-workflows.lndo.site/admin` (Lando) or
   `http://localhost:8000/admin` (manual)
2. **Create a Customer**:
    - Go to **Customers** in the sidebar
    - Click "Create" and enter customer details with Zuora API credentials
3. **View Workflows**: Click on a customer to see their synced workflows
4. **Manual Sync**: Click the **Sync Workflows** button in the customer page header
5. **Filter & Search**: Use filters and search box to find workflows by name, ID, or state

### CLI Commands (with Lando)

**Sync specific customer**:

```bash
lando artisan app:sync-workflows --customer="Customer Name"
```

**Sync all customers**:

```bash
lando artisan app:sync-workflows --all
```

**Check failed jobs**:

```bash
lando artisan queue:failed
```

**Retry failed jobs**:

```bash
lando artisan queue:retry all
```

**Clear all failed jobs**:

```bash
lando artisan queue:flush
```

**View database**:

```bash
lando mariadb
```

### Queue Management

**Start queue worker** (processes background sync jobs):

```bash
lando artisan queue:work --verbose
```

**Stop queue worker**:

```bash
Ctrl+C
```

**Check queue status and failed jobs**:

```bash
lando artisan queue:failed
```

### Running the Complete Stack (Lando)

To run the full development environment with web, queue, scheduler, and logs:

```bash
# Terminal 1: Keep Lando running
lando start

# Terminal 2: Process queue jobs
lando artisan queue:work

# Terminal 3: Process scheduled tasks
lando artisan schedule:work

# Terminal 4: View logs
lando logs -f
```

Or use the concurrency helper for manual installations:

```bash
# Terminal 1 (manual setup only)
composer run dev
```

---

## Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│              [FILAMENT](https://filamentphp.com/) DASHBOARD UI                │
│              CustomerWorkflows Page                          │
│         (View + Sync Button + Workflow Table)               │
└─────────────┬──────────────────────────┬────────────────────┘
              │                          │
         Dispatch Job          Query [MariaDB](https://mariadb.org/)
              │                          │
              ↓                          ↓
       ┌─────────────────────────────────────────────────────┐
       │     [Laravel](https://laravel.com) Background Jobs (Queue)       │
       │         SyncCustomerWorkflows Job                    │
       │     (3 retry attempts, 60s backoff)                  │
       └─────────────┬─────────────────────────────────────────┘
                     │
                     ↓
       ┌─────────────────────────────────────────────────────┐
       │            WorkflowSyncService                       │
       │  • Orchestrates synchronization                      │
       │  • Handles paginated requests                        │
       │  • Save/Update/Delete operations                     │
       │  • Optional [Redis](https://redis.io/) caching                      │
       └─────────────┬─────────────────────────────────────────┘
                     │
                     ↓
       ┌─────────────────────────────────────────────────────┐
       │            ZuoraService                              │
       │  • [OAuth 2.0](https://tools.ietf.org/html/rfc6749) token management              │
       │  • HTTP API calls                                    │
       │  • Response normalization                            │
       └──────────────────────────────────────────────────────┘
                     │
                     ↓
       ┌─────────────────────────────────────────────────────┐
       │          Zuora REST API                              │
       │   https://api.zuora.com/v1/workflows                │
       └──────────────────────────────────────────────────────┘
```

### Service Layer Architecture

**ZuoraService** (`app/Services/ZuoraService.php`):

- Handles [OAuth 2.0](https://tools.ietf.org/html/rfc6749) authentication with token caching
- Manages HTTP requests to [Zuora API](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API)
- Normalizes API responses into consistent format
- Implements error handling and logging

**WorkflowSyncService** (`app/Services/WorkflowSyncService.php`):

- Orchestrates the synchronization process
- Handles [pagination](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#Pagination) through Zuora workflows
- Creates, updates, and deletes workflow records
- Maintains data consistency with [MariaDB](https://mariadb.org/) database

### Job Architecture

**SyncCustomerWorkflows** (`app/Jobs/SyncCustomerWorkflows.php`):

- Implements Laravel's `ShouldQueue` interface
- 3 retry attempts with 60-second backoff
- Dispatches via database queue
- Logs detailed sync statistics

---

## Database Schema

Built on [MariaDB 11.4](https://mariadb.org/) with support for [foreign key constraints](https://mariadb.com/kb/en/foreign-keys/) and [indexes](https://mariadb.com/kb/en/create-index/) for optimal query performance.

### Workflows Table

```sql
CREATE TABLE workflows
(
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id    BIGINT UNSIGNED NOT NULL,
    zuora_id       VARCHAR(255) NOT NULL UNIQUE,
    name           VARCHAR(255) NOT NULL,
    description    LONGTEXT,
    state          VARCHAR(100),
    created_on     TIMESTAMP,
    updated_on     TIMESTAMP,
    last_synced_at TIMESTAMP,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    INDEX          idx_customer_id (customer_id),
    INDEX          idx_zuora_id (zuora_id),
    INDEX          idx_state (state)
);
```

### Customers Table

```sql
CREATE TABLE customers
(
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name             VARCHAR(255) NOT NULL,
    zuora_account_id VARCHAR(255) UNIQUE,
    zuora_api_key    VARCHAR(255),
    zuora_api_secret VARCHAR(255),
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX            idx_name (name)
);
```

### Jobs Table ([Laravel Queue](https://laravel.com/docs/queues))

```sql
CREATE TABLE jobs
(
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    queue        VARCHAR(255) NOT NULL,
    payload      LONGTEXT     NOT NULL,
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    reserved_at  BIGINT UNSIGNED,
    available_at BIGINT UNSIGNED NOT NULL,
    created_at   BIGINT       NOT NULL,

    INDEX        idx_queue (queue)
);
```

For [Redis](https://redis.io/) queue support, configure `QUEUE_CONNECTION=redis` in `.env`.

---

## API Integration

### [Zuora REST API](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API) Workflow Endpoints

See [Zuora API Documentation](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API) for complete API reference.

**List Workflows**

```http
GET /v1/workflows
Parameters:
  - page (int, default: 1)
  - page_length (int, default: 20, max: 50)
  - name (string, optional)
  - state (string, optional: Active/Inactive)
  - ondemand_trigger (boolean, optional)
  - scheduled_trigger (boolean, optional)
  - callout_trigger (boolean, optional)
```

Reference: [Zuora Workflows API](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#/Workflows)

**Sample Response**

```json
{
  "data": [
    {
      "id": "wf_12345",
      "name": "Subscription Renewal Workflow",
      "state": "Active",
      "created_on": "2025-01-01T00:00:00Z",
      "updated_on": "2025-01-10T12:30:45Z"
    }
  ],
  "pagination": {
    "page": 1,
    "page_length": 50,
    "next_page": "https://rest.zuora.com/workflows?page=2&page_length=50"
  }
}
```

**Download Workflow**

```http
GET /v1/workflows/{workflowId}/definition
Response: Binary YAML/JSON workflow definition
```

Reference: [Zuora Get Workflow Definition](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#/Workflows/get_workflows__id__definition)

---

## Monitoring & Troubleshooting

### Logs (with Lando)

View application logs in real-time:

```bash
# View all Lando container logs
lando logs -f

# View specific service logs (appserver = PHP container)
lando logs -s appserver -f

# View file-based logs
lando exec appserver tail -f storage/logs/laravel.log

# Filter for workflow sync logs
lando exec appserver grep -i "workflow" storage/logs/laravel.log
```

### Queue Status

**Check queue health and process jobs**:

```bash
# Start queue worker
lando artisan queue:work --verbose

# In another terminal, check failed jobs
lando artisan queue:failed

# Retry specific failed job
lando artisan queue:retry {job-id}

# Clear all failed jobs
lando artisan queue:flush
```

### Database Access

```bash
# Access MariaDB directly
lando mariadb

# Or with database name
lando mariadb zuora_workflows

# View queue table
SELECT * FROM jobs;

# View failed jobs table
SELECT * FROM failed_jobs;
```

### Common Issues

#### 1. Queue Jobs Not Processing

```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# Start queue worker with verbose output
lando artisan queue:work --verbose

# Check failed jobs
lando artisan queue:failed

# Retry all failed jobs
lando artisan queue:retry all

# View logs for errors
lando logs -f
```

#### 2. Workflows Not Synchronizing

- **Verify Zuora credentials**:
    - Go to **Customers** in the admin dashboard
    - Check that Client ID, Client Secret, and Base URL are correct

- **Check logs for errors**:
  ```bash
  lando logs -f | grep -i "error\|zuora"
  ```

- **Run manual sync with debug output**:
  ```bash
  lando artisan app:sync-workflows --customer="Name" -vvv
  ```

- **Verify network connectivity** to `api.zuora.com`:
  ```bash
  lando exec appserver curl -I https://api.zuora.com/v1
  ```

#### 3. Pagination Issues

- Zuora API max `page_length` is 50 (application default)
- Check API response for `next_page` in pagination object
- Monitor logs for pagination cursor errors:
  ```bash
  lando logs -f | grep -i "pagination\|page"
  ```

#### 4. Token Expiration Issues

- Application caches OAuth tokens for 1 hour
- Automatic token refresh occurs on expiration
- Check logs for token-related messages:
  ```bash
  lando logs -f | grep -i "token"
  ```

#### 5. Lando Startup Issues

```bash
# Restart Lando services
lando restart

# Rebuild containers
lando destroy && lando start

# Check Lando status
lando info

# View Docker container status
docker ps
```

#### 6. Database Connection Issues

```bash
# Test database connection
lando mariadb -e "SELECT 1;"

# View current database
lando mariadb -e "USE zuora_workflows; SHOW TABLES;"

# Check database URL in .env
lando exec appserver cat .env | grep DB_
```

---

## Performance

### Benchmark Results

| Operation                    | Timeout   | Notes                                     |
|------------------------------|-----------|-------------------------------------------|
| Query workflows (DB)         | < 100ms   | Single customer, full table scan          |
| Sync 50 workflows (API + DB) | 300-600ms | Includes API calls and DB inserts/updates |
| Load dashboard UI            | < 1s      | Paginated table with filters              |
| Token generation             | 200-400ms | Cached for 1 hour after first generation  |

### Optimization Tips

1. **Use Queue Worker**: Always process syncs via background jobs, not synchronously
2. **Database Indexes**: Ensure indexes on `customer_id` and `zuora_id` columns
3. **API Rate Limiting**: Monitor Zuora API rate limits; adjust scheduler frequency if needed
4. **Cache Tokens**: 1-hour cache reduces authentication overhead
5. **Batch Operations**: Use `all()` with `each()` for multi-customer syncs

---

## Testing

### Running Tests (with Lando)

```bash
# Run all tests
lando test

# Run specific test file
lando artisan test tests/Feature/SyncWorkflowsTest.php

# Verbose output
lando artisan test -v

# Coverage report
lando artisan test --coverage

# Run tests in parallel
lando artisan test --parallel
```

### Manual Installation Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/SyncWorkflowsTest.php

# With coverage
php artisan test --coverage
```

### Test Structure

```
tests/
├── Feature/
│   └── SyncWorkflowsTest.php      (Integration tests)
├── Unit/
│   ├── ZuoraServiceTest.php       (Service tests)
│   └── WorkflowSyncServiceTest.php (Service tests)
└── TestCase.php                    (Base test class)
```

---

## Contributing

We welcome contributions to improve Zuora Workflow Manager. Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details
on:

- Code standards and conventions
- Pull request process
- Commit message format
- Development workflow

---

## Security

For security vulnerabilities, please see [SECURITY.md](SECURITY.md) for responsible disclosure guidelines.

**Supported Versions**: 0.x and above receive security updates.

---

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

---

## Support & Community

- **Issues**: [GitHub Issues](https://github.com/FrancoStino/zuora-laravel/issues)
- **Discussions**: [GitHub Discussions](https://github.com/FrancoStino/zuora-laravel/discussions)
- **Security**: See [SECURITY.md](SECURITY.md)

---

## Changelog

For detailed release notes and changes, see [CHANGELOG.md](CHANGELOG.md).

---

**Made with ❤️ for Zuora workflow management**
