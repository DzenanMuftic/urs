# Deployment Setup Checklist

Use this checklist to track your progress in setting up automatic deployment from GitHub to your server.

## 📋 Pre-Deployment Setup

### 1. Server Access
- [ ] I can SSH to server: `ssh root@65.108.212.64`
- [ ] I have root or sudo access on the server
- [ ] Server is running Ubuntu/Debian Linux
- [ ] Server has internet connectivity

### 2. GitHub Access
- [ ] I have admin access to the repository
- [ ] I can push to the `main` branch
- [ ] Actions are enabled in repository settings

---

## 🔑 SSH Key Configuration

### 3. Generate SSH Keys
- [ ] SSH key pair generated: `ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key`
- [ ] No passphrase set (pressed Enter when prompted)
- [ ] Private key exists: `~/.ssh/github_actions_deploy_key`
- [ ] Public key exists: `~/.ssh/github_actions_deploy_key.pub`

### 4. Install Public Key on Server
- [ ] Public key copied to server: `ssh-copy-id -i ~/.ssh/github_actions_deploy_key.pub root@65.108.212.64`
- [ ] OR manually added to `~/.ssh/authorized_keys` on server
- [ ] SSH connection test successful: `ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64 "echo OK"`

---

## 🔒 GitHub Secrets Configuration

### 5. Add GitHub Secrets
Navigate to: Repository → Settings → Secrets and variables → Actions

- [ ] Added secret: `SERVER_HOST` = `65.108.212.64`
- [ ] Added secret: `SERVER_USER` = `root` (or your username)
- [ ] Added secret: `SERVER_PORT` = `22`
- [ ] Added secret: `SERVER_SSH_KEY` = (complete private key from `cat ~/.ssh/github_actions_deploy_key`)

**Verify secrets:**
- [ ] All 4 secrets are listed in Settings → Secrets → Actions
- [ ] No typos in secret names (case-sensitive)
- [ ] SERVER_SSH_KEY includes `-----BEGIN OPENSSH PRIVATE KEY-----` header
- [ ] SERVER_SSH_KEY includes `-----END OPENSSH PRIVATE KEY-----` footer

---

## 🖥️ Server Preparation

### 6. Install WordPress and Dependencies

**Option A: Using GitHub Actions (Recommended)**
- [ ] Went to Actions tab: https://github.com/DzenanMuftic/urs/actions
- [ ] Selected "Setup WordPress Server" workflow
- [ ] Clicked "Run workflow"
- [ ] Entered server IP: `65.108.212.64`
- [ ] Workflow completed successfully (green checkmark ✅)

**Option B: Manual Setup**
- [ ] SSH to server: `ssh root@65.108.212.64`
- [ ] Ran setup script: `bash <(curl -s https://raw.githubusercontent.com/DzenanMuftic/urs/main/deploy.sh)`
- [ ] Script completed without errors

### 7. Verify Server Setup
- [ ] Apache is running: `ssh root@65.108.212.64 "systemctl status apache2"`
- [ ] PHP is installed: `ssh root@65.108.212.64 "php -v"`
- [ ] MySQL is running: `ssh root@65.108.212.64 "systemctl status mysql"`
- [ ] WordPress directory exists: `ssh root@65.108.212.64 "ls /var/www/html/wp-config.php"`
- [ ] Plugin directory exists: `ssh root@65.108.212.64 "ls -d /var/www/html/wp-content/plugins/my-custom-login-plugin"`

---

## 🌐 WordPress Configuration

### 8. Complete WordPress Installation
- [ ] Visited http://65.108.212.64 in browser
- [ ] Selected language
- [ ] Entered site title
- [ ] Created admin username and password (saved securely)
- [ ] Entered admin email address
- [ ] Clicked "Install WordPress"
- [ ] Saw "Success!" message

### 9. Login to WordPress
- [ ] Logged into admin: http://65.108.212.64/wp-admin
- [ ] Used credentials from previous step
- [ ] Successfully accessed WordPress dashboard

---

## 🚀 Test Deployment

### 10. Verify Workflow Files
- [ ] File exists: `.github/workflows/deploy.yml`
- [ ] File exists: `.github/workflows/setup-server.yml`
- [ ] Files are in the `main` branch

### 11. Test Automatic Deployment

**Make a test change:**
- [ ] Edited a file (e.g., added comment to README.md)
- [ ] Committed: `git add . && git commit -m "Test deployment"`
- [ ] Pushed: `git push origin main`

**Monitor deployment:**
- [ ] Went to Actions tab: https://github.com/DzenanMuftic/urs/actions
- [ ] New workflow run appeared: "Deploy WordPress Plugin to Server"
- [ ] Clicked on workflow run to view details
- [ ] All steps completed successfully (green checkmarks ✅)
- [ ] No error messages in logs

