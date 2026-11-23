############################################
# Base image
############################################

# Server Side Up PHP images: https://serversideup.net/open-source/docker-php/
FROM serversideup/php:8.5-fpm-nginx-alpine AS base

USER root
RUN install-php-extensions pdo_mysql redis intl bcmath pcntl zip opcache

############################################
# Development image
############################################
FROM base AS development

# Pass USER_ID and GROUP_ID so www-data matches the host user.
ARG USER_ID
ARG GROUP_ID

USER root
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

USER www-data

############################################
# CI image
############################################
FROM base AS ci

USER root
RUN echo "user = www-data" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf

############################################
# CLI image (queue worker, scheduler, gates)
############################################
FROM serversideup/php:8.5-cli-alpine AS development-cli

USER root
RUN install-php-extensions pdo_mysql redis intl bcmath pcntl zip opcache
