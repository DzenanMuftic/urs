#!/bin/bash

# WordPress Deployment Script for 65.108.212.64
# This script installs WordPress, dependencies, and deploys the URS plugin

SERVER_IP="65.108.212.64"
SERVER_USER="root"  # Change this to your username
WP_PATH="/var/www/html"
PLUGIN_NAME="my-custom-login-plugin"

echo "=== WordPress Deployment Script ==="
echo "Target Server: $SERVER_IP"
echo "WordPress Path: $WP_PATH"

# Function to run commands on remote server
run_remote() {
    ssh $SERVER_USER@$SERVER_IP "$1"
}

# Function to copy files to remote server
copy_to_remote() {
    scp -r "$1" $SERVER_USER@$SERVER_IP:"$2"
}

echo "Step 1: Installing system dependencies..."
run_remote "apt update && apt install -y apache2 php php-mysql php-curl php-gd php-mbstring php-xml php-zip wget unzip mysql-server"

echo "Step 2: Starting Apache and MySQL services..."
run_remote "systemctl start apache2 && systemctl enable apache2"
run_remote "systemctl start mysql && systemctl enable mysql"

echo "Step 3: Configuring Apache for WordPress..."
run_remote "chown -R www-data:www-data $WP_PATH"
run_remote "chmod -R 755 $WP_PATH"

echo "Step 4: Downloading and installing WordPress..."
run_remote "cd /tmp && wget https://wordpress.org/latest.tar.gz"
run_remote "cd /tmp && tar xzf latest.tar.gz"
run_remote "cp -R /tmp/wordpress/* $WP_PATH/"
run_remote "chown -R www-data:www-data $WP_PATH"

echo "Step 5: Creating WordPress configuration..."
run_remote "cd $WP_PATH && cp wp-config-sample.php wp-config.php"

# Note: You'll need to manually configure the database settings in wp-config.php
echo "Step 6: Setting up MySQL database..."
run_remote "mysql -e \"CREATE DATABASE IF NOT EXISTS wordpress;\""
run_remote "mysql -e \"CREATE USER IF NOT EXISTS 'wpuser'@'localhost' IDENTIFIED BY 'wppassword';\""
run_remote "mysql -e \"GRANT ALL PRIVILEGES ON wordpress.* TO 'wpuser'@'localhost';\""
run_remote "mysql -e \"FLUSH PRIVILEGES;\""

echo "Step 7: Updating wp-config.php with database settings..."
run_remote "sed -i \"s/database_name_here/wordpress/g\" $WP_PATH/wp-config.php"
run_remote "sed -i \"s/username_here/wpuser/g\" $WP_PATH/wp-config.php"
run_remote "sed -i \"s/password_here/wppassword/g\" $WP_PATH/wp-config.php"

echo "Step 8: Creating plugins directory..."
run_remote "mkdir -p $WP_PATH/wp-content/plugins/$PLUGIN_NAME"

echo "Step 9: Deploying URS plugin files..."
copy_to_remote "*.php" "$WP_PATH/wp-content/plugins/$PLUGIN_NAME/"
copy_to_remote "*.png" "$WP_PATH/wp-content/plugins/$PLUGIN_NAME/"
copy_to_remote "*.sql" "$WP_PATH/wp-content/plugins/$PLUGIN_NAME/"
copy_to_remote "README.md" "$WP_PATH/wp-content/plugins/$PLUGIN_NAME/"

echo "Step 10: Setting proper permissions..."
run_remote "chown -R www-data:www-data $WP_PATH/wp-content/plugins/$PLUGIN_NAME"
run_remote "chmod -R 755 $WP_PATH/wp-content/plugins/$PLUGIN_NAME"

echo "Step 11: Restarting Apache..."
run_remote "systemctl restart apache2"

echo "Step 12: Configuring firewall (if needed)..."
run_remote "ufw allow 80/tcp && ufw allow 443/tcp"

echo ""
echo "=== Deployment Complete! ==="
echo ""
echo "Next steps:"
echo "1. Visit http://$SERVER_IP to complete WordPress setup"
echo "2. Create an admin user during WordPress installation"
echo "3. Log into WordPress admin at http://$SERVER_IP/wp-admin"
echo "4. Go to Plugins and activate 'My Custom Login Plugin'"
echo "5. Test the plugin with shortcodes [custom_login_form] and [custom_user_dashboard]"
echo ""
echo "Plugin files deployed to: $WP_PATH/wp-content/plugins/$PLUGIN_NAME"
echo ""

# Check if WordPress is accessible
echo "Testing WordPress accessibility..."
if curl -s http://$SERVER_IP | grep -q "WordPress"; then
    echo "✅ WordPress is accessible at http://$SERVER_IP"
else
    echo "❌ WordPress might not be accessible. Check Apache configuration."
fi