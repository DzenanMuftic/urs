#!/bin/bash

# Docker-based WordPress deployment
# This creates a containerized WordPress environment

echo "=== Docker WordPress Deployment ==="

# Create docker-compose.yml for WordPress
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  wordpress:
    image: wordpress:latest
    ports:
      - "80:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress123
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - wordpress_data:/var/www/html
      - ./urs-plugin-deploy:/var/www/html/wp-content/plugins/my-custom-login-plugin
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress123
      MYSQL_ROOT_PASSWORD: rootpassword123
    volumes:
      - db_data:/var/lib/mysql
    restart: unless-stopped

volumes:
  wordpress_data:
  db_data:
EOF

# Create deployment script for Docker
cat > deploy-docker.sh << 'EOF'
#!/bin/bash
echo "Starting WordPress with Docker..."

# Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    systemctl start docker
    systemctl enable docker
fi

# Install Docker Compose if not present
if ! command -v docker-compose &> /dev/null; then
    echo "Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
fi

# Start WordPress
docker-compose up -d

echo "WordPress is starting..."
echo "Visit http://$(hostname -I | awk '{print $1}') in a few minutes"
echo "Database: wordpress"
echo "DB User: wordpress"
echo "DB Password: wordpress123"
EOF

chmod +x deploy-docker.sh

echo "Docker deployment files created!"
echo ""
echo "To deploy using Docker:"
echo "1. Copy files to server: scp -r . user@65.108.212.64:~/wordpress-deploy/"
echo "2. SSH to server: ssh user@65.108.212.64"
echo "3. Run: cd wordpress-deploy && sudo bash deploy-docker.sh"
echo ""