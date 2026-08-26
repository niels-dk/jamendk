#!/bin/bash
# Exercise the backup's lock logic in isolation: normal acquire, overlap while
# a run is in progress, and takeover of a lock left behind by a hung run.
set -u
BASE="$(mktemp -d)"
LOCK_DIR="$BASE/.lock"
LOCK_MAX_AGE=3600

acquire() {   # mirrors scripts/backup.sh
    if ! mkdir "$LOCK_DIR" 2>/dev/null; then
        LOCK_AGE=$(( $(date +%s) - $(stat -c %Y "$LOCK_DIR" 2>/dev/null \
                                     || stat -f %m "$LOCK_DIR" 2>/dev/null || echo 0) ))
        if [ "$LOCK_AGE" -lt "$LOCK_MAX_AGE" ]; then
            echo "  -> skipped quietly (holder is ${LOCK_AGE}s old)"
            return 9
        fi
        echo "  -> cleared stale lock (${LOCK_AGE}s old) and continued"
        rm -rf "$LOCK_DIR"; mkdir "$LOCK_DIR" 2>/dev/null || true
    else
        echo "  -> acquired"
    fi
    return 0
}

echo "1. first run, no lock present:"
acquire; echo "     rc=$?"

echo "2. second run while the first still holds it:"
acquire; rc=$?; echo "     rc=$rc  (9 = skipped, and importantly SILENT to cron)"
[ "$rc" -eq 9 ] || { echo "FAIL: overlap was not skipped"; exit 1; }

echo "3. lock left behind by a hung run (backdated 2 hours):"
touch -d '2 hours ago' "$LOCK_DIR" 2>/dev/null || touch -A -020000 "$LOCK_DIR" 2>/dev/null
acquire; rc=$?; echo "     rc=$rc  (0 = took over, so a hang costs ONE night not every night)"
[ "$rc" -eq 0 ] || { echo "FAIL: stale lock was not broken"; exit 1; }

echo "4. trap releases it on exit:"
( trap 'rm -rf "$LOCK_DIR"' EXIT; : )
[ -d "$LOCK_DIR" ] && { echo "FAIL: lock survived exit"; exit 1; }
echo "  -> released"

rm -rf "$BASE"
echo
echo "ALL LOCK TESTS PASSED"
