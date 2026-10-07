FROM php:8.3-apache

# Folder for the JSON data, outside the web root, writable by Apache
RUN mkdir -p /var/www/data && chown www-data:www-data /var/www/data

COPY src/ /var/www/html/

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD curl -fs http://localhost/health.php || exit 1
