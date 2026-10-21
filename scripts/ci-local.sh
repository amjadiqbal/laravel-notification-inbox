#!/usr/bin/env bash
# Local CI ("CI is local", no GitHub workflow runs): every PR runs this and pastes the
# summary into its body. PHP 8.3/8.4/8.5 ×
# Laravel 12/13, Testbench pinned per Laravel major, composer update, a check that the intended Laravel major
# resolved, then Pest, Pint --test and PHPStan. Then a check that the archive users install (git archive HEAD)
# holds only what ships.
#   scripts/ci-local.sh                                     the full 3×2 matrix
#   PHP_VERSIONS=8.4 LARAVEL_VERSIONS=13 scripts/ci-local.sh one cell
# Finds php8.x on PATH, or Homebrew's php@8.x. composer.json is restored after each cell.
set -uo pipefail
cd "$(dirname "$0")/.."

PHP_VERSIONS=${PHP_VERSIONS:-"8.3 8.4 8.5"}
LARAVEL_VERSIONS=${LARAVEL_VERSIONS:-"12 13"}
RESULTS=(); FAILED=0
step() { local name=$1; shift; printf '\n== %s\n' "$name"; if "$@"; then RESULTS+=("pass  $name"); else RESULTS+=("FAIL  $name"); FAILED=1; fi; }

php_bin() {
  local v=$1 p
  for p in "$(command -v "php$v" 2>/dev/null)" "$(brew --prefix 2>/dev/null)/opt/php@$v/bin/php"; do
    [ -n "$p" ] && [ -x "$p" ] && "$p" -r 'exit(PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION === $argv[1] ? 0 : 1);' "$v" && { echo "$p"; return; }
  done
  return 1
}

COMPOSER=$(command -v composer)
SHIM=$(mktemp -d)
cp composer.json "$SHIM/composer.json.orig"
cleanup() { cp "$SHIM/composer.json.orig" composer.json; rm -f composer.lock; rm -rf "$SHIM"; }
trap cleanup EXIT
BASE_PATH=$PATH

for v in $PHP_VERSIONS; do
  PHP=$(php_bin "$v") || { RESULTS+=("FAIL  PHP $v not found"); FAILED=1; continue; }
  # vendor/bin scripts start with "#!/usr/bin/env php": a wrapper puts this version (opcache on, as in CI) first on PATH.
  printf '#!/bin/sh\nexec "%s" -d opcache.enable=1 -d opcache.enable_cli=1 "$@"\n' "$PHP" > "$SHIM/php"; chmod +x "$SHIM/php"
  export PATH="$SHIM:$BASE_PATH"
  for l in $LARAVEL_VERSIONS; do
    cell="PHP $v / Laravel $l"
    cp "$SHIM/composer.json.orig" composer.json; rm -f composer.lock
    case $l in 12) tb='^10.0' ;; 13) tb='^11.0' ;; *) RESULTS+=("FAIL  $cell: unknown Laravel"); FAILED=1; continue ;; esac
    step "$cell active php"    sh -c "php -r 'exit(PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION === \"$v\" ? 0 : 1);'"
    step "$cell pin testbench" php "$COMPOSER" require --dev "orchestra/testbench:$tb" --no-update -W
    step "$cell composer update" php "$COMPOSER" update --no-interaction --prefer-dist --prefer-stable --no-progress || continue
    step "$cell laravel $l resolved" sh -c "php '$COMPOSER' show laravel/framework | grep -E '^versions' | grep -q '\* v$l\.'"
    step "$cell pest"    php "$COMPOSER" test
    step "$cell pint"    php "$COMPOSER" pint
    step "$cell phpstan" php "$COMPOSER" analyse
  done
done

# What Composer users download: no tests, art or dev config, and every runtime file present.
shipped() {
  local list; list=$(git archive HEAD | tar -t) || return 1
  for f in composer.json config/notification-inbox.php routes/web.php resources/views/inbox.blade.php src/NotificationInboxServiceProvider.php; do
    printf '%s\n' "$list" | grep -qx "$f" || { echo "missing from archive: $f"; return 1; }
  done
  ! printf '%s\n' "$list" | grep -Eq '^(tests|art|scripts)/|phpunit|phpstan' || { echo 'dev files in archive'; return 1; }
}
step "git archive contents" shipped

printf '\n== Summary (%s, %s)\n' "$(git rev-parse --short HEAD)" "$(date '+%Y-%m-%d %H:%M')"
printf '%s\n' "${RESULTS[@]}"
exit $FAILED
