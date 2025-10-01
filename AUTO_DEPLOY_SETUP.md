# Auto-Deployment to Server 65.108.212.64

## Overview

This repository is configured for automatic deployment to server **65.108.212.64** whenever code is pushed to the `main` branch.

## How It Works

### Deployment Trigger
- **Automatic**: Deploys on every push to `main` branch
- **Manual**: Can be triggered via GitHub Actions UI (workflow_dispatch)
- **PR Preview**: Runs on pull requests to `main` (for validation)

### Default Configuration
The deployment workflow has the following defaults built-in:
- **Server IP**: `65.108.212.64`
- **Username**: `root`
- **Port**: `22`

### Required Setup

#### 1. SSH Key Secret (REQUIRED)
You must configure the SSH private key as a GitHub secret:

1. Go to repository **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add secret:
   - Name: `SERVER_SSH_KEY`
   - Value: Your SSH private key content

To generate an SSH key if you don't have one:
```bash
ssh-keygen -t rsa -b 4096 -f ~/.ssh/deploy_key
```

Then add the public key to the server:
```bash
ssh-copy-id -i ~/.ssh/deploy_key.pub root@65.108.212.64
```

#### 2. Optional Secrets
You can override the defaults by setting these secrets (optional):
- `SERVER_HOST`: Override default server IP
- `SERVER_USER`: Override default username
- `SERVER_PORT`: Override default SSH port

## Deployment Process

### What Happens on Push to Main:

1. **Package Creation**: Plugin files are packaged
2. **Backup**: Existing plugin is backed up with timestamp
3. **Deploy**: Files are copied to `/var/www/html/wp-content/plugins/my-custom-login-plugin/`
4. **Permissions**: Proper ownership and permissions are set
5. **Reload**: Apache is reloaded to apply changes
6. **Verify**: Deployment is verified and status is reported

### Files Deployed:
- All `.php` files
- All `.png` files (images/logos)
- All `.sql` files (database scripts)
- `README.md`

## Monitoring Deployments

### View Deployment Status:
1. Go to the **Actions** tab in GitHub
2. Look for "Deploy WordPress Plugin to Server" workflow
3. Click on the latest run to see details

### Deployment Success Indicators:
- ✅ Green checkmark = Successful deployment
- ❌ Red X = Deployment failed (check logs for details)

## Manual Deployment

If GitHub Actions deployment fails, you can deploy manually:

```bash
# Option 1: Using the deployment script
./deploy.sh

# Option 2: Manual steps
scp *.php *.png *.sql README.md root@65.108.212.64:/tmp/plugin-deploy/
ssh root@65.108.212.64
sudo cp -r /tmp/plugin-deploy/* /var/www/html/wp-content/plugins/my-custom-login-plugin/
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/my-custom-login-plugin
sudo systemctl reload apache2
```

## First-Time Server Setup

If WordPress is not yet installed on the server:

1. Go to **Actions** tab
2. Select **Setup WordPress Server** workflow
3. Click **Run workflow**
4. Enter server IP (default: 65.108.212.64)
5. Wait for completion
6. Complete WordPress setup at `http://65.108.212.64`
7. Push code to `main` branch to deploy plugin

## Troubleshooting

### Deployment Fails with "Permission Denied"
- Ensure SSH key is correctly configured in secrets
- Verify public key is added to server's `~/.ssh/authorized_keys`
- Check server allows root SSH login

### Plugin Not Updating
- Check if Apache reloaded successfully
- Manually reload: `ssh root@65.108.212.64 'sudo systemctl reload apache2'`
- Clear WordPress cache

### Workflow Not Triggering
- Ensure push is to `main` branch (not other branches)
- Check if workflow file is in `.github/workflows/` on `main` branch

## Security Notes

- SSH private key is stored securely in GitHub Secrets
- Automatic backups are created before each deployment
- Proper file permissions (755) are set automatically
- Only specified file types are deployed

## Support

For issues or questions about deployment:
1. Check workflow logs in Actions tab
2. Review this documentation
3. Contact repository maintainer
