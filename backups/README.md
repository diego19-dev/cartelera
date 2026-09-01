# Backups CSET

Snapshots y restos. No servir por HTTP.
Uploads runtime: `public/assets/uploads/`.

## Apache

Plantilla: `bash/apache-cset-alias.conf` → `/cset` = `src/public`.
Si aún no hay sudo: puede existir symlink temporal `public -> src/public` en la raíz.
Tras `sudo bash bash/apply-apache-alias.sh`, eliminar el symlink: `rm -f /var/www/html/cset/public`.
