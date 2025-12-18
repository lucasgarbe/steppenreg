#!/bin/bash

###############################################################################
# Fix Docker Permission Issues
# Run this script if you encounter permission errors with Laravel Sail
###############################################################################

set -e

echo "Fixing Docker permissions for Steppenreg..."

# Get user and group IDs
USER_ID=$(id -u)
GROUP_ID=$(id -g)

echo "Your user ID: $USER_ID"
echo "Your group ID: $GROUP_ID"

# Update .env file
if [ -f .env ]; then
    echo "Updating WWWUSER and WWWGROUP in .env..."
    
    if grep -q "WWWUSER=" .env; then
        if [[ "$OSTYPE" == "darwin"* ]]; then
            sed -i '' "s/WWWUSER=.*/WWWUSER=$USER_ID/" .env
            sed -i '' "s/WWWGROUP=.*/WWWGROUP=$GROUP_ID/" .env
        else
            sed -i "s/WWWUSER=.*/WWWUSER=$USER_ID/" .env
            sed -i "s/WWWGROUP=.*/WWWGROUP=$GROUP_ID/" .env
        fi
    else
        echo "WWWUSER=$USER_ID" >> .env
        echo "WWWGROUP=$GROUP_ID" >> .env
    fi
    
    echo "✓ Updated .env"
else
    echo "Error: .env file not found. Run: cp .env.example .env"
    exit 1
fi

# Fix directory permissions
echo "Fixing directory permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R "$USER_ID:$GROUP_ID" storage bootstrap/cache 2>/dev/null || true
echo "✓ Permissions fixed"

# Rebuild and restart containers
echo "Rebuilding Docker containers..."
./vendor/bin/sail down
./vendor/bin/sail build --no-cache
echo "✓ Containers rebuilt"

echo "Starting containers..."
./vendor/bin/sail up -d
echo "✓ Containers started"

echo ""
echo "✅ Permission fix complete!"
echo ""
echo "You can now run:"
echo "  ./vendor/bin/sail artisan migrate"
echo ""
