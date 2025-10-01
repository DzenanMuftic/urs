# GitHub Actions Deployment Setup

This repository includes automated deployment workflows for the URS WordPress plugin.

## 🔧 Setup Required

### 1. GitHub Secrets Configuration

Go to your repository Settings → Secrets and variables → Actions, and add these secrets:

```
SERVER_HOST=65.108.212.64
SERVER_USER=root
SERVER_PORT=22
SERVER_SSH_KEY=<your-private-ssh-key>
```

### 2. SSH Key Setup

Generate an SSH key pair if you don't have one:

```bash
ssh-keygen -t rsa -b 4096 -f ~/.ssh/deploy_key
```

- Copy the **private key** (`~/.ssh/deploy_key`) content to `SERVER_SSH_KEY` secret
- Copy the **public key** (`~/.ssh/deploy_key.pub`) to your server's `~/.ssh/authorized_keys`

### 3. Server Preparation

Ensure your server allows SSH connections and has sudo access.

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

## 🔍 Monitoring

Check workflow status in the Actions tab:
- ✅ Green checkmark = successful deployment
- ❌ Red X = deployment failed (check logs)

## 🛠️ Manual Deployment (Fallback)

If GitHub Actions fails, use the manual scripts:

```bash
# Upload and run
scp urs-plugin.tar.gz user@65.108.212.64:~/
ssh user@65.108.212.64
tar -xzf urs-plugin.tar.gz
cd urs-plugin-deploy
sudo bash install-wordpress.sh
```

## 📁 File Structure

```
.github/
├── workflows/
│   ├── deploy.yml          # Plugin deployment
│   └── setup-server.yml    # Server setup
```

## 🔐 Security Notes

- SSH keys are stored securely in GitHub Secrets
- Backups are created before each deployment
- Proper file permissions are set automatically
- Firewall rules are configured during setup