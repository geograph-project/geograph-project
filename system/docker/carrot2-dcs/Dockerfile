FROM openjdk:8-jre-slim

ARG DCS_VER=3.14.0

RUN set -eux; \
  export DEBIAN_FRONTEND=noninteractive; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    unzip \
  ; \
  addgroup --gid 999 dcs; \
  adduser --home /var/lib/dcs --no-create-home --uid 999 \
    --gecos 'Carrot2 DCS' --ingroup dcs --disabled-password --disabled-login \
    dcs; \
  find /var/lib/apt/lists -mindepth 1 -delete

RUN set -eux; \
  curl -fsSLO https://github.com/carrot2/carrot2/releases/download/release%2F${DCS_VER}/carrot2-dcs-${DCS_VER}.zip; \
  unzip carrot2-dcs-${DCS_VER}.zip; \
  rm carrot2-dcs-${DCS_VER}.zip; \
  mv carrot2-dcs-${DCS_VER} /opt/carrot2-dcs; \
  chown -R dcs: /opt/carrot2-dcs;

WORKDIR /opt/carrot2-dcs
USER dcs
CMD ["./dcs.sh"]

# https://github.com/opencontainers/image-spec/blob/master/annotations.md
LABEL org.opencontainers.image.authors="Tiger Computing Ltd" \
      org.opencontainers.image.url="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.source="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.title="Carrot2 DCS for Geograph"

# vim: ts=2 sw=2 et sts=2 ft=dockerfile
