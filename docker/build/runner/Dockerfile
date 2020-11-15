ARG PHP_CLI_IMAGE=php:7.4-cli-buster
ARG COMPOSER_IMAGE=composer:2

FROM ${COMPOSER_IMAGE} AS composer-stage

FROM ${PHP_CLI_IMAGE} AS php-stage

###
# Composer
###

COPY --from=composer-stage /usr/bin/composer /usr/local/bin/composer

# default test user
ARG USERNAME=tester
ARG USER_UID=1000
ARG USER_GID=1000
## add user group and user
RUN apt-get update \
    && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
        sudo \
    && groupadd --gid ${USER_GID} ${USERNAME} \
    && useradd --uid ${USER_UID} --gid ${USER_GID} -m ${USERNAME} \
    && echo ${USERNAME} ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/${USERNAME} \
    && chmod 0440 /etc/sudoers.d/${USERNAME} \
    ## cleanup apt list and everything we normally do not need in a container
    && apt-get autoremove -y && apt-get clean -y && rm -rf /var/lib/apt/lists/*

## install git, dev dependencies and tools that might be usefull
RUN apt-get update \
    && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
    ## needed for source code checkout and commits from within container
    ## f.e. https, git+ssh, signing
    git ca-certificates openssl openssh-client gnupg2 \
    ## might be needed here and there
    python \
    ## general usefull tools which might be needed here and there
    wget \
    xz-utils \
    unzip \
    ## good to have, other locale
    locales-all \
    ## cleanup apt list and everything we normally do not need in a container
    && apt-get autoremove -y && apt-get clean -y && rm -rf /var/lib/apt/lists/*

## setup configuration and extensions
RUN set -ex \
    # setup php prod config
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    ## source if needed
    # && docker-php-source extract \
    ## install XDebug, but do not enable it
    && pecl install -o xdebug \
    ## remove tmp pear files
    && rm -rf /tmp/pear \
    ## turn all php modules off so we can pick the needed ones on execution
    && rm -f /usr/local/etc/php/conf.d/*.ini \
    ## cleanup apt list and everything we normally do not need in a container
    && apt-get autoremove -y && apt-get clean -y && rm -rf /var/lib/apt/lists/*

## add aditional scripts
COPY scripts /usr/local/bin

## make scripts executable and enable php modules
RUN set -ex \
    ## setup default pathes and install helper scripts
    && mkdir -p /app \
    && cd /usr/local/bin/ \
        && chmod 755 /usr/local/bin/phpunit-nc \
        && chmod 755 /usr/local/bin/phpunit-coverage \
        && chmod 755 /usr/local/bin/phpstan-check \
        && chmod 755 /usr/local/bin/psalm-check \
        && chmod 755 /usr/local/bin/opcache-enable \
        && chmod 755 /usr/local/bin/opcache-disable \
        && chmod 755 /usr/local/bin/xdebug-enable \
        && chmod 755 /usr/local/bin/xdebug-disable \
        && chmod 755 update-php-ini \
    ## turn off php banner and increase memory limit
    # && /usr/local/bin/update-php-ini "expose_php" "Off" \
    && /usr/local/bin/update-php-ini "memory_limit" "128M"

## for dev, enable XDebug
RUN set -ex \
    && /usr/local/bin/xdebug-enable

## switch to user
USER ${USERNAME}
