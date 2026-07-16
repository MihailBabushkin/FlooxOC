#!/bin/bash
# ============================================
# Файл: install.sh
# Назначение: Автоматическая установка сервера
# ============================================

echo "🚀 Начинаем установку сервера активации..."

# Обновление системы
echo "📦 Обновление системы..."
sudo apt update && sudo apt upgrade -y

# Установка LAMP
echo "📦 Установка LAMP стека..."
sudo apt install -y apache2 mysql-server php php-mysql php-curl php-json php-mbstring php-xml php-zip php-gd

# Установка дополнительных утилит
sudo apt install -y curl wget git nano unzip

# Создание папки для API
sudo mkdir -p /var/www/html/api

# Копирование файлов (предполагается, что они в текущей папке)
echo "📁 Копирование файлов..."
sudo cp activate.php /var/www/html/api/
sudo cp check_code.php /var/www/html/api/
sudo cp generate_codes.php /var/www/html/api/
sudo cp get_stats.php /var/www/html/api/
sudo cp admin_panel.php /var/www/html/api/
sudo cp .htaccess /var/www/html/api/

# Настройка прав
sudo chown -R www-data:www-data /var/www/html/api
sudo chmod -R 755 /var/www/html/api

# Настройка Apache
sudo systemctl enable apache2
sudo systemctl restart apache2

# Настройка MySQL
sudo systemctl enable mysql
sudo systemctl restart mysql

echo "✅ Установка завершена!"
echo "🌐 Откройте в браузере: http://$(hostname -I | awk '{print $1}')/api/admin_panel.php"
echo "🔑 Не забудьте настроить пароль в файлах!"
