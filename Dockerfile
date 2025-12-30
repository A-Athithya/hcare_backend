FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Set Apache document root to /var/www/html/hcare/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/hcare/public

# Update Apache config to use new document root
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy project
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
