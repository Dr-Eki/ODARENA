FROM php:7.4.33-fpm
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
    libmemcached-dev \
    libmemcached11 \
    libmemcachedutil2 
    #supervisor

# Clear Cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install sockets pdo_mysql intl mbstring exif pcntl bcmath gd zip
RUN pecl install memcached && docker-php-ext-enable memcached

#Install Node Js and Npm
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get install -y nodejs

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www
RUN mkdir /var/www/html/storage
RUN chown -R www-data:www-data /var/www

# Switch to the non-root user
USER $user