# syntax = docker/dockerfile:experimental

# Default to PHP 8.2, but we attempt to match
# the PHP version from the user (wherever `flyctl launch` is run)
# Valid version values are PHP 7.4+
ARG PHP_VERSION=8.2
ARG NODE_VERSION=14
FROM fideloper/fly-laravel:${PHP_VERSION} as base

# PHP_VERSION needs to be repeated here
# See https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION

LABEL fly_launch_runtime="laravel"

# copy application code, skipping files based on .dockerignore
COPY . /var/www/html

RUN composer install --optimize-autoloader --no-dev \
    && chown -R www-data:www-data /var/www/html \
    && cp .fly/entrypoint.sh /entrypoint \
    && chmod +x /entrypoint



# RUN mkdir -p  /app
# WORKDIR /app
COPY . /var/www/html
RUN mkdir /var/www/html/runtime
RUN mkdir /var/www/html/public/assets
RUN chmod -R 777 /var/www/html/public/assets
RUN chmod -R 777 /var/www/html/runtime
# COPY --from=base /var/www/html/vendor /app/vendor


# From our base container created above, we
# create our final image, adding in static
# assets that we generated above
FROM base

# Packages like Laravel Nova may have added assets to the public directory
# or maybe some custom assets were added manually! Either way, we merge
# in the assets we generated above rather than overwrite them

EXPOSE 8080

ENTRYPOINT ["/entrypoint"]

