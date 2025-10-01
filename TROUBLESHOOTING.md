# Troubleshooting Guide - Automatic Deployment

This guide helps you diagnose and fix common issues with automatic deployment from GitHub to your server.

## 🔍 Quick Diagnostics

### Run Automated Verification
```bash
./verify-setup.sh
```
This script checks your local setup, SSH connection, server configuration, and provides specific recommendations.

---

## 🔴 Common Issues & Solutions

### 1. SSH Connection Failed

**Symptoms:**
- Deployment fails with "Permission denied (publickey)"
- Cannot connect to server
- SSH timeout errors

**Diagnosis:**
```bash
# Test SSH connection manually
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64

# Check if public key is on server
ssh root@65.108.212.64 "cat ~/.ssh/authorized_keys"
```

**Solutions:**

**A. SSH keys not generated:**
```bash
# Generate new SSH key pair
ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key -C "github-actions"
# Don't set a passphrase (press Enter)
```

**B. Public key not installed on server:**
```bash
# Install public key on server
ssh-copy-id -i ~/.ssh/github_actions_deploy_key.pub root@65.108.212.64

# Or manually:
cat ~/.ssh/github_actions_deploy_key.pub | ssh root@65.108.212.64 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

**C. Wrong GitHub secret:**
```bash
# Display private key (copy ALL of it including headers)
cat ~/.ssh/github_actions_deploy_key

# Go to GitHub: Settings → Secrets → Actions
# Update SERVER_SSH_KEY with complete private key content
```

**D. SSH permissions on server:**
```bash
ssh root@65.108.212.64 "chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys"
```

---

### 2. GitHub Actions Workflow Not Triggering

**Symptoms:**
- Push to main doesn't trigger deployment
- No workflow runs in Actions tab
- Manual trigger button missing

**Diagnosis:**
```bash
# Check current branch
git branch

# Check if workflow files exist
ls -la .github/workflows/

# Check git remote
git remote -v
```

**Solutions:**

**A. Not on main branch:**
```bash
# Switch to main branch
git checkout main

# Or create and push main branch
git checkout -b main
git push -u origin main
```

**B. Workflow files missing:**
```bash
# Check if files exist
ls .github/workflows/deploy.yml
ls .github/workflows/setup-server.yml

# If missing, restore from repository or check git
git status
```

**C. Actions disabled in repository:**
1. Go to GitHub repository
2. Settings → Actions → General
3. Enable "Allow all actions and reusable workflows"
4. Save

**D. Workflow syntax error:**
```bash
# Validate workflow syntax locally (requires act)
act -n

# Or check in GitHub Actions tab for error messages
```

---

### 3. Deployment Succeeds but Files Not Updated

**Symptoms:**
- Workflow shows success (green checkmark)
- Server files unchanged
- Old code still running

**Diagnosis:**
```bash
# Check file timestamps on server
ssh root@65.108.212.64 "ls -lt /var/www/html/wp-content/plugins/my-custom-login-plugin/ | head -10"

# Check if deployment actually reached server
ssh root@65.108.212.64 "ls -la /tmp/plugin-deploy/"

# Check Apache serving correct files
curl -I http://65.108.212.64
```

**Solutions:**

**A. WordPress cache:**
```bash
# Clear WordPress cache via SSH
ssh root@65.108.212.64 "rm -rf /var/www/html/wp-content/cache/*"

# Or use WordPress admin:
# 1. Login to wp-admin
# 2. Tools → Clear Cache (if caching plugin installed)
```

**B. Browser cache:**
```
# Hard refresh in browser
Windows/Linux: Ctrl + F5
Mac: Cmd + Shift + R

# Or use private/incognito mode
```

**C. Apache not reloading:**
```bash
# Manually restart Apache
ssh root@65.108.212.64 "sudo systemctl restart apache2"

