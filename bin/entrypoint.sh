#!/bin/sh
# Arranque del contenedor: migraciones -> (seed demo) -> Apache.
set -e

export PORT="${PORT:-8080}"

php bin/migrate.php

if [ "$SEED_DEMO" = "true" ]; then
    php bin/seed.php
fi

# mod_php necesita el MPM prefork: Apache no arranca si hay otro MPM habilitado.
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null

# Si la configuración de Apache no es válida, deja en el log qué la rompe.
if ! apache2ctl configtest; then
    echo "--- mods-enabled (mpm):"; ls -la /etc/apache2/mods-enabled | grep -i mpm || true
    echo "--- LoadModule mpm:"; grep -rn "mpm_" /etc/apache2 --include=*.conf --include=*.load 2>/dev/null | grep -i loadmodule || true
    exit 1
fi

exec apache2-foreground
