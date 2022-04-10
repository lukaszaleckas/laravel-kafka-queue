FROM composer:2 as builder

COPY composer.json /app/

RUN composer install \
  --ignore-platform-reqs \
  --no-ansi \
  --no-autoloader \
  --no-interaction \
  --no-scripts

COPY . /app/

RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:7.4-cli as base

ENV LIBRDKAFKA_VERSION v1.6.2

RUN  apt-get update \
    && apt-get install -y --no-install-recommends build-essential git python \
    && cd /tmp \
    && git clone \
        --branch ${LIBRDKAFKA_VERSION} \
        --depth 1 \
        https://github.com/edenhill/librdkafka.git \
    && cd librdkafka \
    && ./configure \
    && make \
    && make install \
    && pecl install xdebug rdkafka \
    && docker-php-ext-enable xdebug rdkafka \
    && rm -rf /tmp/librdkafka

# Add php extensions configuration
COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Cleanup
RUN rm -rf /var/lib/apt/lists/*
RUN rm -rf /tmp/pear/

# Setup working directory
WORKDIR /app

COPY --from=builder /app /app
