#!/bin/bash

BACKUP_DIR="/home/wblc8418/backups"
DB_NAME="wblc8418_mic"
UPLOADS_DIR="/home/wblc8418/public_html/mic/writable/uploads"
TIMESTAMP=$(date +%Y%m%d_%H%M)

# Backup database
mysqldump "$DB_NAME" > "$BACKUP_DIR/mic_db_${TIMESTAMP}.sql" 2>&1

# Backup uploads
tar -czf "$BACKUP_DIR/mic_uploads_${TIMESTAMP}.tar.gz" "$UPLOADS_DIR" 2>&1

# Hapus backup DB lebih dari 30 hari
find "$BACKUP_DIR" -name "mic_db_*.sql" -mtime +30 -delete

# Hapus backup uploads lebih dari 30 hari
find "$BACKUP_DIR" -name "mic_uploads_*.tar.gz" -mtime +30 -delete

echo "[$(date '+%Y-%m-%d %H:%M')] Backup selesai: mic_db_${TIMESTAMP}.sql + mic_uploads_${TIMESTAMP}.tar.gz"
