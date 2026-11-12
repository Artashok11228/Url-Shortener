#!/usr/bin/env bash
# Repeatable ApacheBench (ab) run against a short-link redirect.
#
# ab does NOT follow Location headers, so the destination site is never hit.
# That keeps the measurement on our app only (status 302/410/404 of GET /{code}).
#
# Usage:
#   ./scripts/benchmark-redirect.sh <url> <label> [requests] [concurrency]
# Example:
#   ./scripts/benchmark-redirect.sh http://127.0.0.1:8000/bench before-null 2000 20
#
# Prefer the httpd Docker image so the machine does not need a local ab binary:
#   docker run --rm --network host httpd:2.4-alpine ab -n 2000 -c 20 -r URL
# On Docker Desktop for Windows/Mac without host networking, use host.docker.internal
# and publish the app port.

set -euo pipefail

URL="${1:?url required, e.g. http://127.0.0.1:8000/bench}"
LABEL="${2:?label required, e.g. before-null}"
REQUESTS="${3:-2000}"
CONCURRENCY="${4:-20}"
OUT_DIR="$(cd "$(dirname "$0")/.." && pwd)/docs/benchmarks"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUT_FILE="${OUT_DIR}/ab-${LABEL}-${STAMP}.txt"

mkdir -p "${OUT_DIR}"

run_ab() {
  if command -v ab >/dev/null 2>&1; then
    ab -n "${REQUESTS}" -c "${CONCURRENCY}" -r "${URL}"
    return
  fi

  if command -v docker >/dev/null 2>&1; then
    # host.docker.internal works from Linux containers on Docker Desktop.
    local docker_url="${URL/127.0.0.1/host.docker.internal}"
    docker_url="${docker_url/localhost/host.docker.internal}"
    docker run --rm httpd:2.4-alpine \
      ab -n "${REQUESTS}" -c "${CONCURRENCY}" -r "${docker_url}"
    return
  fi

  echo "Neither ab nor docker is available. Install ApacheBench or start Docker." >&2
  exit 1
}

{
  echo "# label=${LABEL}"
  echo "# url=${URL}"
  echo "# requests=${REQUESTS} concurrency=${CONCURRENCY}"
  echo "# started_utc=${STAMP}"
  echo "# note=ab does not follow redirects; only the first hop is measured"
  echo
  run_ab
} | tee "${OUT_FILE}"

echo
echo "Wrote ${OUT_FILE}"
