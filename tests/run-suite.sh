#!/usr/bin/env bash
set -euo pipefail

DOCKER="${DOCKER:-docker}"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ARCH="$(uname -m)"

case "$ARCH" in
    x86_64|amd64)
        PHP_IMAGE="php@sha256:48ed67e99726bbccd8ea1ce741dcf6b202263ad74bf70834a600fc37e401279e"
        ;;
    aarch64|arm64)
        PHP_IMAGE="php@sha256:c1a27a5e3fc9caeea1ed0f5ff0995f596fa5c269b8485698e1a74bd530cff64c"
        ;;
    *)
        echo "Unsupported architecture: $ARCH" >&2
        exit 1
        ;;
esac

WORK_DIR="$(mktemp -d)"
chmod 0700 "$WORK_DIR"
cleanup() { rm -rf "$WORK_DIR"; }
trap cleanup EXIT

"$DOCKER" pull "$PHP_IMAGE"

"$DOCKER" run --rm \
    -v "$REPO_ROOT:/repo:ro" \
    -v "$WORK_DIR:/work" \
    -w /repo \
    "$PHP_IMAGE" \
    bash -ec '
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq curl ca-certificates git unzip >/dev/null

cd /tmp
curl -fsSLO https://getcomposer.org/installer
curl -fsSLo composer-setup.sig https://composer.github.io/installer.sig
EXPECTED_SIG="$(cat composer-setup.sig)"
ACTUAL_SIG="$(php -r "echo hash_file(\"sha384\", \"installer\");")"
if [ "$ACTUAL_SIG" != "$EXPECTED_SIG" ]; then
  echo "Composer installer signature mismatch" >&2
  exit 1
fi
php installer --install-dir=/usr/local/bin --filename=composer
rm -f installer composer-setup.sig

mkdir -p /opt/phpunit
cd /opt/phpunit
composer require --no-interaction --no-progress phpunit/phpunit:9.6.38

OUTPUT="$(mktemp)"
set +e
php -d error_reporting=-1 /opt/phpunit/vendor/bin/phpunit \
  --configuration /repo/phpunit.xml.dist \
  --order-by=random \
  --random-order-seed=20261108 \
  --do-not-cache-result \
  --colors=never \
  --log-junit /work/junit.xml \
  2>&1 | tee "$OUTPUT"
STATUS=${PIPESTATUS[0]}
set -e

if grep -qE "PHP (Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error)" "$OUTPUT"; then
  echo "PHP diagnostic detected in test output" >&2
  exit 1
fi

if ! grep -qE "^OK \((110 tests," "$OUTPUT"; then
  echo "Expected clean OK summary for 110 tests" >&2
  exit 1
fi

if grep -qE "skipped|risky|warnings|incomplete" "$OUTPUT"; then
  echo "Summary indicates skipped/risky/warnings/incomplete" >&2
  exit 1
fi

exit "$STATUS"
'

cat "$WORK_DIR/junit.xml" >/dev/null
echo "Suite passed; junit at $WORK_DIR/junit.xml"
