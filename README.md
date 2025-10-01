# My Custom Login Plugin - Laravel Integration

A WordPress plugin that provides custom login functionality with Laravel database authentication and user dashboard.

## 🚀 Auto-Deployment

This repository is configured for **automatic deployment** to server `65.108.212.64` on every push to the `main` branch.

📖 **[View Auto-Deployment Setup Guide](AUTO_DEPLOY_SETUP.md)**

### Quick Start for Auto-Deployment:
1. Configure `SERVER_SSH_KEY` secret in GitHub repository settings
2. Push code to `main` branch
3. Plugin automatically deploys to server

## Features

- **Laravel Database Integration**: Connects to external Laravel database
- **Bcrypt Password Support**: Compatible with Laravel's password hashing
- **Custom Login Form**: Secure login using external database credentials
- **User Dashboard**: Display user information from Laravel database
- **Session Management**: Secure PHP session handling with regeneration
- **AJAX Integration**: Smooth user experience without page reloads
- **Rate Limiting**: Protection against brute force attacks
- **Debug Mode**: Built-in connection testing and debugging
- **Responsive Design**: Works on desktop and mobile devices
- **Security**: Input sanitization, nonce verification, and logging

## Installation

1. Upload the `my-custom-login-plugin` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure database settings in the main plugin file if needed
4. Test connection using `[custom_db_test]` shortcode (debug mode)

## Usage

### Login Form Shortcode
```
[custom_login_form]
```

Optional parameters:
```
[custom_login_form redirect_url="https://example.com/dashboard"]
```

### User Dashboard Shortcode
```
[custom_user_dashboard]
```

### Debug Connection Test (Debug Mode Only)
```
[custom_db_test]
```

## Laravel Database Configuration

### Current Settings
- **Database**: `ursbihba_lara195`
- **Host**: `localhost:3306` 
- **Table**: `users_data`
- **Password Hashing**: Laravel bcrypt (compatible)

### Database Schema

The plugin expects a `users_data` table with the following structure:

```sql
CREATE TABLE users_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    USER_ID VARCHAR(255),
    ADRESA VARCHAR(255),
    GRAD VARCHAR(255),
    EMAIL VARCHAR(255),
    MOBITEL VARCHAR(255),
    BANKA VARCHAR(255),
    RACUN VARCHAR(255),
    POZIV_NA_BR VARCHAR(255),
    ZBOR VARCHAR(255),
    KANTONALNI_SAVEZ VARCHAR(255),
    ULOGA_ID VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Ime VARCHAR(255),
    Prezime VARCHAR(255),
    Username VARCHAR(255) UNIQUE,
    Passw VARCHAR(255), -- Laravel bcrypt hashed password
    IZNOS_CLANARINE DECIMAL(10,2),
    SEZONA VARCHAR(255)
);
```

## Laravel Password Compatibility

### Password Verification
The plugin automatically detects and verifies Laravel bcrypt passwords:

```php
// Laravel creates passwords like this:
$password = bcrypt('user_password'); // Results in $2y$10$...

// Plugin verifies using:
password_verify($input_password, $stored_hash);
```

### Password Migration
If you need to create/update passwords for testing:

```php
// In Laravel:
$user = User::find(1);
$user->Passw = bcrypt('new_password');
$user->save();

// Or direct MySQL:
UPDATE users_data SET Passw = '$2y$10$...' WHERE Username = 'testuser';
```

## Security Features

- **Laravel Bcrypt Support**: Full compatibility with Laravel's password hashing
- **Session Regeneration**: Prevents session fixation attacks
- **Rate Limiting**: 5 failed attempts lock for 15 minutes
- **Input Sanitization**: All inputs properly sanitized
- **CSRF Protection**: WordPress nonce verification
- **Prepared Statements**: SQL injection prevention
- **Login Logging**: Failed attempts logged for monitoring
- **Secure Database Connection**: Proper error handling and connection management

## Debug Mode

Set `$debug_mode = true` in the plugin file to enable:

- Connection testing with `[custom_db_test]` shortcode
- Detailed error logging
- Database structure inspection
- User count verification

**Important**: Set `$debug_mode = false` in production!

## Configuration

Update the database configuration in `my-custom-login-plugin.php`:

```php
private $db_config = [
    'host' => 'localhost',           // Your database host
    'port' => '3306',               // Database port
    'database' => 'ursbihba_lara195', // Laravel database name
    'username' => 'your-db-user',    // Database username
    'password' => 'your-db-pass',    // Database password
    'charset' => 'utf8'             // Database charset
];
```

## Customization

- **Styling**: Modify `assets/style.css` for custom appearance
- **Templates**: Edit files in `templates/` folder for custom layouts
- **Functionality**: Extend the main plugin class for additional features

## Troubleshooting

1. **Login not working**: Check database connection and credentials
2. **Styles not loading**: Ensure WordPress can access the assets folder
3. **Session issues**: Verify PHP sessions are enabled on your server

## Support

For support and customization, contact the plugin developer.

## Version History

- **1.0.0**: Initial release with basic login and dashboard functionality
