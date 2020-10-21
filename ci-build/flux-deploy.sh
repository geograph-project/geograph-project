#!/bin/dash
#
# NB: This script runs in fluxcd/fluxctl container in BusyBox ash. You must
# avoid bashisms when modifying this script.
#

set -eu

COMPONENT="${CI_JOB_NAME#${CI_JOB_STAGE}/*}"
IMAGE_NAME="${CI_REGISTRY_IMAGE}/${GEO_REGION}/${COMPONENT}}"
IMAGE_TAG=$CI_COMMIT_SHORT_SHA

fluxctl list-workloads -n "${KUBE_NAMESPACE}"

fluxctl list-images \
  --workload "${KUBE_NAMESPACE}:${FLUX_KIND}/${COMPONENT}"

fluxctl release \
      --workload "${KUBE_NAMESPACE}:${FLUX_KIND}/${COMPONENT}" \
      --update-image "${IMAGE_NAME}:${IMAGE_TAG}" \
      --user "${GITLAB_USER_NAME} <${GITLAB_USER_EMAIL}>" \
      --message "Deployed by CI pipeline ${CI_PIPELINE_URL}" \
      --watch

# vim: ai ts=2 sw=2 et sts=2 ft=sh
