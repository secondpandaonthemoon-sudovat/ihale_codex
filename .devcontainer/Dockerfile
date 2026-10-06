FROM php:8.4-cli-bookworm
RUN sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list.d/debian.sources \
    && apt-get update \
    && apt-get install -y --no-install-recommends git curl libonig-dev libzip-dev libxml2-dev \
    && docker-php-ext-install -j2 pdo_mysql mysqli mbstring zip dom simplexml xml xmlreader xmlwriter \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
