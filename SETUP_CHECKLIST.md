# Steppenreg Setup Checklist

Use this checklist when deploying Steppenreg for your event.

## Pre-Deployment Checklist

### 1. System Requirements
- [ ] Docker Desktop installed (macOS/Windows) or Docker Engine (Linux)
- [ ] Git installed
- [ ] At least 4GB RAM available
- [ ] At least 5GB disk space available
- [ ] Port 80 available (or choose alternative in `.env`)

### 2. Repository Setup
- [ ] Repository cloned: `git clone <repo-url>`
- [ ] Navigate to project directory: `cd steppenreg`
- [ ] Copy environment file: `cp .env.example .env`

### 3. Environment Configuration

#### Required Settings
- [ ] `APP_NAME` - Set to your event name
- [ ] `APP_URL` - Set to your domain (production) or `http://localhost` (local)
- [ ] `APP_LOCALE` - Set to `de` (German) or `en` (English)
- [ ] `APP_TIMEZONE` - Set to your timezone (e.g., `Europe/Berlin`)

#### Database Configuration
- [ ] `DB_DATABASE` - Choose a database name (default: `steppenreg`)
- [ ] `DB_USERNAME` - Default is `postgres`
- [ ] `DB_PASSWORD` - **MUST CHANGE for production!**

#### Mail Configuration
- [ ] `MAIL_FROM_ADDRESS` - Your event's email address
- [ ] `MAIL_FROM_NAME` - Sender name for emails
- [ ] For production: Configure SMTP settings (see DEPLOYMENT.md)

### 4. Installation

#### Dependency Installation
- [ ] Install Composer dependencies (see command below)
- [ ] Start Docker containers: `./vendor/bin/sail up -d`
- [ ] Generate app key: `./vendor/bin/sail artisan key:generate`
- [ ] Run migrations: `./vendor/bin/sail artisan migrate`
- [ ] Install npm dependencies: `./vendor/bin/sail npm install`
- [ ] Build assets: `./vendor/bin/sail npm run build`

**Composer install command:**
```bash
docker run --rm \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```
- [ ] Fix permissions: `chmod -R 775 storage bootstrap/cache`

### 5. Initial Configuration

- [ ] Create admin user: `./vendor/bin/sail artisan make:filament-user`
- [ ] Access admin panel: `http://localhost/admin`
- [ ] Test that you can login
- [ ] Verify email settings (check logs with: `./vendor/bin/sail artisan log:tail`)

---

## Production Deployment Checklist

### Security
- [ ] Change `APP_ENV=production` in `.env`
- [ ] Change `APP_DEBUG=false` in `.env`
- [ ] Set strong `DB_PASSWORD`
- [ ] Set strong password for admin user
- [ ] Configure proper SMTP mail server
- [ ] Set up SSL/TLS certificates
- [ ] Configure firewall rules
- [ ] Review file permissions

### Performance
- [ ] Run: `./vendor/bin/sail artisan config:cache`
- [ ] Run: `./vendor/bin/sail artisan route:cache`
- [ ] Run: `./vendor/bin/sail artisan view:cache`
- [ ] Run: `./vendor/bin/sail composer install --optimize-autoloader --no-dev`

### Backup & Monitoring
- [ ] Set up automated database backups
- [ ] Set up automated file backups (`storage/` directory)
- [ ] Configure monitoring (Laravel Pulse included)
- [ ] Set up error logging/alerting
- [ ] Document backup restoration procedure

### Testing
- [ ] Test participant registration flow
- [ ] Test admin panel access
- [ ] Test email delivery
- [ ] Test with different browsers
- [ ] Test on mobile devices
- [ ] Verify all features work correctly

---

## Post-Deployment Checklist

### Documentation
- [ ] Document your specific event configuration
- [ ] Document any custom features added
- [ ] Create backup/restore procedures document
- [ ] Document admin user management

### Team Training
- [ ] Train staff on admin panel usage
- [ ] Document common administrative tasks
- [ ] Create troubleshooting guide for your team
- [ ] Establish support contact procedures

### Monitoring
- [ ] Verify backups are running
- [ ] Check disk space regularly
- [ ] Monitor application logs
- [ ] Monitor email delivery
- [ ] Set up uptime monitoring

---

## Troubleshooting Checklist

If something goes wrong, check:

- [ ] Are containers running? `./vendor/bin/sail ps`
- [ ] Check logs: `./vendor/bin/sail logs`
- [ ] Check application logs: `./vendor/bin/sail artisan log:tail`
- [ ] Verify `.env` settings
- [ ] Check permissions: `ls -la storage bootstrap/cache`
- [ ] Verify database connection: `./vendor/bin/sail artisan db:show`
- [ ] Check disk space: `df -h`
- [ ] Rebuild containers: `./vendor/bin/sail build --no-cache`

---

## Common Issues and Solutions

### "Permission denied" errors
```bash
chmod -R 775 storage bootstrap/cache
./vendor/bin/sail down
./vendor/bin/sail up -d
```

### "Port already in use"
Change port in `.env`:
```bash
APP_PORT=8080
```

### "Database connection failed"
Check `.env` database settings match `docker-compose.yml`

### Emails not sending
For development, check logs:
```bash
./vendor/bin/sail artisan log:tail
```
For production, verify SMTP settings in `.env`

---

## Quick Reference Commands

```bash
# Start system
./vendor/bin/sail up -d

# Stop system
./vendor/bin/sail down

# View logs
./vendor/bin/sail logs -f

# Run migrations
./vendor/bin/sail artisan migrate

# Create admin user
./vendor/bin/sail artisan make:filament-user

# Clear cache
./vendor/bin/sail artisan cache:clear

# Backup database
./vendor/bin/sail exec pgsql pg_dump -U postgres steppenreg > backup-$(date +%Y%m%d).sql
```
