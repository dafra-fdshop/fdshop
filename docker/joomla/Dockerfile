FROM joomla:6.1.3-php8.4-apache

# Add only the CLI tools required by the approved Phase-1 workflows.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        mariadb-client \
        rsync \
        unzip \
        zip; \
    rm -rf /var/lib/apt/lists/*

COPY development.ini /usr/local/etc/php/conf.d/zz-fdshop-development.ini
