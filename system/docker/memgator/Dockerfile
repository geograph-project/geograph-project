FROM alpine:3.12

ARG MEMGATOR_VER=1.0-rc8

RUN set -eux; \
  apk add --no-cache --update \
    curl \
  ; \
  addgroup -g 1001 memgator; \
  adduser -h /var/lib/memgator -g MemGator -G memgator -DH -u 1001 memgator; \
  rm -rf /var/cache/apk/*

# Install ODU WS-DL MemGator
RUN set -eux; \
  curl -fsSLo /usr/local/bin/memgator \
  "https://github.com/oduwsdl/MemGator/releases/download/${MEMGATOR_VER}/memgator-linux-amd64"; \
  chmod +x /usr/local/bin/memgator

COPY system/docker/memgator/archives.json /usr/share/memgator/

USER memgator
CMD ["memgator", \
  "--agent", "Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)", \
  "--arcs", "/usr/share/memgator/archives.json", \
  "server"]

# https://github.com/opencontainers/image-spec/blob/master/annotations.md
LABEL org.opencontainers.image.authors="Tiger Computing Ltd" \
      org.opencontainers.image.url="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.source="https://github.com/geograph-project/geograph-project" \
      org.opencontainers.image.title="ODU WS-DL MemGator for Geograph"

# vim: ts=2 sw=2 et sts=2 ft=dockerfile
