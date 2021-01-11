#!/bin/dash
#
# NB: This script runs in fluxcd/fluxctl container in BusyBox ash. You must
# avoid bashisms when modifying this script.
#

set -eu

COMPONENT="${CI_JOB_NAME#${CI_JOB_STAGE}/*}"
WORKLOAD="${KUBE_NAMESPACE}:${FLUX_KIND}/${COMPONENT}"
IMAGE_NAME="${CI_REGISTRY_IMAGE}/${GEO_REGION}/${FLUX_IMAGE:="${COMPONENT}"}"
IMAGE_TAG=$CI_COMMIT_SHORT_SHA

image_exists() {
  fluxctl list-images --workload "${WORKLOAD}" 2>/dev/null | \
    grep -q "\\<${IMAGE_TAG}\\>"
}

echo "Deploying image ${IMAGE_NAME}:${IMAGE_TAG} ..."
echo "  ... to ${WORKLOAD}."

# Check for the image every ten seconds, sixty times, to total a ten-minute
# window.
for i in $(seq 60 -1 1); do
  # If the image exists, fall out of the loop
  image_exists && break

  if [ $i -gt 1 ]; then
    echo "[ ... waiting for image to become available ... ]"
    sleep 10
  else
    echo "The image hasn't appeared, giving up."
    echo "Output of 'fluxctl list-images' for debug purposes..."

    fluxctl list-images --workload "${WORKLOAD}"

    exit 1
  fi
done

set -x

fluxctl release \
      --workload "${WORKLOAD}" \
      --update-image "${IMAGE_NAME}:${IMAGE_TAG}" \
      --user "${GITLAB_USER_NAME} <${GITLAB_USER_EMAIL}>" \
      --message "Deployed ${KUBE_NAMESPACE}/${COMPONENT} by CI pipeline ${CI_PIPELINE_URL}" \
      --watch

# vim: ai ts=2 sw=2 et sts=2 ft=sh
