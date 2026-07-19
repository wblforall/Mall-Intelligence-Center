#!/bin/bash
BACKUP_DIR="/home/wblc8418/backups"
APP_DIR="/home/wblc8418/public_html/mic"
ENV="$APP_DIR/.env"
TIMESTAMP=$(date +%Y%m%d_%H%M)
LOG="$BACKUP_DIR/backup.log"

getenv() { grep -E "^[[:space:]]*$1[[:space:]]*=" "$ENV" | head -1 | sed -E "s/^[^=]*=[[:space:]]*//; s/^['\"]//; s/['\"][[:space:]]*\$//"; }
DB_HOST="$(getenv 'database.default.hostname')"; [ -z "$DB_HOST" ] && DB_HOST="localhost"
DB_NAME="$(getenv 'database.default.database')"
DB_USER="$(getenv 'database.default.username')"
DB_PASS="$(getenv 'database.default.password')"

CNF="$(mktemp)"; chmod 600 "$CNF"
printf '[mysqldump]\nhost=%s\nuser=%s\npassword="%s"\n' "$DB_HOST" "$DB_USER" "$DB_PASS" > "$CNF"

if mysqldump --defaults-file="$CNF" --single-transaction --quick --no-tablespaces "$DB_NAME" > "$BACKUP_DIR/mic_db_${TIMESTAMP}.sql" 2>>"$LOG"; then
    DB_OK="OK ($(du -h "$BACKUP_DIR/mic_db_${TIMESTAMP}.sql" | cut -f1))"
else
    DB_OK="GAGAL (lihat $LOG)"; rm -f "$BACKUP_DIR/mic_db_${TIMESTAMP}.sql"
fi
rm -f "$CNF"

UP=(); for d in writable/uploads public/uploads; do [ -d "$APP_DIR/$d" ] && UP+=("$d"); done
tar -czf "$BACKUP_DIR/mic_uploads_${TIMESTAMP}.tar.gz" -C "$APP_DIR" "${UP[@]}" 2>>"$LOG"

find "$BACKUP_DIR" -name "mic_db_*.sql" -mtime +30 -delete
find "$BACKUP_DIR" -name "mic_uploads_*.tar.gz" -mtime +10 -delete
echo "[$(date '+%Y-%m-%d %H:%M')] DB: $DB_OK | uploads: ${UP[*]}" >> "$LOG"
