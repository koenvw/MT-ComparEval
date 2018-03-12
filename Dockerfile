FROM php:7.1

RUN apt-get update && \
  apt-get -y install \
    sqlite3 \
    curl \
    unzip \
    git \
    python

COPY . /
WORKDIR /

RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar update --no-dev
RUN chmod -R 777 temp log storage
RUN sqlite3 storage/database < schema.sql
RUN mkdir data

RUN echo 'max_execution_time=1200' >> /usr/local/etc/php/conf.d/timeout.ini
RUN echo 'memory_limit=512M' >> /usr/local/etc/php/conf.d/memory.ini

ENTRYPOINT ["bin/watchAndServe.sh"]

EXPOSE 8080
