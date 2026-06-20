FROM hyperf/hyperf:8.4-alpine-v3.22-swoole
LABEL maintainer="Gabriel Assuncao" version="1.0" license="MIT" app.name="hyperf-bank-api"

ARG timezone=America/Sao_Paulo
ARG APP_ENV=dev

ENV TIMEZONE=${timezone} \
    APP_ENV=${APP_ENV} \
    SCAN_CACHEABLE=false

RUN set -ex \
    && php -v \
    && php -m \
    && php --ri swoole \
    && cd /etc/php* \
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && apk add --no-cache inotify-tools \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

WORKDIR /opt/www

COPY composer.* /opt/www/
RUN composer install --no-scripts

COPY . /opt/www
RUN composer dump-autoload -o

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php"]
CMD ["start"]
