<div align="center">

# Zuora Workflow Manager

<figure>
  <img src="public/images/zuora-logo-readme.png" alt="Zuora Workflows Logo" width="60%">
</figure><br /><br />

[![PHP Version](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://custom-icon-badges.demolab.com/badge/Filament-4.2-df4090?style=for-the-badge&logo=filament&logoColor=white)](https://filamentphp.com/)
[![Lando](https://custom-icon-badges.demolab.com/badge/Lando-DEV_Environment-df4090?style=for-the-badge&logo=lando&logoColor=white)](https://lando.dev/)
[![MariaDB](https://img.shields.io/badge/MariaDB-11.4-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Redis](https://img.shields.io/badge/Redis-7.0-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io/)
[![Nginx](https://img.shields.io/badge/Nginx-Latest-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org/)
[![License](https://img.shields.io/badge/License-MIT-4CAF50?style=for-the-badge&logoColor=white)](LICENSE)
[![Latest Release](https://img.shields.io/github/v/release/FrancoStino/zuora-workflows?style=for-the-badge&color=2196F3&logoColor=white)](https://github.com/FrancoStino/zuora-workflows/releases)
[![Status](https://img.shields.io/badge/Status-Active-00BCD4?style=for-the-badge&logoColor=white)](https://github.com/FrancoStino/zuora-workflows)

</div>

A powerful web application for synchronizing, viewing, and managing Zuora workflows directly from your Filament admin
dashboard. Built with modern Laravel architecture featuring automated sync jobs, real-time dashboards, and comprehensive
workflow management.

## Table of Contents

- [Features](#features) • [Requirements](#requirements) • [Installation](#installation) • [Configuration](#configuration)
- [Usage](#usage) • [Architecture](#architecture) • [Database Schema](#database-schema)
- [Monitoring](#monitoring--troubleshooting) • [Testing](#testing) • [Contributing](#contributing) • [License](#license)

---

## Features

- 🔄 **Automatic Synchronization**: Hourly sync + manual sync button for immediate updates
- 📊 **Filament Dashboard**: Rich workflow visualization with search, filters, and sorting
- ⚙️ **Background Jobs**: Queue-based processing with retry logic (3 attempts, 60s backoff)
- 🔐 **OAuth 2.0**: Secure token management with 1-hour caching
- 📥 **Workflow Download**: Direct export from Zuora
- 🔒 **RBAC**: Role-based access control via Filament Shield
- 🗄️ **Multi-tenant**: Per-customer Zuora credentials in database

---

## Requirements

| Requirement                       | Version    | Link                                  |
|-----------------------------------|------------|---------------------------------------|
| [Lando](https://lando.dev)        | Latest     | [lando.dev](https://lando.dev)        |
| [Docker](https://www.docker.com/) | 20.0+      | [docker.com](https://www.docker.com/) |
| [Node.js](https://nodejs.org/)    | 20.19+     | [nodejs.org](https://nodejs.org/)     |
| [Yarn](https://yarnpkg.com/)      | Latest     | [yarnpkg.com](https://yarnpkg.com/)   |

**Lando Stack:** PHP 8.4, MariaDB 11.4, Nginx, Redis 7.0, Xdebug

**Key Dependencies:** Laravel 12, Filament 4.2, Filament Shield, Tailwind CSS 4, Vite 7

---

## Installation

### Option A: Using [Lando](https://lando.dev) (Recommended)

[Lando](https://lando.dev) provides a containerized development environment
with [PHP 8.4](https://www.php.net/), [MariaDB 11.4](https://mariadb.org/), [Nginx](https://nginx.org/),
and [Redis](https://redis.io/) pre-configured. It eliminates "works on my machine" problems by
using [Docker](https://www.docker.com/) containers.

**Step 1: Clone the Repository**

```bash
git clone https://github.com/FrancoStino/zuora-workflows.git
cd zuora-workflows
```

**Step 2: Start Lando**

```bash
lando start
```

This will automatically:

- Start PHP 8.4, MariaDB 11.4, Nginx, and Redis containers
- Run `lando composer install`
- Configure the development environment

**Step 3: Setup Environment**

```bash
# Copy environment file
cp .env.example .env

# Generate application key
lando artisan key:generate

# Run migrations
lando artisan migrate

# Install frontend dependencies (use yarn globally, not via lando)
yarn install

# Build frontend assets
yarn run build
```

**Step 4: Access the Dashboard**

Navigate to `https://zuora-workflows.lndo.site` (via Lando's Nginx, not port 8000) and create your admin account.

**Step 5: Configure Customer Zuora Credentials**

After login:

1. Go to **Customers** in the sidebar
2. Create a new customer
3. Enter Zuora API credentials:
    - **Client ID**: Your Zuora OAuth client ID
    - **Client Secret**: Your Zuora OAuth client secret
    - **Base URL**: `https://rest.zuora.com` (or `https://rest.test.zuora.com` for sandbox)
4. Save the customer
5. Click **Sync Workflows** to sync workflows from Zuora

**Quick Commands:**

```bash
lando artisan migrate                # Run migrations
lando artisan queue:work             # Start queue worker (database/redis only)
lando test                           # Run tests
lando logs -f                        # View logs
lando mariadb                        # Database access
```

**URL:** `https://zuora-workflows.lndo.site`

---

## Configuration

### Queue Driver Setup

The application uses the database queue driver by default. The `.env.example` file specifies:

```env
QUEUE_CONNECTION=sync
```

To change to [Redis](https://redis.io/) or another [Laravel](https://laravel.com/docs/queues) queue driver:

```env
QUEUE_CONNECTION=redis  # or sync, sqs, etc.
```

See [Laravel Queue Documentation](https://laravel.com/docs/queues) for more driver options.

### Scheduler Configuration

Workflows sync hourly by default. Modify in `app/Console/Kernel.php`:

```php
->hourly();              // Default
->everyThirtyMinutes();  // Every 30 min
->everyFiveMinutes();    // Every 5 min
```

Start scheduler: `lando artisan schedule:work`

### Zuora API Configuration

Zuora credentials are stored **per-customer in the database**, not in environment variables. This enables multi-tenant
support.

**For each customer:**

1. Login to the admin dashboard
2. Navigate to **Customers**
3. Create/Edit a customer and enter:
    - **Client ID
      **: [Zuora OAuth 2.0 Client ID](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Client_Authentication)
    - **Client Secret
      **: [Zuora OAuth 2.0 Client Secret](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Client_Authentication)
    - **Base URL**: `https://rest.zuora.com` (
      see [Zuora API Endpoints](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#API_Endpoints) -
      Production, Test, or Sandbox)

The application automatically handles:

- [OAuth 2.0](https://tools.ietf.org/html/rfc6749) token generation and caching (1-hour TTL)
- [Paginated requests](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API#Pagination) (max 50
  items per page)
- Error handling and retry logic for failed syncs

---

## Usage

**Web Interface:**

1. Navigate to `https://zuora-workflows.lndo.site`
2. Create customer with Zuora credentials (Client ID, Secret, Base URL)
3. Click **Sync Workflows** button to sync from Zuora
4. View, filter, and search workflows in the table

**CLI Commands:**

```bash
lando artisan app:sync-workflows --customer="Name"  # Sync one
lando artisan app:sync-workflows --all              # Sync all
lando artisan queue:work                            # Start queue worker (database/redis only)
lando artisan queue:failed                          # Check failed jobs (database/redis only)
lando composer run dev                              # Full dev stack
```

---

## Architecture

**Service Layer:**

- `ZuoraService`: OAuth 2.0 authentication, HTTP API calls, token caching
- `WorkflowSyncService`: Orchestrates sync, handles pagination, CRUD operations
- `SyncCustomerWorkflows` Job: Queue-based processing with retry logic

**Flow:** Filament UI → Dispatch Job → WorkflowSyncService → ZuoraService → Zuora REST API

---

## Database Schema

Built on [MariaDB 11.4](https://mariadb.org/) with support
for [foreign key constraints](https://mariadb.com/kb/en/foreign-keys/)
and [indexes](https://mariadb.com/kb/en/create-index/) for optimal query performance.

### Workflows Table

```sql
CREATE TABLE workflows
(
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id    BIGINT UNSIGNED NOT NULL,
    zuora_id       VARCHAR(255) NOT NULL UNIQUE,
    name           VARCHAR(255) NOT NULL,
    description    TEXT,
    state          VARCHAR(255),
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
    id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name          VARCHAR(255) NOT NULL,
    client_id     VARCHAR(255) NOT NULL,
    client_secret VARCHAR(255) NOT NULL,
    base_url      VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX         idx_name (name)
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

Uses [Zuora REST API](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API) for workflow
synchronization.

**Endpoints:**

- `GET /v1/workflows` - List workflows (paginated, default 50 per page)
- `GET /v1/workflows/{id}/export` - Download workflow definition

See [Zuora API Documentation](https://knowledgecenter.zuora.com/Zuora_Central_Platform/API/Zuora_REST_API) for details.

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

### Queue Status (Database/Redis only)

**Check queue health and process jobs** (only when `QUEUE_CONNECTION=database` or `redis`):

```bash
# Start queue worker
lando artisan queue:work --verbose

# Check failed jobs
lando artisan queue:failed

# Retry specific failed job
lando artisan queue:retry {job-id}

# Clear all failed jobs
lando artisan queue:flush
```

**Note:** With `QUEUE_CONNECTION=sync`, jobs execute immediately and queue commands are not needed.

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

For troubleshooting common issues, check the logs:

```bash
# View all logs
lando logs -f

# Check failed jobs
lando artisan queue:failed

# Retry failed jobs
lando artisan queue:retry all
```

**Common problems:**

- **Queue not processing**: Start queue worker with `lando artisan queue:work`
- **Workflows not syncing**: Verify Zuora credentials in customer settings
- **Connection issues**: Check logs with `lando logs -f | grep -i "error"`

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

### Test Structure

```
tests/
├── Feature/
│   ├── ExampleTest.php
│   └── SyncWorkflowsTest.php      (Integration tests)
├── Unit/
│   └── ExampleTest.php
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

## Deployment

For production deployment instructions, including queue worker setup for shared hosting environments, see the [Deployment Guide](docs/DEPLOYMENT.md).

### Quick Setup Options:

**Option A - Sync Queue (Simplest for Shared Hosting):**
1. Deploy using GitHub Actions workflow
2. Set `QUEUE_CONNECTION=sync` in your `.env` file
3. Jobs execute immediately - no additional configuration needed!

**Option B - Database Queue with Cron (For Background Processing):**
1. Deploy using GitHub Actions workflow
2. Set `QUEUE_CONNECTION=database` in your `.env` file
3. Set up a cron job: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
4. The scheduler will handle background job processing automatically

---

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

---

## Support & Community

- **Issues**: [GitHub Issues](https://github.com/FrancoStino/zuora-workflows/issues)
- **Discussions**: [GitHub Discussions](https://github.com/FrancoStino/zuora-workflows/discussions)
- **Security**: See [SECURITY.md](SECURITY.md)

---

## Changelog

For detailed release notes and changes, see [CHANGELOG.md](CHANGELOG.md).

---

**Made with ❤️ for Zuora workflow management**
