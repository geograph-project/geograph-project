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
    php-redis \
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

RUN ln -s /mnt/efs/upload_tmp_dir /var/www/geograph/upload_tmp_dir
RUN ln -s /mnt/efs/upload_tmp_dir_old /var/www/geograph/upload_tmp_dir_old

#git repo contains 'default' files for the shared folders, but actully needs to be linked to EFS for use
RUN mv /var/www/geograph/public_html/maps /var/www/geograph/public_html/maps.orig
RUN mv /var/www/geograph/public_html/kml /var/www/geograph/public_html/kml.orig
RUN mv /var/www/geograph/public_html/rss /var/www/geograph/public_html/rss.orig
RUN mv /var/www/geograph/public_html/sitemap /var/www/geograph/public_html/sitemap.orig

RUN ln -s /mnt/efs/maps /var/www/geograph/public_html/maps
RUN ln -s /mnt/efs/kml /var/www/geograph/public_html/kml
RUN ln -s /mnt/efs/rss /var/www/geograph/public_html/rss
RUN ln -s /mnt/efs/sitemap /var/www/geograph/public_html/sitemap

# Note, the smarty symlinks, are set in 'init-container.php' as they have more logic

WORKDIR /var/www/geograph
ENV CONF_PROFILE=docker

# Used by scripts to setup enviroment
ENV BASE_DIR=/var/www/geograph

# https://github.com/opencontainers/image-spec/blob/master/annotations.md
LABEL org.opencontainers.image.authors="Tiger Computing Ltd" \
      org.opencontainers.image.url="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.source="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.title="Geograph"

# vim: ts=2 sw=2 et sts=2 ft=dockerfile
