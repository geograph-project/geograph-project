#!/bin/bash
set -eu -o pipefail

COMPONENT="${CI_JOB_NAME#${CI_JOB_STAGE}/*}"
IMAGE_NAME="${CI_REGISTRY_IMAGE}/${GEO_REGION}/${SOURCE_IMAGE:-${COMPONENT}}"
SOURCE_TAG=$CI_COMMIT_SHORT_SHA
JOB_TS="$(echo "$CI_JOB_STARTED_AT" | sed -e 's/[^0-9]//g' | cut -c1-14)"
DEST_TAGS=(
  "${SOURCE_IMAGE:+${COMPONENT}-}${CI_JOB_STAGE}"
  "${SOURCE_IMAGE:+${COMPONENT}-}${CI_JOB_STAGE}-${CI_COMMIT_SHORT_SHA}-${JOB_TS}"
)

skopeo login --username "$CI_REGISTRY_USER" --password "$CI_REGISTRY_PASSWORD" "$CI_REGISTRY"

echo
echo "****************************************"
echo "Promoting ${IMAGE_NAME}:${SOURCE_TAG} to:"
for DEST_TAG in "${DEST_TAGS[@]}"; do
  echo " - ${DEST_TAG}"
done
echo "****************************************"
echo

for DEST_TAG in "${DEST_TAGS[@]}"; do
  echo "Promoting ${DEST_TAG}"

  skopeo copy \
    "docker://${IMAGE_NAME}:${SOURCE_TAG}" \
    "docker://${IMAGE_NAME}:${DEST_TAG}"

  echo
done

# vim: ai ts=2 sw=2 et sts=2 ft=sh
