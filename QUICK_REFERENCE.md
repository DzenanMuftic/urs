# Quick Reference: Automatic Deployment

## 📋 One-Time Setup

### 1. Generate SSH Keys
```bash
ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key
```

### 2. Install Public Key on Server
```bash
ssh-copy-id -i ~/.ssh/github_actions_deploy_key.pub root@65.108.212.64
```

### 3. Add GitHub Secrets
Go to: Repository → Settings → Secrets → Actions

Add these 4 secrets:
- `SERVER_HOST` = `65.108.212.64`
- `SERVER_USER` = `root`
- `SERVER_PORT` = `22`
- `SERVER_SSH_KEY` = (paste private key from `cat ~/.ssh/github_actions_deploy_key`)

### 4. Setup Server (First Time)
Go to: Actions → "Setup WordPress Server" → Run workflow

OR run manually:
```bash
ssh root@65.108.212.64
bash <(curl -s https://raw.githubusercontent.com/DzenanMuftic/urs/main/deploy.sh)
```

### 5. Complete WordPress Setup
Visit: http://65.108.212.64 and follow WordPress installation

---

## 🔄 Daily Workflow

### Deploy Changes
```bash
git add .
git commit -m "Your changes"
git push origin main
# Automatic deployment starts!
```

### Monitor Deployment
- View: https://github.com/DzenanMuftic/urs/actions
- ✅ = Success
- ❌ = Failed (check logs)

---

## 🔍 Quick Checks

### Test SSH Connection
```bash
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64
```

### Check Plugin Files on Server
```bash
ssh root@65.108.212.64 "ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/"
```

### View Apache Logs
```bash
ssh root@65.108.212.64 "tail -f /var/log/apache2/wordpress_error.log"
```

### Restart Apache
```bash
ssh root@65.108.212.64 "sudo systemctl restart apache2"
```

---

## 🛠️ Quick Fixes

### Deployment Failed?
1. Check Actions tab for error message
2. Verify GitHub Secrets are set correctly
3. Test SSH connection manually
4. Try manual deployment: `./deploy.sh`

### Plugin Not Updating?
1. Check deployment completed successfully
2. Clear WordPress cache
3. Hard refresh browser (Ctrl+F5)
4. Manually restart Apache

### Permission Errors?
```bash
ssh root@65.108.212.64 "sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/my-custom-login-plugin && sudo chmod -R 755 /var/www/html/wp-content/plugins/my-custom-login-plugin"
```

---

## 📚 Documentation

- **Complete Setup**: [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Deployment Details**: [.github/DEPLOYMENT.md](.github/DEPLOYMENT.md)
- **Plugin Features**: [README.md](README.md)

---

## 🎯 URLs

- **Website**: http://65.108.212.64/
- **WordPress Admin**: http://65.108.212.64/wp-admin
- **GitHub Actions**: https://github.com/DzenanMuftic/urs/actions
- **Repository**: https://github.com/DzenanMuftic/urs
