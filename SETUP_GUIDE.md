# 🚀 Automatic Deployment Setup Guide

## Complete Guide for Automatic Deployment from GitHub to http://65.108.212.64/

This guide will help you set up automatic deployment from GitHub to your server so that every time you push code to the main branch, it automatically deploys to http://65.108.212.64/.

---

## 📋 Prerequisites

- Access to server `65.108.212.64` with SSH
- Root or sudo access on the server
- GitHub repository access with admin permissions

---

## 🔐 Step 1: Generate SSH Keys for GitHub Actions

SSH keys are needed so GitHub Actions can connect to your server securely.

### On Your Local Machine or Server:

```bash
# Generate a new SSH key pair specifically for GitHub Actions
ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key -C "github-actions-deploy"

# This creates two files:
# - github_actions_deploy_key (private key - for GitHub)
# - github_actions_deploy_key.pub (public key - for server)
```

**Important:** Don't set a passphrase when prompted (just press Enter), as GitHub Actions needs to use this key automatically.

---

## 🔑 Step 2: Install Public Key on Server

Copy the **public key** to your server's authorized_keys:

```bash
# Method 1: Using ssh-copy-id (recommended)
ssh-copy-id -i ~/.ssh/github_actions_deploy_key.pub root@65.108.212.64

# Method 2: Manual copy
cat ~/.ssh/github_actions_deploy_key.pub | ssh root@65.108.212.64 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"

# Verify SSH connection works
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64 "echo 'SSH connection successful!'"
```

---

## 🔒 Step 3: Add Secrets to GitHub Repository

GitHub Secrets store sensitive information securely for GitHub Actions.

### Navigate to Repository Secrets:
1. Go to your repository: https://github.com/DzenanMuftic/urs
2. Click **Settings** (top menu)
3. Click **Secrets and variables** → **Actions** (left sidebar)
4. Click **New repository secret**

### Add These 4 Secrets:

#### Secret 1: `SERVER_HOST`
- Name: `SERVER_HOST`
- Value: `65.108.212.64`
- Click **Add secret**

#### Secret 2: `SERVER_USER`
- Name: `SERVER_USER`
- Value: `root` (or your SSH username)
- Click **Add secret**

#### Secret 3: `SERVER_PORT`
- Name: `SERVER_PORT`
- Value: `22`
- Click **Add secret**

#### Secret 4: `SERVER_SSH_KEY`
- Name: `SERVER_SSH_KEY`
- Value: Copy the **entire private key** content:
  ```bash
  cat ~/.ssh/github_actions_deploy_key
  ```
- Copy everything including:
  ```
  -----BEGIN OPENSSH PRIVATE KEY-----
  ...
  -----END OPENSSH PRIVATE KEY-----
  ```
- Paste into Value field
- Click **Add secret**

---

## 🖥️ Step 4: Prepare the Server (First Time Only)

Run the server setup workflow to install WordPress and dependencies:

### Option A: Using GitHub Actions (Recommended)

1. Go to **Actions** tab in GitHub: https://github.com/DzenanMuftic/urs/actions
2. Click **Setup WordPress Server** workflow
3. Click **Run workflow** button (right side)
4. Enter server IP: `65.108.212.64`
5. Click **Run workflow**
6. Wait for completion (5-10 minutes)

### Option B: Manual Setup via SSH

```bash
# SSH to your server
ssh root@65.108.212.64

# Run this installation script
curl -fsSL https://raw.githubusercontent.com/DzenanMuftic/urs/main/deploy.sh | bash
```

### After Setup Completes:

1. Visit http://65.108.212.64 in your browser
2. Complete WordPress installation:
   - Choose language
   - Set site title
   - Create admin username and password
   - Enter admin email
   - Click **Install WordPress**
3. Login to WordPress admin at http://65.108.212.64/wp-admin

---

## ✅ Step 5: Verify Automatic Deployment

Now test that automatic deployment works!

### Test Push-to-Deploy:

1. Make a small change to any file in your repository (e.g., edit README.md)
2. Commit and push to main branch:
   ```bash
   git add .
   git commit -m "Test automatic deployment"
   git push origin main
   ```
3. Go to **Actions** tab: https://github.com/DzenanMuftic/urs/actions
4. You should see a new workflow run **"Deploy WordPress Plugin to Server"**
5. Click on it to see deployment progress
6. Wait for all steps to complete (green checkmarks ✅)

### Verify on Server:

1. SSH to server: `ssh root@65.108.212.64`
2. Check plugin files:
   ```bash
   ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/
   ```
3. Check Apache status:
   ```bash
   systemctl status apache2
   ```

### Activate Plugin in WordPress:

1. Login to WordPress admin: http://65.108.212.64/wp-admin
2. Go to **Plugins** → **Installed Plugins**
3. Find **My Custom Login Plugin**
4. Click **Activate**
5. Plugin is now live! 🎉

---

## 🔄 How Automatic Deployment Works

Once set up, here's what happens automatically:

1. **You push code** to `main` branch
   ```bash
   git push origin main
   ```

2. **GitHub Actions triggers** automatically
   - Detects push to main branch
   - Starts deployment workflow

3. **Deployment workflow executes:**
   - ✅ Creates backup of current plugin
   - ✅ Packages new plugin files
   - ✅ Connects to server via SSH
   - ✅ Copies files to server
   - ✅ Sets proper permissions
   - ✅ Restarts Apache
   - ✅ Verifies deployment

