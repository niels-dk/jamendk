#!/bin/bash
# =============================================================================
# Merely a Dream backup — database nightly, uploaded files weekly.
#
# Designed for DreamHost shared hosting. Run by cron (see SETUP below) and
# safe to run by hand any time — e.g. right BEFORE executing a migration:
#
#     ~/merelyadream.com/scripts/backup.sh
#
# What it does
#   * mysqldump of the app database → gzip → ~/backups/db/DBNAME-YYYY-MM-DD.sql.gz
#     (one per day; re-running the same day overwrites — that's the point when
#     you run it manually before a migration)
#   * on Sundays (or FORCE_FILES=1): tar of the site's storage/ (uploaded media,
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
#          printf '[client]\nuser=DBUSER\npassword="DBPASS"\nhost=mysql.merelyadream.com\n' > ~/.my.cnf
#          chmod 600 ~/.my.cnf
#   2. Cron — DreamHost panel → Advanced → Cron Jobs → Add:
#          command:  /home/YOURUSER/merelyadream.com/scripts/backup.sh
#          schedule: daily, some quiet hour (e.g. 04:12)
#          leave "email output" on — cron only produces output on FAILURE,
#          so any email you get from it is a real alarm.
#   3. Offsite (recommended) — panel → Billing & Account → Backups user gives
#      you a free 50GB space on backup.dreamhost.com. Then:
#          echo 'b1234567@backup.dreamhost.com:merelyadream/' > ~/.backup_remote
#      (set up an SSH key for it so rsync runs unattended)
#
# Restore (database):
#     gunzip < ~/backups/db/DBNAME-YYYY-MM-DD.sql.gz | mysql YOUR_DB
# Restore (files):
#     tar -xzf ~/backups/files/storage-YYYY-MM-DD.tar.gz -C ~/merelyadream.com
# =============================================================================
set -u

# Derived, not hardcoded, so a domain/folder rename can never silently break
# backups: the site dir is wherever this script lives, and the database name is
# read from the app's own config. The two can't drift apart.
SITE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BASE="$HOME/backups"
DB_DIR="$BASE/db"
FILES_DIR="$BASE/files"
STAMP="$(date +%F)"

mkdir -p "$DB_DIR" "$FILES_DIR"

# Every run leaves its outcome here, success or failure. /admin/backups reads
# it, so a run that FAILED is distinguishable from one that never happened —
# from mtimes alone both look identical, which is how eight nights of 378-byte
# dumps once went unnoticed.
STATUS_FILE="$BASE/last_run"

write_status() {
    # "<epoch>\t<ok|fail>\t<message>"
    printf '%s\t%s\t%s\n' "$(date +%s)" "$1" "$2" > "$STATUS_FILE" 2>/dev/null || true
}

fail() {
    write_status fail "$1"
    # Anything on stdout/stderr makes DreamHost cron send mail — that's the alarm.
    echo "BACKUP FAILED: $1" >&2
    exit 1
}

# Read the dbname out of the app's own DSN. Ignore commented-out lines so an
# old config left in place can't win — that silently backs up the wrong (or an
# empty) database, which is exactly the sort of failure a backup must not have.
DB_NAME="$(grep -v '^[[:space:]]*//' "$SITE_DIR/app/config.php" 2>/dev/null \
           | grep -v '^[[:space:]]*#' \
           | sed -n "s/.*dbname=\([A-Za-z0-9_]*\).*/\1/p" | head -1)"
[ -n "$DB_NAME" ] || fail "could not read dbname from $SITE_DIR/app/config.php"

# Confirm it actually contains tables BEFORE dumping. Catches a valid-but-empty
# database immediately, and names it, instead of leaving you to infer it from a
# filename in a failure message.
TABLE_COUNT="$(mysql -N -B -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" \
  2>/dev/null || echo 0)"
[ "${TABLE_COUNT:-0}" -gt 0 ] \
  || fail "database '$DB_NAME' has no tables (read from app/config.php). Backing up the wrong database?"

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
[ "$SIZE" -ge 10240 ] \
  || fail "dump of database '$DB_NAME' suspiciously small (${SIZE} bytes, $TABLE_COUNT tables): $DB_OUT"

# ── 2. Uploaded files (weekly — they're big and change slower) ───────────────
# Driven by how old the newest archive is, NOT by the day of the week. A cron
# that misses its Sunday used to skip a whole week — and one that fires
# irregularly could miss every Sunday and never archive at all.
NEWEST_ARCHIVE_AGE_DAYS=99
if [ -d "$FILES_DIR" ]; then
    NEWEST="$(ls -t "$FILES_DIR"/*.tar.gz 2>/dev/null | head -1)"
    if [ -n "$NEWEST" ]; then
        NEWEST_MTIME="$(stat -c %Y "$NEWEST" 2>/dev/null || stat -f %m "$NEWEST" 2>/dev/null || echo 0)"
        NEWEST_ARCHIVE_AGE_DAYS=$(( ( $(date +%s) - NEWEST_MTIME ) / 86400 ))
    fi
fi

if [ "$NEWEST_ARCHIVE_AGE_DAYS" -ge 7 ] || [ "${FORCE_FILES:-0}" = "1" ]; then
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

write_status ok "database $DB_NAME (${SIZE} bytes, $TABLE_COUNT tables)"

# ── 5. Success marker (read by /admin/backups) ───────────────────────────────
date '+%Y-%m-%d %H:%M:%S' > "$BASE/last_success.txt"

# Silence = success: no output means DreamHost cron sends no mail.
exit 0