### 12. Verify Deployment on Server
- [ ] SSH to server: `ssh root@65.108.212.64`
- [ ] Listed plugin files: `ls -la /var/www/html/wp-content/plugins/my-custom-login-plugin/`
- [ ] Files have recent timestamps (within last few minutes)
- [ ] File ownership is `www-data:www-data`
- [ ] File permissions are `755` for directories, `644` for files

---

## ✅ Activate Plugin

### 13. WordPress Plugin Activation
- [ ] Logged into WordPress admin: http://65.108.212.64/wp-admin
- [ ] Navigated to: Plugins → Installed Plugins
- [ ] Found "My Custom Login Plugin" in list
- [ ] Clicked "Activate" (if not already active)
- [ ] Plugin shows as "Active"
- [ ] No error messages displayed

### 14. Test Plugin Functionality
- [ ] Created a test page with shortcode `[custom_login_form]`
- [ ] Viewed page on frontend
- [ ] Login form displays correctly
- [ ] Plugin CSS is loading
- [ ] Plugin JavaScript is working

---

## 🔄 Ongoing Usage

### 15. Regular Deployment Workflow
- [ ] Understand: Push to `main` branch triggers automatic deployment
- [ ] Tested: Made code change, pushed, deployment happened automatically
- [ ] Can monitor deployments in GitHub Actions tab
- [ ] Know how to check deployment logs if needed

### 16. Troubleshooting Readiness
- [ ] Read: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- [ ] Bookmarked: GitHub Actions URL for monitoring
- [ ] Saved: SSH command to access server
- [ ] Know how to: View Apache error logs on server

---

## 🛠️ Verification Tools

### 17. Run Verification Script
- [ ] Ran: `./verify-setup.sh`
- [ ] All checks passed
- [ ] No error messages
- [ ] Script shows "✓ All checks passed!"

### 18. Manual Verification Commands

Run these commands to manually verify everything:

```bash
# SSH connection
ssh -i ~/.ssh/github_actions_deploy_key root@65.108.212.64 "echo 'SSH OK'"

# Server software
ssh root@65.108.212.64 "apache2 -v && php -v && mysql --version"

# WordPress
curl -I http://65.108.212.64 | grep HTTP

# Plugin files
ssh root@65.108.212.64 "ls /var/www/html/wp-content/plugins/my-custom-login-plugin/"

# Workflow files
ls .github/workflows/
```

- [ ] All commands run successfully
- [ ] No error messages

---

## 📚 Documentation Review

### 19. Read Key Documentation
- [ ] Read: [SETUP_GUIDE.md](SETUP_GUIDE.md) - Setup instructions
- [ ] Read: [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Quick commands
- [ ] Read: [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues
- [ ] Read: [DEPLOYMENT_FLOW.md](DEPLOYMENT_FLOW.md) - How deployment works
- [ ] Read: [.github/DEPLOYMENT.md](.github/DEPLOYMENT.md) - Workflow details

### 20. Bookmark Important URLs
- [ ] GitHub Repository: https://github.com/DzenanMuftic/urs
- [ ] GitHub Actions: https://github.com/DzenanMuftic/urs/actions
- [ ] GitHub Secrets: https://github.com/DzenanMuftic/urs/settings/secrets/actions
- [ ] WordPress Admin: http://65.108.212.64/wp-admin
- [ ] WordPress Site: http://65.108.212.64

---

## 🎉 Final Verification

### 21. Complete End-to-End Test

**Full deployment test:**
1. Make a meaningful code change
2. Commit and push to main
3. Deployment triggers automatically
4. Deployment completes successfully
5. Changes appear on server
6. WordPress shows updated plugin
7. Plugin works on frontend

- [ ] End-to-end test completed successfully
- [ ] Confident in deployment process
- [ ] Ready to use automatic deployment for development

---

## ✅ Completion

### Setup Status

**Count your checkboxes:**
- Total tasks: 70+
- Completed: _____ / 70+

**If all tasks are checked:**
🎊 **Congratulations!** Your automatic deployment is fully configured and working!

**If some tasks are unchecked:**
- Review the unchecked items
- Follow the documentation for guidance
- Use [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for help
- Run `./verify-setup.sh` for diagnostics

---

## 🚀 Next Steps

Now that deployment is set up:

1. **Develop** - Make your code changes
2. **Commit** - `git add . && git commit -m "Your changes"`
3. **Push** - `git push origin main`
4. **Relax** - Deployment happens automatically! ☕

**Monitor your deployments:**
- GitHub Actions: https://github.com/DzenanMuftic/urs/actions
- Server logs: `ssh root@65.108.212.64 "tail -f /var/log/apache2/wordpress_error.log"`

---

## 📞 Getting Help

If you encounter issues:
1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md) first
2. Run `./verify-setup.sh` for diagnostics
3. Review workflow logs in Actions tab
4. Check server logs via SSH

---

**Happy Deploying! 🚀**
