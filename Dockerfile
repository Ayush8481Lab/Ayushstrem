# Use the official PHP image with Apache web server
FROM php:8.2-apache

# Install necessary system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (often required for PHP routing)
RUN a2enmod rewrite

# Copy the entire TS-JioTV repository into the Apache web root
COPY . /var/www/html/

# Set proper read/write permissions so the PHP script can save OTP tokens/configs
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

# Expose port 80. 
# Render automatically detects EXPOSE 80 and routes external web traffic to it.
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
