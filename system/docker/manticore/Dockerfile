ARG DEBIAN_RELEASE=stretch

ARG MANTICORE_VERSION=2.8.2
ARG MANTICORE_PROJECT=manticoresoftware/manticoresearch
ARG MANTICORE_SHA256SUM=1e4f34eb7dfd444c02e653b14bb0d71ac17b9e1ee019d4be6b90f752d4e87826

FROM debian:${DEBIAN_RELEASE}-slim AS base

# Pull in global arguments that we need for this stage
ARG DEBIAN_RELEASE
ARG MANTICORE_PROJECT
ARG MANTICORE_VERSION
ARG MANTICORE_SHA256SUM

RUN set -eux; \
  export DEBIAN_FRONTEND=noninteractive; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    rsync \
    jq \
    libcrypto++6 \
    libexpat1 \
    libmariadbclient-dev-compat \
    libodbc1 \
    libpq5 \
    mysql-client \
    php-cli php-mysql \
    openssl \
    procps \
  ; \
  find /var/lib/apt/lists -mindepth 1 -delete

RUN set -eux; \
  addgroup --gid 999 manticore; \
  adduser --home /var/lib/manticore --uid 999 --gecos 'Manticore Search' \
    --ingroup manticore --disabled-password --disabled-login manticore;

RUN set -eux; \
  curl -fsSL "https://api.github.com/repos/${MANTICORE_PROJECT}/releases/tags/${MANTICORE_VERSION}" | \
    jq -r ".assets[]|select(.name|contains(\"${DEBIAN_RELEASE}_amd64-bin\"))|.browser_download_url" | \
    xargs -n1 curl -fsSL -o manticore.deb; \
  echo "${MANTICORE_SHA256SUM}  manticore.deb" | sha256sum -c -; \
  dpkg -i manticore.deb; \
  rm manticore.deb

# The temporary build container for Manticore UDF plugins
FROM base AS builder

# Pull in global arguments that we need for this stage
ARG MANTICORE_PROJECT
ARG MANTICORE_VERSION

# We don't need to be careful to avoid extraneous layers and files in here
# because this container is discarded after the plugins are built.
RUN apt-get update
RUN apt-get install -y --no-install-recommends \
    build-essential \
  ;

WORKDIR /root
COPY src/sphinx-plugins/*.cpp /root/

RUN curl -fsSLO "https://raw.githubusercontent.com/${MANTICORE_PROJECT}/${MANTICORE_VERSION}/src/sphinxudf.h"

# Build the plugins with Debian's build hardening options enabled
RUN set -eux; \
  eval $(DEB_BUILD_OPTIONS="hardening=+all qa=+all,-canary" dpkg-buildflags --export=sh); \
  g++ -fPIC -shared -Wall -Wextra $CPPFLAGS $CXXFLAGS $LDFLAGS -o uniqueserial.so uniqueserial.cpp; \
  g++ -fPIC -shared -Wall -Wextra $CPPFLAGS $CXXFLAGS $LDFLAGS -o withinfirstx.so withinfirstx.cpp; \
  :

# The end-result Manticore service container
FROM base

ARG SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.1.9/supercronic-linux-amd64
ARG SUPERCRONIC=supercronic-linux-amd64
ARG SUPERCRONIC_SHA1SUM=5ddf8ea26b56d4a7ff6faecdd8966610d5cb9d85

RUN set -eux; \
  curl -fsSLO "$SUPERCRONIC_URL"; \
  echo "${SUPERCRONIC_SHA1SUM}  ${SUPERCRONIC}" | sha1sum -c -; \
  chmod +x "$SUPERCRONIC"; \
  mv "$SUPERCRONIC" "/usr/local/bin/${SUPERCRONIC}"; \
  ln -s "/usr/local/bin/${SUPERCRONIC}" /usr/local/bin/supercronic

COPY --from=builder /root/*.so /usr/lib/manticore/plugins/

COPY system/docker/manticore/usr/share/ /usr/share/

COPY system/docker/manticore/entrypoint /
COPY system/docker/manticore/entrypoint.d/ /entrypoint.d/
COPY system/docker/manticore/etc/ /etc/

COPY system/docker/manticore/usr/local/bin/indexer-wrapper.php /usr/local/bin/indexer-wrapper.php

RUN set -eux; \
  mkdir -p \
    /var/lib/manticore/binlog \
    /var/run/manticore \
  ; \
  chown -R manticore: \
    /var/lib/manticore \
    /var/log/manticore \
    /var/run/manticore \
  ; \
  :

WORKDIR /var/lib/manticore
USER manticore
ENTRYPOINT ["/entrypoint"]
CMD ["searchd", "--nodetach"]

# https://github.com/opencontainers/image-spec/blob/master/annotations.md
LABEL org.opencontainers.image.authors="Tiger Computing Ltd" \
      org.opencontainers.image.url="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.source="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.title="Manticore Search for Geograph"

# vim: ts=2 sw=2 et sts=2 ft=dockerfile
