ARG DEBIAN_RELEASE=stretch

ARG MANTICORE_VERSION=2.8.2
ARG MANTICORE_PROJECT=manticoresoftware/manticoresearch
ARG MANTICORE_SHA256SUM=1e4f34eb7dfd444c02e653b14bb0d71ac17b9e1ee019d4be6b90f752d4e87826


## Temporary bodge, we unable to build new images, as there are no longer apt repositories for debian stretch
## ...  so try reusing the last sucessfully built image, 
FROM registry.cloud.geograph.org.uk/geograph-project/geograph-project/british-isles/manticore:production-f235656a-20230314131913

# and just copy our config files (manticore/supercronic should be fully setup)

COPY system/docker/manticore/usr/share/ /usr/share/

COPY system/docker/manticore/entrypoint /
COPY system/docker/manticore/entrypoint.d/ /entrypoint.d/
COPY system/docker/manticore/etc/ /etc/

COPY system/docker/manticore/usr/local/bin/indexer-wrapper.php /usr/local/bin/indexer-wrapper.php


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
