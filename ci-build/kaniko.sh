#!/bin/dash
#
# NB: This script runs in a Kaniko container in BusyBox ash. You must avoid
# bashisms when modifying this script.
#

set -eu

COMPONENT="${CI_JOB_NAME#build/*}"
IMAGE_NAME="${CI_REGISTRY_IMAGE}/${GEO_REGION}/${COMPONENT}"
TAGS="$CI_COMMIT_SHORT_SHA $CI_COMMIT_REF_SLUG"

CREATED_DATE="$(date --utc +%Y-%m-%dT%H:%M:%SZ)"

kaniko_destinations() {
  for TAG in $TAGS; do
    echo "--destination ${IMAGE_NAME}:${TAG}";
  done
}

# Generate the Docker config.json with authentication info to our registry
[ -z "${DOCKER_CONFIG:-}" ] && export DOCKER_CONFIG=/kaniko/.docker/
cat > "${DOCKER_CONFIG}/config.json" <<EOF
{
  "auths": {
    "${CI_REGISTRY}": {
      "username": "${CI_REGISTRY_USER}",
      "password": "${CI_REGISTRY_PASSWORD}"
    }
  }
}
EOF

echo "Building image: $IMAGE_NAME ..."
set -x

# Build the container image
# shellcheck disable=SC2046
/kaniko/executor \
  --context . \
  --dockerfile "system/docker/${COMPONENT}/Dockerfile" \
  --label org.opencontainers.image.created="$CREATED_DATE" \
  --label org.opencontainers.image.revision="$CI_COMMIT_SHA" \
  "$@" \
  $(kaniko_destinations)

# vim: ai ts=2 sw=2 et sts=2 ft=sh
