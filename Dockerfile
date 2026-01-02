FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install mysqli
RUN docker-php-ext-install mysqli

# ✅ CORRECT document root (IMPORTANT)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend/public

# Update Apache configs to use new document root
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Optional: suppress ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy entire project
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
