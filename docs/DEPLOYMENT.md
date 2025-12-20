# Deployment Guide

## Overview

This guide covers deploying the Zuora Workflow application to production environments, with special focus on shared hosting environments where `sudo` access is not available.

## Deployment Workflow

The application uses GitHub Actions for automated deployment. The workflow is defined in `.github/workflows/deploy.yml`.

### Manual Deployment Trigger

To deploy manually:

1. Go to the **Actions** tab in your GitHub repository
2. Select **Deploy to Production**
3. Click **Run workflow**
4. Configure the deployment options:
   - **Application Environment**: `production` or `staging`
   - **Enable Debug Mode**: `false` for production, `true` for debugging

### Deployment Steps

The GitHub Actions workflow performs the following steps:

1. **Checkout code** from the repository
2. **Setup PHP 8.4** with required extensions
3. **Setup Node.js 23** for asset compilation
4. **Install Composer dependencies** (production mode)
5. **Install Yarn dependencies**
6. **Build assets** using Vite
7. **Create .env file** with production configuration
8. **Deploy files via SCP** to the production server
9. **Run post-deployment commands** via SSH:
   - Set correct permissions
   - Run fresh migrations
   - Clear and cache configurations
   - Link storage
   - Optimize application

## Queue Worker Setup

The application uses Laravel's queue system with **database driver** to process background jobs asynchronously. The deployment workflow automatically configures `QUEUE_CONNECTION=database`.

### Recommended Setup: Laravel Scheduler with Cron (Works on All Environments)

**✅ This is the simplest and most reliable option for shared hosting.**

Set up a single cron job to run Laravel's scheduler every minute. The scheduler automatically handles:
- Processing queued jobs (every minute)
- Automatic workflow synchronization (configurable: hourly, daily, etc.)
- No need for persistent worker processes

**Requirements:**
- `QUEUE_CONNECTION=database` (automatically configured by deployment workflow)
- Cron job access in your hosting control panel (cPanel, Plesk, DirectAdmin, etc.)

#### Setup Instructions:

1. **Add this single cron job** via your hosting control panel:

```bash
* * * * * cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Replace:
- `YOUR_USERNAME` with your SSH username
- `YOUR_DOMAIN` with your domain name

**For the deployed application:**
```bash
* * * * * cd /home/your-username/domains/zuora.workflows.davideladisa.it/public_html && php artisan schedule:run >> /dev/null 2>&1
```

2. **That's it!** The scheduler automatically handles:
   - ✅ Processing all queued jobs (every minute)
   - ✅ Automatic workflow synchronization (configurable frequency in `routes/console.php`)
   - ✅ Task extraction from workflows
   - ✅ Job retries and failure handling

#### How It Works:

The scheduler (defined in `routes/console.php` for Laravel 12) runs:
1. **Queue processor**: `queue:work --stop-when-empty` every minute
2. **Workflow sync**: At configured intervals (default: hourly, commented out by default)

**To enable automatic workflow sync**, edit `routes/console.php`:
```php
// Uncomment to enable automatic sync
Schedule::command('app:sync-workflows --all')
    ->hourly()  // or ->daily(), ->everyFiveMinutes(), etc.
    ->name('sync-customer-workflows');
```

#### Verification:

```bash
# Check scheduled tasks
php artisan schedule:list

# Should show:
# * * * * *  php artisan queue:work --stop-when-empty --max-jobs=50
# [and sync-customer-workflows if enabled]

# Manually trigger scheduler (for testing)
php artisan schedule:run

# View queued jobs
php artisan queue:failed
```

#### Monitoring:

Access **Moox Jobs** in the Filament admin panel (Jobs menu) to monitor:
- Running jobs
- Waiting jobs
- Failed jobs (with retry option)
- Job batches

**Benefits:**
- ✅ Works on all shared hosting environments (no sudo required)
- ✅ Only one cron job needed
- ✅ Automatically processes jobs without persistent workers
- ✅ Built-in retry logic and failure handling
- ✅ Real-time monitoring via Filament UI

### Option 2: Sync Queue (Not Recommended - No Background Processing)

If you prefer simplicity over background processing, you can use the `sync` queue driver which processes jobs immediately. This is a valid option for shared hosting environments where setting up cron jobs or Supervisor is not feasible.

Update your `.env` file:

```env
QUEUE_CONNECTION=sync
```

**Pros:**
- ✅ No additional configuration required (no cron jobs or Supervisor needed)
- ✅ Jobs execute immediately with instant feedback
- ✅ Simpler deployment and maintenance
- ✅ Works perfectly in shared hosting environments
- ✅ No risk of queued jobs not being processed

**Cons:**
- ⚠️ Synchronization operations will block the user's request until completion
- ⚠️ May cause timeouts if operations take too long
- ⚠️ Not ideal for high-traffic applications or long-running jobs
- ⚠️ The hourly scheduler will run all customer syncs sequentially

**Recommended for:**
- ⚠️ Only if cron job access is absolutely not available
- Development/testing environments

**Note:** The deployment workflow now uses `QUEUE_CONNECTION=database` by default. Use Option 1 (Scheduler + Cron) for production.

### Option 3: Supervisor (For VPS/Dedicated Servers)

If you have full server access with `sudo`, you can set up a persistent queue worker using Supervisor.

**Requirements:**
- `QUEUE_CONNECTION=database` in `.env`
- Root/sudo access to the server
- Supervisor installed

#### Install Supervisor:

```bash
sudo apt-get install supervisor
```

#### Create Supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/zuora-workflow-queue.conf
```

