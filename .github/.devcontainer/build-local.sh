#!/usr/bin/env bash
set -euo pipefail

# Build the devcontainer image locally from the current branch.
# Usage: .github/.devcontainer/build-local.sh [PHP_VERSION]
#
# Requires: Docker, a pre-built dist/slh.phar (run: box compile)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
PHP_VERSION="${1:-8.4}"
ARCH="$(docker info --format '{{.Architecture}}')"

# Normalise arch names to match SPC conventions
case "${ARCH}" in
    aarch64|arm64) ARCH="arm64" ;;
    x86_64|amd64)  ARCH="amd64" ;;
    *) echo "Unsupported architecture: ${ARCH}" && exit 1 ;;
esac

echo "==> Building for linux/${ARCH} with PHP ${PHP_VERSION}"

# 1. Ensure PHAR exists
if [[ ! -f "${PROJECT_ROOT}/dist/slh.phar" ]]; then
    echo "[!] dist/slh.phar not found. Build it first:"
    echo "    cp .cache/buildroot/bin/frankenphp bin/ && box compile"
    exit 1
fi

# 2. Build Linux binary inside a container using SPC
echo "==> Building Linux binary via static-php-cli..."

# Pass GitHub token to avoid API rate limits during SPC downloads
GH_TOKEN="${GITHUB_TOKEN:-$(gh auth token 2>/dev/null || true)}"

docker build \
    --platform "linux/${ARCH}" \
    --build-arg "PHP_VERSION=${PHP_VERSION}" \
    --build-arg "GITHUB_TOKEN=${GH_TOKEN}" \
    -f "${SCRIPT_DIR}/Dockerfile.build" \
    -t slh-builder \
    "${PROJECT_ROOT}"

# Extract the binary from the builder image
CONTAINER_ID=$(docker create --platform "linux/${ARCH}" slh-builder)
docker cp "${CONTAINER_ID}:/out/slh-linux-${ARCH}-${PHP_VERSION}" "${SCRIPT_DIR}/slh-linux-${ARCH}-${PHP_VERSION}"
docker rm "${CONTAINER_ID}" > /dev/null

echo "==> Linux binary: slh-linux-${ARCH}-${PHP_VERSION}"

# 3. Copy webserver templates next to Dockerfile
cp "${PROJECT_ROOT}/templates/Caddyfile" "${SCRIPT_DIR}/Caddyfile"
cp "${PROJECT_ROOT}/templates/.env" "${SCRIPT_DIR}/env-template"

# 4. Build the devcontainer image
echo "==> Building devcontainer image..."
docker build \
    --platform "linux/${ARCH}" \
    --build-arg "TARGETOS=linux" \
    --build-arg "TARGETARCH=${ARCH}" \
    --build-arg "PHP_VERSION=${PHP_VERSION}" \
    -f "${SCRIPT_DIR}/Dockerfile" \
    -t "slh-devcontainer:${PHP_VERSION}" \
    "${SCRIPT_DIR}"

# 5. Cleanup build artifacts
rm -f "${SCRIPT_DIR}/slh-linux-${ARCH}-${PHP_VERSION}"
rm -f "${SCRIPT_DIR}/Caddyfile"
rm -f "${SCRIPT_DIR}/env-template"

echo ""
echo "==> Done! Image: slh-devcontainer:${PHP_VERSION}"
echo "    To test: docker run --rm -it slh-devcontainer:${PHP_VERSION} slh --version"
