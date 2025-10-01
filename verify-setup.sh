#!/bin/bash

# Deployment Setup Verification Script
# This script helps verify that GitHub Actions automatic deployment is properly configured

echo "=============================================="
echo "  URS Plugin Deployment Setup Verification"
echo "=============================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
checks_passed=0
checks_failed=0
checks_total=0

# Function to check and report
check() {
    local description=$1
    local command=$2
    local expected_result=${3:-0}
    
    ((checks_total++))
    echo -n "[$checks_total] Checking $description... "
    
    eval "$command" > /dev/null 2>&1
    result=$?
    
    if [ $result -eq $expected_result ]; then
        echo -e "${GREEN}✓ PASS${NC}"
        ((checks_passed++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC}"
        ((checks_failed++))
        return 1
    fi
}

# Function to check file exists
check_file() {
    local description=$1
    local filepath=$2
    
    ((checks_total++))
    echo -n "[$checks_total] Checking $description... "
    
    if [ -f "$filepath" ]; then
        echo -e "${GREEN}✓ PASS${NC}"
        ((checks_passed++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (not found: $filepath)"
        ((checks_failed++))
        return 1
    fi
}

echo "=== Local Environment Checks ==="
echo ""

# Check if SSH key exists
if [ -f ~/.ssh/github_actions_deploy_key ]; then
    check_file "SSH private key exists" ~/.ssh/github_actions_deploy_key
    check_file "SSH public key exists" ~/.ssh/github_actions_deploy_key.pub
else
    echo -e "${YELLOW}⚠ SSH keys not found at ~/.ssh/github_actions_deploy_key${NC}"
    echo "  Generate with: ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key"
    echo ""
fi

# Check workflow files exist
check_file "Deploy workflow file" ".github/workflows/deploy.yml"
check_file "Setup workflow file" ".github/workflows/setup-server.yml"
check_file "Deployment documentation" ".github/DEPLOYMENT.md"
check_file "Setup guide" "SETUP_GUIDE.md"

echo ""
echo "=== Server Connection Checks ==="
echo ""

# Server details
SERVER_HOST=${SERVER_HOST:-"65.108.212.64"}
SERVER_USER=${SERVER_USER:-"root"}
SERVER_PORT=${SERVER_PORT:-"22"}

echo "Server: $SERVER_USER@$SERVER_HOST:$SERVER_PORT"
echo ""

# Check if SSH key is installed
if [ -f ~/.ssh/github_actions_deploy_key ]; then
    check "SSH connection to server" "ssh -i ~/.ssh/github_actions_deploy_key -o ConnectTimeout=5 -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_HOST -p $SERVER_PORT 'exit 0'"
    
    if [ $? -eq 0 ]; then
        # SSH works, do more checks
        check "Apache is installed" "ssh -i ~/.ssh/github_actions_deploy_key $SERVER_USER@$SERVER_HOST -p $SERVER_PORT 'which apache2'"
        check "PHP is installed" "ssh -i ~/.ssh/github_actions_deploy_key $SERVER_USER@$SERVER_HOST -p $SERVER_PORT 'which php'"
        check "MySQL is installed" "ssh -i ~/.ssh/github_actions_deploy_key $SERVER_USER@$SERVER_HOST -p $SERVER_PORT 'which mysql'"
        check "WordPress directory exists" "ssh -i ~/.ssh/github_actions_deploy_key $SERVER_USER@$SERVER_HOST -p $SERVER_PORT 'test -d /var/www/html'"
        check "Plugin directory exists" "ssh -i ~/.ssh/github_actions_deploy_key $SERVER_USER@$SERVER_HOST -p $SERVER_PORT 'test -d /var/www/html/wp-content/plugins/my-custom-login-plugin'"
    fi
else
    echo -e "${YELLOW}⚠ Skipping server checks (SSH key not found)${NC}"
    echo ""
fi

echo ""
echo "=== GitHub Repository Checks ==="
echo ""

# Check if we're in a git repository
if git rev-parse --git-dir > /dev/null 2>&1; then
    check "Git repository initialized" "git rev-parse --git-dir"
    check "Remote 'origin' configured" "git remote get-url origin"
    
    # Get remote URL
    REMOTE_URL=$(git remote get-url origin 2>/dev/null)
    if [ -n "$REMOTE_URL" ]; then
        echo "  Remote URL: $REMOTE_URL"
    fi
    
    # Check if on main branch
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
    if [ "$CURRENT_BRANCH" = "main" ]; then
        echo -e "  ${GREEN}✓${NC} On main branch"
    else
        echo -e "  ${YELLOW}⚠${NC} Current branch: $CURRENT_BRANCH (deployment triggers on 'main')"
    fi
else
    echo -e "${RED}✗ Not a git repository${NC}"
    ((checks_failed++))
fi

echo ""
echo "=== GitHub Secrets Verification ==="
echo ""

echo -e "${YELLOW}Note: GitHub Secrets must be configured manually in repository settings${NC}"
echo "Go to: https://github.com/YOUR_USERNAME/urs/settings/secrets/actions"
echo ""
echo "Required secrets:"
echo "  • SERVER_HOST (value: $SERVER_HOST)"
echo "  • SERVER_USER (value: $SERVER_USER)"
echo "  • SERVER_PORT (value: $SERVER_PORT)"
echo "  • SERVER_SSH_KEY (value: contents of ~/.ssh/github_actions_deploy_key)"
echo ""

# Check if .git/config has GitHub URL
if grep -q "github.com" .git/config 2>/dev/null; then
    REPO_URL=$(git remote get-url origin | sed 's/\.git$//' | sed 's|git@github.com:|https://github.com/|')
    echo "GitHub Actions URL: ${REPO_URL}/actions"
    echo "GitHub Secrets URL: ${REPO_URL}/settings/secrets/actions"
fi

echo ""
echo "=== Summary ==="
echo ""
echo -e "Total checks: $checks_total"
echo -e "Passed: ${GREEN}$checks_passed${NC}"
echo -e "Failed: ${RED}$checks_failed${NC}"
echo ""

if [ $checks_failed -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Verify GitHub Secrets are configured (see URLs above)"
    echo "2. Push to main branch to trigger deployment:"
    echo "   git add . && git commit -m 'Deploy' && git push origin main"
    echo "3. Monitor deployment: https://github.com/YOUR_USERNAME/urs/actions"
    exit 0
else
    echo -e "${YELLOW}⚠ Some checks failed. Please review the issues above.${NC}"
    echo ""
    echo "Common fixes:"
    echo "• Generate SSH keys: ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_actions_deploy_key"
    echo "• Install public key: ssh-copy-id -i ~/.ssh/github_actions_deploy_key.pub $SERVER_USER@$SERVER_HOST"
    echo "• Configure GitHub Secrets in repository settings"
    echo "• Run server setup: ssh $SERVER_USER@$SERVER_HOST 'bash <(curl -s https://raw.githubusercontent.com/DzenanMuftic/urs/main/deploy.sh)'"
    echo ""
    echo "For detailed setup instructions, see: SETUP_GUIDE.md"
    exit 1
fi
