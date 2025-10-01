#!/bin/bash
# Run this script on the target server (65.108.212.64)

echo "Installing WordPress and dependencies..."

# Update system
apt update

# Install LAMP stack
apt install -y apache2 php php-mysql php-curl php-gd php-mbstring php-xml php-zip php-json mysql-server wget unzip

# Start services
systemctl start apache2
systemctl enable apache2
systemctl start mysql
systemctl enable mysql

# Download WordPress
cd /tmp
wget https://wordpress.org/latest.tar.gz
tar xzf latest.tar.gz

# Install WordPress
cp -R /tmp/wordpress/* /var/www/html/
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Create database
mysql -e "CREATE DATABASE IF NOT EXISTS wordpress;"
mysql -e "CREATE USER IF NOT EXISTS 'wpuser'@'localhost' IDENTIFIED BY 'wppassword123';"
mysql -e "GRANT ALL PRIVILEGES ON wordpress.* TO 'wpuser'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Configure WordPress
cd /var/www/html
cp wp-config-sample.php wp-config.php
sed -i "s/database_name_here/wordpress/g" wp-config.php
sed -i "s/username_here/wpuser/g" wp-config.php
sed -i "s/password_here/wppassword123/g" wp-config.php

# Create plugin directory
mkdir -p /var/www/html/wp-content/plugins/my-custom-login-plugin

echo "WordPress installation complete!"
echo "Upload plugin files to: /var/www/html/wp-content/plugins/my-custom-login-plugin/"
echo "Visit http://$(hostname -I | awk '{print $1}') to complete setup"