# Check Apache status
ssh root@65.108.212.64 "sudo systemctl status apache2"
```

**D. File permissions:**
```bash
# Fix permissions on server
ssh root@65.108.212.64 "sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/my-custom-login-plugin"
ssh root@65.108.212.64 "sudo chmod -R 755 /var/www/html/wp-content/plugins/my-custom-login-plugin"
```

---

### 4. GitHub Secrets Not Set or Incorrect

**Symptoms:**
- Workflow fails immediately
- Error: "secret not found"
- Wrong server or path errors

**Diagnosis:**
```bash
# List which secrets are needed
cat .github/workflows/deploy.yml | grep "secrets."
```

**Solutions:**

**Check/Set all required secrets:**

1. Go to: https://github.com/YOUR_USERNAME/urs/settings/secrets/actions

2. Verify these 4 secrets exist:
   - `SERVER_HOST` = `65.108.212.64`
   - `SERVER_USER` = `root` (or your SSH username)
   - `SERVER_PORT` = `22`
   - `SERVER_SSH_KEY` = (complete private key)

3. **For SERVER_SSH_KEY:**
   ```bash
   # Display private key
   cat ~/.ssh/github_actions_deploy_key
   
   # Copy EVERYTHING including:
   -----BEGIN OPENSSH PRIVATE KEY-----
   ...all the lines...
   -----END OPENSSH PRIVATE KEY-----
   ```

4. Update secret in GitHub:
   - Click the secret name
   - Click "Update secret"
   - Paste complete key
   - Save

---

### 5. WordPress or Server Not Accessible

**Symptoms:**
- Cannot access http://65.108.212.64
- "Connection refused" or timeout
- 500 Internal Server Error

**Diagnosis:**
```bash
# Test if server is reachable
ping -c 3 65.108.212.64

# Test if port 80 is open
telnet 65.108.212.64 80

# Or using nc
nc -zv 65.108.212.64 80

# Check from server side
ssh root@65.108.212.64 "curl -I http://localhost"
```

**Solutions:**

**A. Apache not running:**
```bash
ssh root@65.108.212.64 "sudo systemctl start apache2"
ssh root@65.108.212.64 "sudo systemctl enable apache2"
ssh root@65.108.212.64 "sudo systemctl status apache2"
```

**B. Firewall blocking:**
```bash
# Check firewall status
ssh root@65.108.212.64 "sudo ufw status"

# Allow HTTP/HTTPS
ssh root@65.108.212.64 "sudo ufw allow 80/tcp"
ssh root@65.108.212.64 "sudo ufw allow 443/tcp"
ssh root@65.108.212.64 "sudo ufw reload"
```

**C. Apache configuration error:**
```bash
# Test Apache config
ssh root@65.108.212.64 "sudo apache2ctl configtest"

# Check error logs
ssh root@65.108.212.64 "sudo tail -50 /var/log/apache2/error.log"
```

**D. WordPress not installed:**
```bash
# Check if WordPress files exist
ssh root@65.108.212.64 "ls -la /var/www/html/wp-config.php"

