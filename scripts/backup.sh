#!/bin/bash
# =============================================================================
# DreamBoard backup — database nightly, uploaded files weekly.
#
# Designed for DreamHost shared hosting. Run by cron (see SETUP below) and
# safe to run by hand any time — e.g. right BEFORE executing a migration:
#
#     ~/jamen.dk/scripts/backup.sh
#
# What it does
#   * mysqldump of the app database → gzip → ~/backups/db/jamen_dk-YYYY-MM-DD.sql.gz
#     (one per day; re-running the same day overwrites — that's the point when
#     you run it manually before a migration)
#   * on Sundays (or FORCE_FILES=1): tar of ~/jamen.dk/storage (uploaded media,
#     encrypted documents, thumbnails) → ~/backups/files/
#   * verifies the dump is a valid gzip and not suspiciously tiny before
#     declaring success
#   * retention: DB dumps 30 days, file archives ~5 weeks
#   * optional offsite copy: if ~/.backup_remote exists, its first line is used
#     as an rsync target (DreamHost gives every account a free 50GB backups
#     server — see SETUP step 3)
#   * writes ~/backups/last_success.txt on success — /admin/backups reads this
#     and shows a red warning when it goes stale
#
# SETUP (once, over SSH):
#   1. Database credentials — create ~/.my.cnf so no password lives in this
#      script or in crontab:
#          printf '[client]\nuser=DBUSER\npassword="DBPASS"\nhost=mysql.jamen.dk\n' > ~/.my.cnf
#          chmod 600 ~/.my.cnf
#   2. Cron — DreamHost panel → Advanced → Cron Jobs → Add:
#          command:  /home/YOURUSER/jamen.dk/scripts/backup.sh
#          schedule: daily, some quiet hour (e.g. 04:12)
#          leave "email output" on — cron only produces output on FAILURE,
#          so any email you get from it is a real alarm.
#   3. Offsite (recommended) — panel → Billing & Account → Backups user gives
#      you a free 50GB space on backup.dreamhost.com. Then:
#          echo 'b1234567@backup.dreamhost.com:dreamboard/' > ~/.backup_remote
#      (set up an SSH key for it so rsync runs unattended)
#
# Restore (database):
#     gunzip < ~/backups/db/jamen_dk-YYYY-MM-DD.sql.gz | mysql jamen_dk
# Restore (files):
#     tar -xzf ~/backups/files/storage-YYYY-MM-DD.tar.gz -C ~/jamen.dk
# =============================================================================
set -u

DB_NAME="jamen_dk"
SITE_DIR="$HOME/jamen.dk"
BASE="$HOME/backups"
DB_DIR="$BASE/db"
FILES_DIR="$BASE/files"
STAMP="$(date +%F)"

mkdir -p "$DB_DIR" "$FILES_DIR"

fail() {
    # Anything on stdout/stderr makes DreamHost cron send mail — that's the alarm.
    echo "BACKUP FAILED: $1" >&2
    exit 1
}

# ── 1. Database ──────────────────────────────────────────────────────────────
DB_OUT="$DB_DIR/${DB_NAME}-${STAMP}.sql.gz"
# --single-transaction: consistent InnoDB snapshot without locking the live site
# --routines/--triggers: don't silently lose stored logic if we ever add any
mysqldump --single-transaction --routines --triggers "$DB_NAME" 2>/tmp/backup_err.$$ \
    | gzip > "$DB_OUT" || fail "mysqldump: $(cat /tmp/backup_err.$$ 2>/dev/null)"
rm -f /tmp/backup_err.$$

# Verify: readable gzip, and big enough to plausibly be the real database.
gzip -t "$DB_OUT" 2>/dev/null || fail "dump is not a valid gzip: $DB_OUT"
SIZE=$(stat -c %s "$DB_OUT" 2>/dev/null || stat -f %z "$DB_OUT")
[ "$SIZE" -ge 10240 ] || fail "dump suspiciously small (${SIZE} bytes): $DB_OUT"

# ── 2. Uploaded files (weekly — they're big and change slower) ───────────────
if [ "$(date +%u)" = "7" ] || [ "${FORCE_FILES:-0}" = "1" ]; then
    FILES_OUT="$FILES_DIR/storage-${STAMP}.tar.gz"
    if [ -d "$SITE_DIR/storage" ]; then
        tar --exclude='storage/cache' --exclude='storage/logs' \
            -czf "$FILES_OUT" -C "$SITE_DIR" storage \
            || fail "tar of storage/ failed"
        gzip -t "$FILES_OUT" 2>/dev/null || fail "storage archive is not a valid gzip"
    fi
fi

# ── 3. Retention ─────────────────────────────────────────────────────────────
find "$DB_DIR"    -name '*.sql.gz' -mtime +30 -delete 2>/dev/null
find "$FILES_DIR" -name '*.tar.gz' -mtime +35 -delete 2>/dev/null

# ── 4. Offsite copy (optional but strongly recommended) ──────────────────────
if [ -f "$HOME/.backup_remote" ]; then
    REMOTE="$(head -n1 "$HOME/.backup_remote")"
    rsync -az "$BASE/" "$REMOTE" \
        || fail "offsite rsync to $REMOTE failed (local backup still OK)"
fi

# ── 5. Success marker (read by /admin/backups) ───────────────────────────────
date '+%Y-%m-%d %H:%M:%S' > "$BASE/last_success.txt"

# Silence = success: no output means DreamHost cron sends no mail.
exit 0
