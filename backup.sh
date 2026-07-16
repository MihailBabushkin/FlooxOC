#!/bin/bash
# ============================================
# Файл: backup.sh
# Назначение: Создание бэкапа базы данных
# ============================================

# Настройки
DB_NAME="activation_system"
DB_USER="app_user"
DB_PASS="YOUR_PASSWORD_HERE"  # ← СМЕНИ ПАРОЛЬ!
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# Создаём папку
mkdir -p $BACKUP_DIR

# Делаем дамп
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/backup_$DATE.sql"

# Удаляем старые бэкапы (старше 30 дней)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete

echo "✅ Бэкап создан: $BACKUP_DIR/backup_$DATE.sql"
