# Deployment Flow Diagram

## 📊 Complete Automatic Deployment Flow

### Overview

```
┌────────────────────────────────────────────────────────────────┐
│                    AUTOMATIC DEPLOYMENT FLOW                    │
│                  GitHub → Actions → Server                      │
└────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Step-by-Step Flow

### 1️⃣ Developer Makes Changes

```
Developer's Machine
├── Edit plugin files (*.php, *.png, etc.)
├── Test locally
├── git add .
├── git commit -m "Update plugin"
└── git push origin main
       │
       ▼
```

### 2️⃣ GitHub Receives Code

```
GitHub Repository
├── Detects push to 'main' branch
├── Triggers deploy.yml workflow
└── Starts GitHub Actions runner
       │
       ▼
```

### 3️⃣ GitHub Actions Workflow Executes

```
GitHub Actions Runner (Ubuntu)
│
├── Step 1: Checkout code
│   └── Clone repository from GitHub
│
├── Step 2: Setup PHP
│   └── Install PHP 8.0 environment
│
├── Step 3: Create deployment package
│   ├── Create directory: deployment/my-custom-login-plugin/
│   ├── Copy *.php files
│   ├── Copy *.png images
│   ├── Copy *.sql files
│   ├── Copy README.md
│   └── Create tar.gz archive
│
├── Step 4: Deploy to server via SSH
│   ├── Connect: ssh root@65.108.212.64
│   ├── Create backup of existing plugin
│   ├── Create plugin directory if needed
│   └── Set ownership: www-data:www-data
│
├── Step 5: Copy files to server
│   ├── Connect: scp to 65.108.212.64
│   ├── Upload files to /tmp/plugin-deploy/
│   └── Transfer complete
│
├── Step 6: Install plugin files
│   ├── Connect: ssh root@65.108.212.64
│   ├── Copy /tmp/plugin-deploy/* → /var/www/html/wp-content/plugins/
│   ├── Set permissions: chmod 755
│   ├── Set ownership: www-data:www-data
│   ├── Clean up /tmp/plugin-deploy
│   └── Reload Apache
│
└── Step 7: Verify deployment
    ├── List plugin files
    ├── Check permissions
    ├── Verify Apache status
    └── Display success message
       │
       ▼
```

### 4️⃣ Plugin Deployed on Server

```
Server: 65.108.212.64
│
├── /var/www/html/wp-content/plugins/my-custom-login-plugin/
│   ├── my-custom-login-plugin.php ✓
│   ├── admin-dashboard.php ✓
│   ├── login-form.php ✓
│   ├── user-dashboard.php ✓
│   ├── *.png (images) ✓
│   └── README.md ✓
│
├── Apache reloaded
└── Plugin ready to use!
```

### 5️⃣ WordPress Admin

```
WordPress Admin (http://65.108.212.64/wp-admin)
│
├── Navigate to: Plugins → Installed Plugins
├── Find: "My Custom Login Plugin"
├── Click: Activate
└── Plugin active! 🎉
```

---

## 🔐 Security Flow

### SSH Authentication

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────┐
│  GitHub Actions │         │   SSH Keys       │         │   Server    │
│                 │────────▶│  Private Key     │────────▶│             │
│  Workflow       │  Uses   │  (in Secrets)    │  Auth   │ Public Key  │
│                 │         │                  │         │ (authorized)│
└─────────────────┘         └──────────────────┘         └─────────────┘
```

**Key Points:**
- Private key stored securely in GitHub Secrets
- Public key installed in server's `~/.ssh/authorized_keys`
- No password needed - key-based authentication
- Encrypted connection via SSH (port 22)

---

## 📦 File Transfer Flow

```
Source (Repository)          Transfer                   Destination (Server)
┌──────────────┐            ┌──────┐                   ┌─────────────────────┐
│ *.php files  │───────────▶│ SCP  │──────────────────▶│ /var/www/html/      │
│ *.png files  │            │      │                   │ wp-content/plugins/ │
│ *.sql files  │            │ via  │                   │ my-custom-login-    │
│ README.md    │            │ SSH  │                   │ plugin/             │
└──────────────┘            └──────┘                   └─────────────────────┘
```

**Transfer Method:**
- Protocol: SCP (Secure Copy Protocol)
- Encryption: SSH tunnel
- Authentication: SSH keys
- Temp location: `/tmp/plugin-deploy/`
- Final location: `/var/www/html/wp-content/plugins/my-custom-login-plugin/`

---

## ⚙️ Workflow Triggers

### Automatic Triggers

```
Event                           Trigger Workflow?
─────────────────────────────────────────────────
git push origin main            ✅ YES
git push origin develop         ❌ NO
git push origin feature/*       ❌ NO
Pull Request to main            ✅ YES (test only)
Manual trigger                  ✅ YES
Scheduled (cron)                ❌ NO (not configured)
```

### Manual Trigger

```
GitHub Web Interface
├── Navigate to: Actions tab
├── Select: "Deploy WordPress Plugin to Server"
├── Click: "Run workflow" button
├── Choose branch: main (or other)
└── Click: "Run workflow" to start
```

---

## 🔍 Monitoring & Verification Flow

### During Deployment

```
GitHub Actions Tab
├── Workflow appears with status: 🟡 Running
├── Click workflow to see details
├── Each step shows: ⏳ In progress → ✅ Done / ❌ Failed
└── Total time: ~1-2 minutes
```

### After Deployment

```
Verification Steps
│
├── 1. GitHub Actions
│   ├── Check: Status is ✅ green
│   └── View: Deployment logs
│
├── 2. Server Files
│   ├── SSH to server
│   ├── List: ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/
│   └── Check: File timestamps are recent
│
├── 3. WordPress
│   ├── Visit: http://65.108.212.64/wp-admin
│   ├── Go to: Plugins
│   └── Verify: Plugin present and activated
│
└── 4. Website
    ├── Visit: http://65.108.212.64
    └── Test: Plugin functionality
```

---

## 🛠️ Rollback Flow

### If Deployment Fails

```
Automatic Backup System
│
├── Before each deployment:
│   └── Backup created: my-custom-login-plugin.backup.YYYYMMDD_HHMMSS
│
└── To rollback manually:
    ├── SSH to server
    ├── Find backup: ls /var/www/html/wp-content/plugins/my-custom-login-plugin.backup.*
    ├── Remove current: rm -rf /var/www/html/wp-content/plugins/my-custom-login-plugin
    ├── Restore backup: mv backup.YYYYMMDD_HHMMSS my-custom-login-plugin
    └── Reload Apache: systemctl reload apache2
```

---

## 📊 Complete Timeline

```
Time    Action                              Location
─────────────────────────────────────────────────────────────
00:00   Developer pushes code               Local machine
00:01   GitHub receives push                GitHub.com
00:02   Workflow triggered                  GitHub Actions
00:03   Environment setup                   GitHub Runner
00:05   Files packaged                      GitHub Runner
00:10   SSH connection established          → Server
00:15   Backup created                      Server
00:20   Files copied                        → Server
00:30   Permissions set                     Server
00:35   Apache reloaded                     Server
00:40   Verification complete               Server
00:45   Workflow success ✅                 GitHub Actions
─────────────────────────────────────────────────────────────
        Plugin is live!                     http://65.108.212.64
```

---

## 🎯 Quick Reference

### What Triggers Deployment?
- ✅ Push to `main` branch
- ✅ Pull request to `main` (test mode)
- ✅ Manual workflow dispatch

### Where Do Files Go?
- **Server**: `65.108.212.64`
- **Path**: `/var/www/html/wp-content/plugins/my-custom-login-plugin/`
- **URL**: http://65.108.212.64

### How Long Does It Take?
- **Typical**: 1-2 minutes
- **First time**: 2-3 minutes
- **With issues**: 5+ minutes (or fail)

### Who Can Deploy?
- Anyone with push access to `main` branch
- Anyone with repository admin access (manual trigger)

---

## 📚 Related Documentation

- **Setup Guide**: [SETUP_GUIDE.md](SETUP_GUIDE.md) - How to configure deployment
- **Quick Reference**: [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Command cheatsheet
- **Troubleshooting**: [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Fix common issues
- **Deployment Docs**: [.github/DEPLOYMENT.md](.github/DEPLOYMENT.md) - Workflow details

---

## 🚀 Start Deploying!

```bash
# Make changes
vim my-custom-login-plugin.php

# Commit and push
git add .
git commit -m "Update plugin feature"
git push origin main

# Watch deployment
open https://github.com/DzenanMuftic/urs/actions

# Verify on server
curl http://65.108.212.64
```

**That's it!** Your code is automatically deployed! 🎉
