#!/bin/sh
set -e

# Run migrations as www-data so sqlite file has correct ownership
su-exec www-data php /var/www/database/migrate.php

exec "$@"