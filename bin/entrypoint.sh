#!/bin/sh
# Arranque del contenedor: migraciones -> (seed demo) -> Apache.
set -e

export PORT="${PORT:-8080}"

php bin/migrate.php

if [ "$SEED_DEMO" = "true" ]; then
    php bin/seed.php
fi

exec apache2-foreground
