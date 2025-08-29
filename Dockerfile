FROM trafex/php-nginx:latest

# Switch to root to install packages
USER root

# Install additional PHP extensions needed for Laravel
RUN apk add --no-cache \
    php84-iconv

# Install composer from the official image
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Configure Nginx to serve from the public directory
RUN sed -i 's|root /var/www/html;|root /var/www/html/public;|' /etc/nginx/conf.d/default.conf

# Switch back to nobody user
USER nobody

# Copy application files
COPY --chown=nobody:nobody . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Run composer install to install the dependencies
RUN composer install --optimize-autoloader --no-interaction --no-progress

# Expose port 8080
EXPOSE 8080
