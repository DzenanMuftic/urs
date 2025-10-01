#!/bin/bash

# Local deployment script - Alternative approach
# This script prepares files and provides manual deployment instructions

echo "=== URS Plugin Local Deployment Preparation ==="

# Create deployment package
DEPLOY_DIR="urs-plugin-deploy"
mkdir -p $DEPLOY_DIR

echo "Copying plugin files..."
cp *.php $DEPLOY_DIR/
cp *.png $DEPLOY_DIR/
cp *.sql $DEPLOY_DIR/
cp README.md $DEPLOY_DIR/

echo "Creating deployment archive..."
tar -czf urs-plugin.tar.gz $DEPLOY_DIR/

echo "Creating WordPress installation script..."
cat > $DEPLOY_DIR/install-wordpress.sh << 'EOF'
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
EOF

chmod +x $DEPLOY_DIR/install-wordpress.sh

echo ""
echo "=== Deployment Package Created ==="
echo ""
echo "Files prepared in: $DEPLOY_DIR/"
echo "Archive created: urs-plugin.tar.gz"
echo ""
echo "Manual Deployment Steps:"
echo "1. Upload the archive to your server:"
echo "   scp urs-plugin.tar.gz user@65.108.212.64:~/"
echo ""
echo "2. SSH to the server:"
echo "   ssh user@65.108.212.64"
echo ""
echo "3. Extract and run installation:"
echo "   tar -xzf urs-plugin.tar.gz"
echo "   cd $DEPLOY_DIR"
echo "   sudo bash install-wordpress.sh"
echo ""
echo "4. Copy plugin files:"
echo "   sudo cp *.php /var/www/html/wp-content/plugins/my-custom-login-plugin/"
echo "   sudo cp *.png /var/www/html/wp-content/plugins/my-custom-login-plugin/"
echo "   sudo cp *.sql /var/www/html/wp-content/plugins/my-custom-login-plugin/"
echo "   sudo cp README.md /var/www/html/wp-content/plugins/my-custom-login-plugin/"
echo ""
echo "5. Set permissions:"
echo "   sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/my-custom-login-plugin"
echo ""
echo "6. Visit http://65.108.212.64 to complete WordPress setup"
echo "7. Activate the plugin in WordPress admin"
echo ""