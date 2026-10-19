FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_pgsql curl \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html
COPY docker-entrypoint.sh /usr/local/bin/nexus-entrypoint
RUN chmod +x /usr/local/bin/nexus-entrypoint \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["nexus-entrypoint"]
