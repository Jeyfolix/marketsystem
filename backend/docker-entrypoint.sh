#!/bin/sh
set -e

# Wait for database to be ready (if using local DB, but for TiDB we don't need to wait)
echo "Starting Apache with PHP..."

# Replace environment variables in PHP files if needed
# This is optional - you can also use getenv() in PHP

# Start Apache
exec apache2-foreground
