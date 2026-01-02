FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install mysqli
RUN docker-php-ext-install mysqli

# Set document root to /hcare/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/hcare/public

# Update Apache configs to use new doc root
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# IMPORTANT: Allow .htaccess overrides
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy project
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