Add this configuration:

```ini
[program:zuora-workflow-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/application/artisan queue:work database --sleep=3 --tries=3 --timeout=90 --memory=256
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/zuora-workflow-queue.log
stopwaitsecs=3600
```

#### Start the queue worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start zuora-workflow-queue:*
```

#### Monitor the queue worker:

```bash
sudo supervisorctl status zuora-workflow-queue:*
```

## Queue Monitoring

### Check Queue Status

```bash
# View pending jobs (only for database/redis queue)
php artisan queue:monitor database

# View failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush
```

### Database Queries

You can also check the queue status directly in the database (only for database queue):

```sql
-- View pending jobs
SELECT * FROM jobs;

-- View failed jobs
SELECT * FROM failed_jobs;
```

## Scheduled Tasks

The application schedules the following tasks (configured in `app/Console/Kernel.php`):

- **Workflow Sync**: Runs every hour to sync workflows for all customers

### How Scheduled Tasks Work

**With database queue + cron job (Option 1):**
1. The cron job runs `php artisan schedule:run` every minute
2. Laravel checks which scheduled tasks are due
3. For workflow sync, it dispatches a `SyncCustomerWorkflows` job for each customer
4. Jobs are added to the queue and processed asynchronously

**With sync queue (Option 2):**
1. The scheduled task runs directly when triggered (manually or via cron if set up)
2. Each customer sync executes immediately and sequentially
3. No background processing - all happens in the foreground

**With Supervisor (Option 3):**
1. Similar to Option 1 but with a dedicated queue worker process
2. More reliable for high-traffic production environments

## Environment Configuration

### Required Environment Variables

The following variables are set during deployment:

```env
APP_NAME='Zuora Workflow'
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=sync  # or 'database' depending on your choice

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

### Queue Configuration Options

Choose the queue connection that best fits your hosting environment:

```env
# Use sync queue (immediate processing, no background jobs)
QUEUE_CONNECTION=sync

# Use database queue (requires cron job or Supervisor)
QUEUE_CONNECTION=database

# Use Redis (requires Redis server and cron job or Supervisor)
QUEUE_CONNECTION=redis
```

## File Permissions

The deployment script sets the following permissions:

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;
```

If you encounter permission issues, run these commands manually via SSH.

## Troubleshooting

### Deployment Fails with "sudo: command not found"

This error occurs when the deployment script tries to use `sudo` in a shared hosting environment. The latest version of the deployment workflow has removed all `sudo` commands. Make sure you're using the updated `.github/workflows/deploy.yml`.

### Queue Jobs Not Processing (Database Queue Only)

1. **Check if cron job is running:**
   ```bash
   crontab -l
   ```

2. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Manually run the scheduler:**
   ```bash
   php artisan schedule:run
   ```

4. **Check failed jobs:**
   ```bash
   php artisan queue:failed
   ```

### Sync Operations Taking Too Long (Sync Queue)

If you're using `QUEUE_CONNECTION=sync` and experiencing timeouts:

1. **Check your web server timeout settings** (Apache/Nginx)
2. **Consider switching to database queue** with cron job (Option 1)
3. **Reduce the frequency** of scheduled syncs in `app/Console/Kernel.php`
4. **Optimize the sync operations** to be faster

### Migration Fails

If migrations fail during deployment:

1. Check database connection in `.env`
2. Verify database credentials
3. Check database server status
4. Review migration error in deployment logs

### Permission Denied Errors

If you get permission denied errors:

1. SSH into your server
2. Navigate to your application directory
3. Run the permission commands manually (see File Permissions section above)

## Security Considerations

### Production Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials stored in GitHub Secrets
- [ ] SSH keys secured and rotated regularly
- [ ] HTTPS enabled with valid SSL certificate
- [ ] File permissions properly set
- [ ] Queue worker running (if using database queue)
- [ ] Error logs monitored regularly

### GitHub Secrets

The following secrets must be configured in your GitHub repository:

- `APP_KEY`: Laravel application key
- `SSH_HOST`: Production server hostname/IP
- `SSH_USERNAME`: SSH username
- `SSH_PRIVATE_KEY`: SSH private key for authentication
- `DB_HOST`: Database host
- `DB_PORT`: Database port
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `GOOGLE_CLIENT_ID`: Google OAuth client ID
- `GOOGLE_CLIENT_SECRET`: Google OAuth client secret

## Monitoring

### Application Health

After deployment, verify the application is running:

```bash
php artisan about --only=environment
```

### Queue Health (Database Queue Only)

Monitor queue performance:

```bash
# Check queue size
php artisan queue:monitor database --max=100

# View recent jobs
php artisan queue:failed

# Check scheduler tasks
php artisan schedule:list
```

## Rollback

If you need to rollback a deployment:

1. Identify the previous working commit
2. Trigger a new deployment workflow with that commit
3. Or manually SSH into the server and restore from backup

## Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
