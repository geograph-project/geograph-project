# https://hub.docker.com/r/tigercomputing/debian-php-fpm
FROM tigercomputing/debian-php-fpm:buster

RUN set -eux; \
  export DEBIAN_FRONTEND=noninteractive; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    curl \
    imagemagick \
    libimage-exiftool-perl \
    libjpeg-turbo-progs \
    nginx \
    php-bz2 \
    php-memcache \
    procps \
  ; \
  rm -f /etc/nginx/sites-enabled/default; \
  find /var/lib/apt/lists -mindepth 1 -delete

ARG SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.1.9/supercronic-linux-amd64
ARG SUPERCRONIC=supercronic-linux-amd64
ARG SUPERCRONIC_SHA1SUM=5ddf8ea26b56d4a7ff6faecdd8966610d5cb9d85

RUN set -eux; \
  curl -fsSLO "$SUPERCRONIC_URL"; \
  echo "${SUPERCRONIC_SHA1SUM}  ${SUPERCRONIC}" | sha1sum -c -; \
  chmod +x "$SUPERCRONIC"; \
  mv "$SUPERCRONIC" "/usr/local/bin/${SUPERCRONIC}"; \
  ln -s "/usr/local/bin/${SUPERCRONIC}" /usr/local/bin/supercronic

COPY system/docker/geograph/etc/ /etc/

COPY libs/        /var/www/geograph/libs/
COPY public_html/ /var/www/geograph/public_html/
COPY scripts/     /var/www/geograph/scripts/

RUN ln -s /var/www/geograph/scripts /scripts

WORKDIR /var/www/geograph
ENV CONF_PROFILE=docker

# Used by scripts to setup enviroment. CONFIG_FILE will need to be set in flux
ENV BASE_DIR=/var/www/geograph

# https://github.com/opencontainers/image-spec/blob/master/annotations.md
LABEL org.opencontainers.image.authors="Tiger Computing Ltd" \
      org.opencontainers.image.url="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.source="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.title="Geograph"

# vim: ts=2 sw=2 et sts=2 ft=dockerfile
