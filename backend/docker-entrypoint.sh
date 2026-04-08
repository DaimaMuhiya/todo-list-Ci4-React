#!/bin/sh
set -e
PORT="${PORT:-10000}"

printf 'Listen %s\n' "$PORT" >/etc/apache2/ports.conf

cat >/etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    ServerName localhost
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
EOF

exec apache2-foreground
