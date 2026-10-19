#!/bin/sh
set -eu

php /var/www/html/scripts/migrate.php
exec apache2-foreground
