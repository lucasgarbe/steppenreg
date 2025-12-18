# Steppenreg

**Open-source event registration and participant management system built with Laravel and Filament.**

---

## Quick Start

### Prerequisites

- **Docker Desktop** (macOS/Windows) or **Docker Engine** (Linux)
- Minimum **4GB RAM** and **5GB disk space**
- Git installed

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/steppenreg.git
cd steppenreg

# 2. Create environment configuration
cp .env.example .env

# 3. Install PHP dependencies (one-time setup)
docker run --rm \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 4. Start Docker containers
./vendor/bin/sail up -d

# 5. Initialize application
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 6. Create your first admin user
./vendor/bin/sail artisan make:filament-user
```

### Access Your Application

- **Public registration site**: http://localhost
- **Admin panel**: http://localhost/admin

---

## Essential Configuration

### Update Your .env File

Configure these settings for your event:

```env
# Application
APP_NAME="Your Event Name"
APP_URL=http://localhost
APP_LOCALE=de                    # de=German, en=English
APP_TIMEZONE=Europe/Berlin

# Database
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_DATABASE=steppenreg
DB_USERNAME=postgres
DB_PASSWORD=password             # CHANGE FOR PRODUCTION

# Email
MAIL_FROM_ADDRESS="registration@yourevent.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Mail Configuration

**For Development (emails written to log file):**

```env
MAIL_MAILER=log
```

**For Production (real SMTP server):**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
```

### Port Conflicts

If port 80 is already in use, change these in `.env`:

```env
APP_PORT=8080                    # Web server port
FORWARD_DB_PORT=54320            # PostgreSQL port
```

Then restart: `./vendor/bin/sail down && ./vendor/bin/sail up -d`

---

## Common Commands

### Container Management

```bash
# Start containers
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Restart containers
./vendor/bin/sail restart

# View running containers
./vendor/bin/sail ps

# View logs
./vendor/bin/sail logs -f
```

### Application Management

```bash
# View application logs
./vendor/bin/sail artisan log:tail

# Run database migrations
./vendor/bin/sail artisan migrate

# Clear application cache
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# Create additional admin users
./vendor/bin/sail artisan make:filament-user
```

### Database Operations

```bash
# Create database backup
./vendor/bin/sail exec pgsql pg_dump -U postgres steppenreg > backup-$(date +%Y%m%d).sql

# Restore database from backup
./vendor/bin/sail exec -T pgsql psql -U postgres steppenreg < backup.sql

# Access PostgreSQL shell
./vendor/bin/sail psql
```

### Development

```bash
# Install new PHP packages
./vendor/bin/sail composer require package/name

# Install Node dependencies
./vendor/bin/sail npm install

# Build assets for production
./vendor/bin/sail npm run build

# Watch assets during development
./vendor/bin/sail npm run dev
```

### Shell Alias (Optional)

Add to `~/.zshrc` or `~/.bashrc`:

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Then use shorter commands: `sail up`, `sail artisan migrate`, etc.

---

## Using the Admin Panel

Access the admin panel at http://localhost/admin with your created admin credentials.

### Main Features

1. **Registrations** - View, edit, and manage all participant registrations
2. **Teams** - Manage team registrations and membership
3. **Draw Management** - Run the lottery system and manage results
4. **Waitlist** - Handle waitlisted participants and slot reassignment
5. **Mail Templates** - Create and customize email communications
6. **Settings** - Configure event details and system preferences

### Common Admin Tasks

- **Export registrations**: Navigate to Registrations, use the Export button
- **Run the draw**: Go to Draw Management, configure parameters, click Start Draw
- **Send bulk emails**: Use Mail Templates to compose and send to participant groups
- **Manage starting numbers**: Automatically assigned after draw completion
- **Handle withdrawals**: Process withdrawal requests from the Registrations view

---

## Production Deployment

### Security Checklist

Before deploying to production:

- [ ] Change `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Update `APP_URL` to your actual domain
- [ ] Generate strong `APP_KEY` (done automatically with `key:generate`)
- [ ] Use a strong random `DB_PASSWORD`
- [ ] Configure real SMTP mail settings with valid credentials
- [ ] Set up SSL/TLS certificates (use Let's Encrypt)
- [ ] Configure a reverse proxy (Nginx or Traefik)
- [ ] Set up automated database backups
- [ ] Configure monitoring and error logging
- [ ] Restrict file permissions appropriately

### Production Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://registration.yourevent.com

DB_PASSWORD=<strong-random-password>

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### Performance Optimization

Run these commands after deployment:

```bash
# Cache configuration files
./vendor/bin/sail artisan config:cache

# Cache routes
./vendor/bin/sail artisan route:cache

# Cache views
./vendor/bin/sail artisan view:cache

# Optimize Composer autoloader
./vendor/bin/sail composer install --optimize-autoloader --no-dev
```

### Reverse Proxy Setup

For production, use a reverse proxy like Nginx or Traefik in front of Laravel Sail to handle SSL/TLS termination and provide better performance.

Example Nginx configuration:

```nginx
server {
    listen 80;
    server_name registration.yourevent.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
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

### Backup Strategy

Set up automated backups for:

1. **Database** - Daily PostgreSQL dumps
2. **Storage directory** - Files uploaded through the application
3. **Environment file** - Secure backup of `.env`

Example backup script:

```bash
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DATE=$(date +"%Y%m%d_%H%M%S")

# Backup database
./vendor/bin/sail exec pgsql pg_dump -U postgres steppenreg | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup storage
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" storage/

# Keep only last 30 days
find "$BACKUP_DIR" -name "*.gz" -mtime +30 -delete
```

Run via cron: `0 2 * * * /path/to/backup.sh`

---

## Troubleshooting

### Port Already in Use

**Error**: `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solution**: Change ports in `.env`:

```env
APP_PORT=8080
```

Then restart: `./vendor/bin/sail down && ./vendor/bin/sail up -d`

### Permission Errors

**Error**: Permission denied errors when writing to storage or cache

**Solution**:

```bash
chmod -R 775 storage bootstrap/cache
./vendor/bin/sail down
./vendor/bin/sail up -d
```

### Database Connection Failed

**Error**: `SQLSTATE[08006] [7] connection refused`

**Solution**:

```bash
# Check if database container is running
./vendor/bin/sail ps

# Test database connection
./vendor/bin/sail exec pgsql pg_isready -U postgres

# Restart containers
./vendor/bin/sail restart
```

### Container Won't Start

**Solution**:

```bash
# Check logs for error messages
./vendor/bin/sail logs

# Rebuild containers from scratch
./vendor/bin/sail down -v
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

### Composer Dependencies Missing

**Error**: `vendor/autoload.php not found`

**Solution**: Reinstall dependencies:

```bash
docker run --rm \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### Frontend Assets Not Loading

**Solution**:

```bash
# Rebuild frontend assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Clear browser cache and reload
```

---

## Customization

### Changing Language

Edit `.env`:

```env
APP_LOCALE=de    # Options: de (German), en (English)
```

Then clear cache: `./vendor/bin/sail artisan config:clear`

See **LOCALIZATION.md** for details on adding additional languages.

---

## Additional Documentation

- **DEPLOYMENT.md** - Detailed deployment guide and advanced configuration
- **LOCALIZATION.md** - Translation and multi-language setup guide