4. **Your changes are live** at http://65.108.212.64/

**No manual deployment needed!** Just push your code.

---

## 🔍 Monitoring Deployments

### Check Deployment Status:

1. **GitHub Actions Tab**
   - https://github.com/DzenanMuftic/urs/actions
   - Shows all workflow runs
   - Green ✅ = Success
   - Red ❌ = Failed (check logs)

2. **Workflow Logs**
   - Click on any workflow run
   - Click on job name
   - See detailed logs for each step

3. **Server Logs**
   ```bash
   # SSH to server
   ssh root@65.108.212.64
   
   # Check Apache logs
   tail -f /var/log/apache2/wordpress_error.log
   
   # Check Apache access logs
   tail -f /var/log/apache2/wordpress_access.log
   ```

---

## 🛠️ Troubleshooting

### Problem: Deployment Fails with SSH Error

**Solution:**
1. Verify SSH key is added to server:
   ```bash
   ssh root@65.108.212.64 "cat ~/.ssh/authorized_keys"
   ```
2. Check GitHub secret `SERVER_SSH_KEY` contains complete private key
3. Test SSH connection manually:
   ```bash
   ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64
   ```

### Problem: Permission Denied Errors

**Solution:**
1. Check server user has sudo access:
   ```bash
   ssh root@65.108.212.64 "sudo ls -la /var/www/html"
   ```
2. Verify file permissions on server:
   ```bash
   ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/"
   ```

### Problem: Files Not Updating on Server

**Solution:**
1. Check workflow completed successfully in Actions tab
2. Manually verify files on server:
   ```bash
   ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"
   ```
3. Check Apache is running:
   ```bash
   ssh root@65.108.212.64 "systemctl status apache2"
   ```
4. Clear WordPress cache in admin panel

### Problem: Workflow Doesn't Trigger

**Solution:**
1. Verify you pushed to `main` branch (not other branches)
2. Check `.github/workflows/deploy.yml` exists in repository
3. Check Actions are enabled in repository settings
4. Try manual trigger:
   - Go to Actions tab
   - Click "Deploy WordPress Plugin to Server"
   - Click "Run workflow"

### Problem: Apache/WordPress Not Working

**Solution:**
1. Restart Apache:
   ```bash
   ssh root@65.108.212.64 "sudo systemctl restart apache2"
   ```
2. Check Apache configuration:
   ```bash
   ssh root@65.108.212.64 "sudo apache2ctl configtest"
   ```
3. Check if port 80 is open:
   ```bash
   ssh root@65.108.212.64 "sudo ufw status"
   ```

---

## 📊 Deployment Workflow Details

### What Gets Deployed:
- All `.php` files
- All `.png` image files
- All `.sql` database files
- `README.md` documentation

### Where Files Go:
```
Server Location: /var/www/html/wp-content/plugins/my-custom-login-plugin/
├── my-custom-login-plugin.php (main plugin file)
├── admin-dashboard.php
├── login-form.php
├── user-dashboard.php
├── index.php
├── *.png (images)
└── README.md
```

### Automatic Actions:
1. **Backup Creation**: Before deployment, current plugin backed up to:
   `/var/www/html/wp-content/plugins/my-custom-login-plugin.backup.YYYYMMDD_HHMMSS`

2. **Permission Setting**: All files automatically set to:
   - Owner: `www-data:www-data`
   - Permissions: `755`

3. **Apache Reload**: Apache automatically reloaded to apply changes

---

## 🔐 Security Best Practices

1. **Never commit secrets to repository**
   - Always use GitHub Secrets
   - Never put passwords in code

2. **Keep SSH keys secure**
   - Private key only in GitHub Secrets
   - Public key only on server

3. **Regular backups**
   - Automatic backups created before each deployment
   - Manually backup database regularly:
     ```bash
     ssh root@65.108.212.64 "mysqldump -u wpuser -p wordpress > /backup/wordpress_$(date +%Y%m%d).sql"
     ```

4. **Monitor deployment logs**
   - Check Actions tab after each deployment
   - Review Apache logs for errors

---

## 📚 Additional Resources

- **GitHub Actions Documentation**: https://docs.github.com/en/actions
- **GitHub Secrets**: https://docs.github.com/en/actions/security-guides/encrypted-secrets
- **WordPress Plugin Development**: https://developer.wordpress.org/plugins/
- **Repository Deployment Guide**: See `.github/DEPLOYMENT.md`

---

## ✅ Quick Setup Checklist

Use this checklist to ensure everything is configured:

- [ ] SSH keys generated (`github_actions_deploy_key`)
- [ ] Public key installed on server (in `~/.ssh/authorized_keys`)
- [ ] GitHub Secret `SERVER_HOST` = `65.108.212.64`
- [ ] GitHub Secret `SERVER_USER` = `root`
- [ ] GitHub Secret `SERVER_PORT` = `22`
- [ ] GitHub Secret `SERVER_SSH_KEY` = (private key content)
- [ ] Server setup completed (WordPress installed)
- [ ] WordPress setup completed (admin account created)
- [ ] Test deployment successful (push to main triggers workflow)
- [ ] Plugin activated in WordPress admin
- [ ] Plugin working on http://65.108.212.64/

---

## 🎉 Success!

Once all steps are complete, your automatic deployment is active!

**Every push to `main` branch will automatically deploy to http://65.108.212.64/**

Just code, commit, push - and your changes are live! 🚀
