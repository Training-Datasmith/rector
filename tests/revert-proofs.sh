#!/usr/bin/env bash
set -euo pipefail

PHP="${PHP:-php8.3}"
PHPUNIT_HOME="$(mktemp -d)"
chmod 0700 "$PHPUNIT_HOME"
export PATH="${HOME}/.local/bin:${PATH}"

EXPORT_DIR=""

cleanup() {
    rm -rf "$PHPUNIT_HOME"
    if [ -n "$EXPORT_DIR" ] && [ -d "$EXPORT_DIR" ]; then
        rm -rf "$EXPORT_DIR"
    fi
}
trap cleanup EXIT

composer --working-dir="$PHPUNIT_HOME" require --no-interaction --no-progress phpunit/phpunit:9.6.38 >/dev/null 2>&1
PHPUNIT="$PHPUNIT_HOME/vendor/bin/phpunit"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE="${BASE:-origin/main}"

materialize_export() {
    EXPORT_DIR="$(mktemp -d)"
    chmod 0700 "$EXPORT_DIR"
    tar -C "$REPO_ROOT" --exclude='.git' -cf - . | tar -x -C "$EXPORT_DIR"
}

run_filtered() {
    local filter="$1"
    $PHP -d error_reporting=-1 "$PHPUNIT" \
        --configuration "$EXPORT_DIR/phpunit.xml.dist" \
        --filter "$filter" \
        --do-not-cache-result \
        --colors=never \
        2>&1
}

proof() {
    local name="$1"
    local filter="$2"
    materialize_export
    local out
    out="$(mktemp)"
    if run_filtered "$filter" >"$out" 2>&1; then
        echo "PROOF FAIL: $name — test still passes on fixed export (sanity)" >&2
        cat "$out" >&2
        exit 1
    fi
    if grep -qE 'PHP (Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error)' "$out"; then
        echo "PROOF FAIL: $name — PHP diagnostic" >&2
        cat "$out" >&2
        exit 1
    fi
    if ! grep -qE '(^FAILURES!|^ERRORS!|There was 1 failure|There was 1 error)' "$out"; then
        echo "PROOF FAIL: $name — expected test failure" >&2
        cat "$out" >&2
        exit 1
    fi
    echo "PROOF SANITY OK: $name (fails before re-mutation on fixed tree — skip)"
    rm -f "$out"
    rm -rf "$EXPORT_DIR"
    EXPORT_DIR=""
}

proof_revert() {
    local name="$1"
    local filter="$2"
    local mutate="$3"
    materialize_export
    export EXPORT_DIR
    bash -c "$mutate"
    local out
    out="$(mktemp)"
    if run_filtered "$filter" >"$out" 2>&1; then
        echo "PROOF FAIL: $name — still passing after revert" >&2
        cat "$out" >&2
        exit 1
    fi
    if grep -qE 'PHP (Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error)' "$out"; then
        echo "PROOF FAIL: $name — PHP diagnostic after revert" >&2
        cat "$out" >&2
        exit 1
    fi
    if ! grep -qE '(^FAILURES!|^ERRORS!|There was 1 failure|There was 1 error)' "$out"; then
        echo "PROOF FAIL: $name — expected failure missing" >&2
        cat "$out" >&2
        exit 1
    fi
    echo "PROOF OK: $name"
    rm -f "$out"
    rm -rf "$EXPORT_DIR"
    EXPORT_DIR=""
}

proof_revert system-error-null testMissingRelativePathReturnsNull \
    'sed -i "s/return \\\\realpath(\$this->relativeFilePath) ?: null;/return \\\\realpath(\$this->relativeFilePath);/" "$EXPORT_DIR/src/ValueObject/Error/SystemError.php"'

proof_revert file-path-helper-phar testPharDotDotAboveRootDoesNotThrow \
    'sed -i "/if (!is_string(\$removedPart)) {/,+2d" "$EXPORT_DIR/src/FileSystem/FilePathHelper.php"'

proof_revert privates-accessor-parent testMissingPropertyOnSubclassThrowsDedicatedException \
    "git -C \"$REPO_ROOT\" show \"$BASE:src/Util/Reflection/PrivatesAccessor.php\" > \"\$EXPORT_DIR/src/Util/Reflection/PrivatesAccessor.php\""

proof_revert file-hasher-is-file testMissingFileThrows \
    'sed -i "/if (!is_file(\$file)) {/,+2d" "$EXPORT_DIR/src/Util/FileHasher.php"'

echo 'All revert proofs passed.'
