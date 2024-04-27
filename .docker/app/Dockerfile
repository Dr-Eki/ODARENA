FROM php:8.3-fpm
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    zlib1g-dev \
    nano \
    cron \
    screen

# Clear Cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install sockets pdo_mysql intl mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Supervisord
RUN apt-get update && apt-get install -y supervisor

#Install Node Js and Npm
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get install -y nodejs

# Install procps
RUN apt-get update && apt-get install -y procps

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user

# Create log directories for PHP-FPM
RUN mkdir -p /var/log/php-fpm && chown -R www-data:www-data /var/log/php-fpm

# Set working directory
WORKDIR /var/www
RUN mkdir /var/www/html/storage
RUN chown -R www-data:www-data /var/www

RUN mkdir -p /var/log/supervisor && \
    chmod -R 755 /var/log/supervisor && \
    chown -R www-data:www-data /var/log/supervisor

# Copy Supervisord configuration file
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set Supervisord as the entry point or command, which will manage starting PHP-FPM
# CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

USER $user