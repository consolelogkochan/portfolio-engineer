FROM ubuntu:26.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=24

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LANG=C.UTF-8
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

# PHP と Composer は、本番と同じく Ubuntu の配布から入れる（拡張の組み合わせも本番と同じ）。
# php8.5-fpm は入れない（開発では artisan serve で動かすため）。
RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor libcap2-bin \
    && apt-get install -y \
        php8.5-cli \
        php8.5-common \
        php8.5-curl \
        php8.5-gd \
        php8.5-intl \
        php8.5-mbstring \
        php8.5-readline \
        php8.5-xml \
        php8.5-zip \
        composer \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.5

RUN userdel -r ubuntu
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail
RUN git config --global --add safe.directory /var/www/html

# 画像最適化バイナリ（sail artisan images:optimize コマンドで使用）
# webp      : cwebp を提供。WebP 変換後の圧縮に spatie/laravel-image-optimizer が使用
# jpegoptim : JPEG のロスレス/ロッシー圧縮に spatie が使用
# pngquant  : PNG のロッシー圧縮に spatie が使用
# optipng   : PNG のロスレス圧縮に spatie が使用
RUN apt-get update \
    && apt-get install -y webp jpegoptim pngquant optipng \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.5/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
