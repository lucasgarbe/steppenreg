# Steppenreg Deployment Guide

This guide helps you deploy Steppenreg for your event using Docker containers.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start (5 Minutes)](#quick-start-5-minutes)
- [Detailed Setup](#detailed-setup)
- [Configuration](#configuration)
- [Common Tasks](#common-tasks)
- [Troubleshooting](#troubleshooting)
- [Production Deployment](#production-deployment)

---

## Prerequisites

Before you begin, ensure you have the following installed on your server:

- **Docker Desktop** (macOS/Windows) or **Docker Engine** (Linux)
  - Download: https://www.docker.com/products/docker-desktop
- **Git** (to clone the repository)
- **Composer** (for dependency installation)

### System Requirements

- **RAM**: Minimum 2GB, recommended 4GB+
- **Disk Space**: Minimum 5GB free
- **OS**: macOS, Linux, or Windows 10/11 with WSL2

---

## Quick Start

Get Steppenreg running with these commands:

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/steppenreg.git
cd steppenreg

# 2. Copy environment configuration
cp .env.example .env

# 3. Install PHP dependencies (if not already done)
docker run --rm \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 4. Fix permissions
chmod -R 775 storage bootstrap/cache

# 5. Start Docker containers
./vendor/bin/sail up -d

# 6. Generate application key
./vendor/bin/sail artisan key:generate

# 7. Run database migrations
./vendor/bin/sail artisan migrate --seed

# 8. Install and build frontend assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 9. Access your application
# Open http://localhost in your browser
```

**Done!** Your registration system is now running.

---

## Detailed Setup

### Step 1: Configure Environment Variables

Edit the `.env` file and update these critical settings:

#### Application Settings

```bash
APP_NAME="Your Event Name"
APP_URL=http://localhost  # Update for production
APP_TIMEZONE=Europe/Berlin  # Your event timezone
APP_LOCALE=de  # de=German, en=English
```

#### Database Settings

```bash
DB_CONNECTION=pgsql
DB_HOST=pgsql  # Docker service name (don't change)
DB_PORT=5432
DB_DATABASE=steppenreg  # You can customize this
DB_USERNAME=postgres
DB_PASSWORD=password  # CHANGE THIS IN PRODUCTION!
```

#### Mail Settings

```bash
MAIL_FROM_ADDRESS="registration@yourevent.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**For development:**
```bash
MAIL_MAILER=log  # Emails saved to storage/logs/laravel.log
```

**For production with SMTP:**
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
```

### Step 2: Build and Start Containers

```bash
# Start all containers in the background
./vendor/bin/sail up -d

# View logs (optional)
./vendor/bin/sail logs -f

# Stop containers
./vendor/bin/sail down
```

### Step 3: Initialize the Application

```bash
# Generate application encryption key
./vendor/bin/sail artisan key:generate

# Run database migrations
./vendor/bin/sail artisan migrate

# Optionally seed with sample data
./vendor/bin/sail artisan db:seed

# Create an admin user
./vendor/bin/sail artisan make:filament-user
```

### Step 4: Build Frontend Assets

```bash
# Install Node dependencies
./vendor/bin/sail npm install

# Build assets for production
./vendor/bin/sail npm run build

# Or run development server with hot reload
./vendor/bin/sail npm run dev
```

---

## Configuration

### Port Conflicts

If ports 80, 5173, or 5432 are already in use, change them in `.env`:

```bash
APP_PORT=8080          # Web server port (default: 80)
VITE_PORT=5174         # Vite dev server (default: 5173)
FORWARD_DB_PORT=54320  # PostgreSQL port (default: 5432)
```

Then restart:
```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

### Shell Alias (Recommended)

Add this to `~/.zshrc` or `~/.bashrc`:

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Then restart your terminal and use:
```bash
sail up -d
sail artisan migrate
sail npm run dev
```

---

## Common Tasks

### Access the Admin Panel

1. Create an admin user:
   ```bash
   ./vendor/bin/sail artisan make:filament-user
   ```

2. Visit: http://localhost/admin
3. Login with the credentials you just created

### View Logs

```bash
# Application logs
./vendor/bin/sail artisan log:tail

# Container logs
./vendor/bin/sail logs

# Specific service logs
./vendor/bin/sail logs pgsql
```

### Run Artisan Commands

```bash
./vendor/bin/sail artisan <command>

# Examples:
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan queue:work
./vendor/bin/sail artisan cache:clear
```

### Database Access

#### Using a GUI Client (TablePlus, pgAdmin, etc.)

- **Host**: `localhost`
- **Port**: `5432` (or your `FORWARD_DB_PORT`)
- **Database**: Value from `DB_DATABASE`
- **Username**: Value from `DB_USERNAME`
- **Password**: Value from `DB_PASSWORD`

#### Using Command Line

```bash
# Enter PostgreSQL shell
./vendor/bin/sail psql

# Run SQL directly
./vendor/bin/sail psql -c "SELECT * FROM registrations;"
```

### Queue Workers

If using queued jobs (recommended for email sending):

```bash
# Run queue worker
./vendor/bin/sail artisan queue:work

# Run as a background process
./vendor/bin/sail artisan queue:work --daemon
```

### Backup Database

```bash
# Create backup
./vendor/bin/sail exec pgsql pg_dump -U postgres steppenreg > backup-$(date +%Y%m%d).sql

# Restore from backup
./vendor/bin/sail exec -T pgsql psql -U postgres steppenreg < backup-20250101.sql
```

---

## Troubleshooting

### Permission Denied Errors

**Error:** `There is no existing directory at "/var/www/html/storage/logs" and it could not be created: Permission denied`

**Solution**:

1. Fix permissions:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

2. Restart containers:
   ```bash
   ./vendor/bin/sail down
   ./vendor/bin/sail up -d
   ```

### Port Already in Use

**Error:** `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solution:** Change the port in `.env`:
```bash
APP_PORT=8080
```

Then restart Sail:
```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

### Database Connection Failed

**Error:** `SQLSTATE[08006] [7] connection refused`

**Solution:**
1. Ensure containers are running:
   ```bash
   ./vendor/bin/sail ps
   ```

2. Check database is healthy:
   ```bash
   ./vendor/bin/sail exec pgsql pg_isready -U postgres
   ```

3. Verify `.env` database settings match `docker-compose.yml`

### Composer Dependencies Not Found

**Error:** `vendor/autoload.php not found`

**Solution**: Install dependencies:
```bash
docker run --rm \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### Container Won't Start

Check logs for errors:
```bash
./vendor/bin/sail logs

# Rebuild from scratch
./vendor/bin/sail down -v  # Warning: deletes database!
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

---

## Production Deployment

### Security Checklist

Before deploying to production:

- [ ] Change `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Update `APP_URL` to your domain
- [ ] Generate strong `APP_KEY`
- [ ] Use a strong `DB_PASSWORD`
- [ ] Configure proper SMTP mail settings
- [ ] Set up SSL/TLS certificates (use Traefik, Nginx Proxy Manager, or similar)
- [ ] Configure backups (database and `storage/` directory)
- [ ] Set up monitoring (Laravel Pulse is included)
- [ ] Review and restrict file permissions

### Production Environment Variables

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://registration.yourevent.com

DB_PASSWORD=use-a-strong-random-password-here

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="registration@yourevent.com"
```

### Optimization Commands

```bash
# Cache configuration
./vendor/bin/sail artisan config:cache

# Cache routes
./vendor/bin/sail artisan route:cache

# Cache views
./vendor/bin/sail artisan view:cache

# Optimize autoloader
./vendor/bin/sail composer install --optimize-autoloader --no-dev
```

### Reverse Proxy Setup

For production, you'll typically want:

1. **Nginx/Traefik** as a reverse proxy in front of Sail
2. **SSL/TLS certificates** (Let's Encrypt via Certbot)
3. **Proper firewall rules**

Example Nginx configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name registration.yourevent.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name registration.yourevent.com;

    ssl_certificate /etc/letsencrypt/live/registration.yourevent.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/registration.yourevent.com/privkey.pem;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Automated Backups

Create a backup script (`backup.sh`):

```bash
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/path/to/backups"

# Backup database
./vendor/bin/sail exec pgsql pg_dump -U postgres steppenreg | gzip > "$BACKUP_DIR/db_$TIMESTAMP.sql.gz"

# Backup storage directory
tar -czf "$BACKUP_DIR/storage_$TIMESTAMP.tar.gz" storage/

# Keep only last 30 days of backups
find "$BACKUP_DIR" -name "*.gz" -mtime +30 -delete

echo "Backup completed: $TIMESTAMP"
```

Run via cron:
```bash
0 2 * * * /path/to/steppenreg/backup.sh >> /var/log/steppenreg-backup.log 2>&1
```