# If missing, run setup workflow or:
ssh root@65.108.212.64
bash <(curl -s https://raw.githubusercontent.com/DzenanMuftic/urs/main/deploy.sh)
```

---

### 6. Plugin Files Missing or Incomplete

**Symptoms:**
- Some PHP files missing on server
- Images not loading
- Plugin not showing in WordPress

**Diagnosis:**
```bash
# List plugin files on server
ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"

# Count files
ssh root@65.108.212.64 "ls -1 /var/www/html/wp-content/plugins/my-custom-login-plugin/ | wc -l"

# Compare with local
ls -1 *.php *.png *.sql README.md | wc -l
```

**Solutions:**

**A. Manual file upload:**
```bash
# Upload specific files
scp my-custom-login-plugin.php root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
scp *.php root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
scp *.png root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
```

**B. Re-run deployment:**
```bash
# Manual deployment
./deploy.sh

# Or trigger workflow manually:
# GitHub → Actions → Deploy Plugin → Run workflow
```

**C. Check deployment logs:**
1. Go to Actions tab in GitHub
2. Click on failed workflow run
3. Click on job name
4. Expand each step to see errors
5. Look for SCP/copy errors

---

### 7. Permission Denied Errors

**Symptoms:**
- "Permission denied" in workflow logs
- Cannot write to plugin directory
- 403 Forbidden errors

**Diagnosis:**
```bash
# Check directory ownership
ssh root@65.108.212.64 "ls -ld /var/www/html/wp-content/plugins/my-custom-login-plugin/"

# Check file permissions
ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"
```

**Solutions:**

**A. Fix ownership:**
```bash
ssh root@65.108.212.64 "sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/my-custom-login-plugin"
```

**B. Fix permissions:**
```bash
ssh root@65.108.212.64 "sudo chmod -R 755 /var/www/html/wp-content/plugins/my-custom-login-plugin"
ssh root@65.108.212.64 "sudo find /var/www/html/wp-content/plugins/my-custom-login-plugin -type f -exec chmod 644 {} \;"
```

**C. Check user has sudo:**
```bash
ssh root@65.108.212.64 "sudo -l"
```

**D. SELinux issues (if applicable):**
```bash
ssh root@65.108.212.64 "sudo setenforce 0"  # Temporary
ssh root@65.108.212.64 "sudo chcon -R -t httpd_sys_content_t /var/www/html/"
```

---

## 🛠️ Advanced Debugging

### View Complete Workflow Logs
1. GitHub → Actions tab
2. Click workflow run
3. Click job name
4. Expand each step
5. Download logs if needed (top right ⋮ menu)

### Enable Debug Logging in GitHub Actions
1. Go to repository Settings → Secrets → Actions
2. Add new secret:
   - Name: `ACTIONS_STEP_DEBUG`
   - Value: `true`
3. Run workflow again
4. More detailed logs will appear

### SSH Into Server and Debug Manually
```bash
# Connect to server
ssh root@65.108.212.64

# Check Apache error log in real-time
tail -f /var/log/apache2/wordpress_error.log

# Check Apache access log
tail -f /var/log/apache2/wordpress_access.log

# Test PHP
php -v
php -m  # Show loaded modules

# Check disk space
df -h

# Check memory
free -h

# Test WordPress
cd /var/www/html
php -r "require 'wp-load.php'; echo 'WordPress loaded successfully!';"
```

### Manual Deployment as Fallback
```bash
# Method 1: Using deploy.sh script
./deploy.sh

# Method 2: Direct SCP
scp -r *.php root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
scp -r *.png root@65.108.212.64:/var/www/html/wp-content/plugins/my-custom-login-plugin/
ssh root@65.108.212.64 "sudo systemctl reload apache2"

# Method 3: Using tarball
tar -czf plugin.tar.gz *.php *.png *.sql README.md
scp plugin.tar.gz root@65.108.212.64:/tmp/
ssh root@65.108.212.64 "cd /var/www/html/wp-content/plugins/my-custom-login-plugin && sudo tar -xzf /tmp/plugin.tar.gz --strip-components=0"
```

---

## 📊 Verification Commands

### Verify Complete Setup
```bash
# Run automated verification
./verify-setup.sh

# Or manual checks:
# 1. SSH connection
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64 "echo OK"

# 2. Server software
ssh root@65.108.212.64 "apache2 -v && php -v && mysql --version"

# 3. WordPress
curl -I http://65.108.212.64 | grep -i server

# 4. Plugin files
ssh root@65.108.212.64 "ls /var/www/html/wp-content/plugins/my-custom-login-plugin/"

# 5. Permissions
ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"
```

---

## 🆘 Getting Help

### Information to Provide When Asking for Help

1. **Workflow logs:**
   - Go to Actions tab
   - Click failed run
   - Download logs (⋮ menu → Download log archive)

2. **Server information:**
   ```bash
   ssh root@65.108.212.64 "uname -a && apache2 -v && php -v"
   ```

3. **Error messages:**
   - Copy exact error from workflow logs
   - Copy Apache error log: `ssh root@65.108.212.64 "tail -50 /var/log/apache2/error.log"`

4. **Configuration:**
   ```bash
   # GitHub secrets (don't share actual values!)
   # Just confirm they are set
   
   # Server paths
   ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/"
   ```

### Contact & Resources
- **Repository Issues**: https://github.com/DzenanMuftic/urs/issues
- **Workflow Status**: https://github.com/DzenanMuftic/urs/actions
- **Setup Guide**: [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Deployment Docs**: [.github/DEPLOYMENT.md](.github/DEPLOYMENT.md)

---

## ✅ Success Checklist

After fixing issues, verify everything works:

- [ ] SSH connection successful: `ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64`
- [ ] GitHub Secrets configured correctly
- [ ] Workflow runs successfully (green checkmark)
- [ ] Files updated on server: `ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"`
- [ ] WordPress accessible: http://65.108.212.64
- [ ] Plugin visible in WordPress admin
- [ ] Plugin activated and working
- [ ] Push to main triggers automatic deployment

**All checked?** 🎉 Your automatic deployment is working!
