# GitHub Actions Deployment Setup

This repository includes automated deployment workflows for the URS WordPress plugin.

## 🚀 Quick Start

**New to automatic deployment?** See the complete [SETUP_GUIDE.md](../SETUP_GUIDE.md) for step-by-step instructions.

## 🔧 Setup Required

### 1. GitHub Secrets Configuration

Go to your repository Settings → Secrets and variables → Actions, and add these secrets:

| Secret Name | Value | Description |
|-------------|-------|-------------|
| `SERVER_HOST` | `65.108.212.64` | Your server IP address |
| `SERVER_USER` | `root` | SSH username (typically root) |
| `SERVER_PORT` | `22` | SSH port (typically 22) |
| `SERVER_SSH_KEY` | `<private-key>` | Your private SSH key content |

### 2. SSH Key Setup

Generate an SSH key pair if you don't have one:

```bash
# Generate SSH key for GitHub Actions
ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key -C "github-actions-deploy"

# No passphrase needed (press Enter when prompted)
```

**Install keys:**
- Copy the **private key** (`~/.ssh/github_actions_deploy_key`) content to `SERVER_SSH_KEY` secret
- Copy the **public key** to your server:
  ```bash
  ssh-copy-id -i ~/.ssh/github_actions_deploy_key.pub root@65.108.212.64
  ```

**Verify SSH access:**
```bash
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64 "echo 'Connection successful!'"
```

### 3. Server Preparation

Ensure your server allows SSH connections and has sudo access.

**Quick server check:**
```bash
ssh root@65.108.212.64 "uname -a && php -v && mysql --version"
```

## 🚀 Deployment Workflows

### Workflow 1: Setup WordPress Server
**File:** `.github/workflows/setup-server.yml`

**Purpose:** Install WordPress, Apache, PHP, MySQL on a fresh server

**Trigger:** Manual (workflow_dispatch)

**Steps:**
1. Go to Actions tab in GitHub
2. Select "Setup WordPress Server"
3. Click "Run workflow"
4. Enter server IP (default: 65.108.212.64)
5. Click "Run workflow"

### Workflow 2: Deploy Plugin
**File:** `.github/workflows/deploy.yml`

**Purpose:** Deploy URS plugin files to WordPress installation

**Triggers:**
- Push to main branch (automatic)
- Pull request to main branch
- Manual (workflow_dispatch)

**What it does:**
1. ✅ Creates backup of existing plugin
2. 📦 Packages plugin files
3. 🚀 Deploys to server via SSH/SCP
4. 🔧 Sets proper permissions
5. ✅ Verifies deployment

## 📋 Deployment Process

### First Time Setup:
1. **Configure secrets** in GitHub repository
2. **Run "Setup WordPress Server"** workflow
3. **Complete WordPress setup** via web interface at `http://65.108.212.64`
4. **Run "Deploy Plugin"** workflow or push to main branch

### Regular Updates:
- Just push changes to main branch
- Deployment happens automatically
- Plugin files are updated on server

## 🔍 Monitoring & Verification

### Check Deployment Status

**Via GitHub:**
1. Go to **Actions** tab: https://github.com/DzenanMuftic/urs/actions
2. View workflow runs:
   - ✅ Green checkmark = successful deployment
   - ❌ Red X = deployment failed (check logs)
3. Click on any run to see detailed logs

**Via Server:**
```bash
# SSH to server and check plugin files
ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"

# Check Apache status
ssh root@65.108.212.64 "systemctl status apache2"

# View Apache error logs
ssh root@65.108.212.64 "tail -n 50 /var/log/apache2/wordpress_error.log"
```

**Via WordPress:**
1. Login to admin: http://65.108.212.64/wp-admin
2. Go to Plugins → Installed Plugins
3. Verify "My Custom Login Plugin" is present
4. Activate if needed

## 🛠️ Troubleshooting

### Deployment Fails with SSH Error
```bash
# Test SSH connection manually
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64

# Verify SSH key in authorized_keys
ssh root@65.108.212.64 "cat ~/.ssh/authorized_keys"

# Check GitHub secret is set correctly
# Go to Settings → Secrets → Actions → SERVER_SSH_KEY
```

### Permission Errors
```bash
# Fix permissions on server
ssh root@65.108.212.64 "sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/my-custom-login-plugin"
ssh root@65.108.212.64 "sudo chmod -R 755 /var/www/html/wp-content/plugins/my-custom-login-plugin"
```

### Workflow Not Triggering
1. Verify pushed to `main` branch (not other branches)
2. Check `.github/workflows/deploy.yml` exists
3. Ensure Actions are enabled in Settings → Actions
4. Try manual trigger: Actions → Deploy → Run workflow

### Plugin Not Updating
1. Check workflow completed successfully
2. Clear WordPress cache (if using caching plugin)
3. Hard refresh browser (Ctrl+F5)
4. Check file timestamps on server:
   ```bash
   ssh root@65.108.212.64 "ls -lt /var/www/html/wp-content/plugins/my-custom-login-plugin/"
   ```

## 🔄 Manual Deployment (Fallback)

If GitHub Actions fails, use the manual scripts:

### Method 1: Using deploy.sh
```bash
# From your local machine with SSH access
./deploy.sh
```

### Method 2: Manual upload
```bash
# Upload and run
scp urs-plugin.tar.gz root@65.108.212.64:~/
ssh root@65.108.212.64
tar -xzf urs-plugin.tar.gz
cd urs-plugin-deploy
sudo bash install-wordpress.sh
```

### Method 3: Direct file copy
```bash
# Copy files directly
scp -r *.php root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
scp -r *.png root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
ssh root@65.108.212.64 "sudo systemctl reload apache2"
```

## 📁 File Structure

```
.github/
├── DEPLOYMENT.md           # This file - deployment documentation
├── workflows/
│   ├── deploy.yml          # Automatic plugin deployment
│   └── setup-server.yml    # Server WordPress setup
../SETUP_GUIDE.md          # Complete step-by-step setup guide
../deploy.sh               # Manual deployment script
../deploy-docker.sh        # Docker deployment option
../deploy-local.sh         # Local preparation script
```

## 🔐 Security Notes

- ✅ SSH keys are stored securely in GitHub Secrets
- ✅ Backups are created before each deployment
- ✅ Proper file permissions are set automatically (www-data:www-data, 755)
- ✅ Firewall rules are configured during setup (ports 22, 80, 443)
- ✅ Private keys never committed to repository
- ✅ WordPress salts generated automatically
- ✅ Database passwords generated securely

## 📚 Additional Documentation

- **Complete Setup Guide**: [SETUP_GUIDE.md](../SETUP_GUIDE.md) - Step-by-step instructions for first-time setup
- **WordPress Plugin Info**: [README.md](../README.md) - Plugin features and usage
- **GitHub Actions Docs**: https://docs.github.com/en/actions
- **WordPress Developer**: https://developer.wordpress.org/

## ✅ Setup Verification Checklist

- [ ] GitHub Secrets configured (SERVER_HOST, SERVER_USER, SERVER_PORT, SERVER_SSH_KEY)
- [ ] SSH connection to server working
- [ ] WordPress installed on server (http://65.108.212.64)
- [ ] WordPress admin account created
- [ ] Test deployment workflow successful
- [ ] Plugin activated in WordPress
- [ ] Plugin functioning at http://65.108.212.64/

**Need help?** See the complete [SETUP_GUIDE.md](../SETUP_GUIDE.md) for detailed instructions.