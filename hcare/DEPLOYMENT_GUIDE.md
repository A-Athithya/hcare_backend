# HCare Backend - InfinityFree Deployment Guide

This guide will walk you through deploying your HCare backend application to InfinityFree hosting.

## Prerequisites

1. **InfinityFree Account**: Sign up at [infinityfree.net](https://www.infinityfree.net)
2. **FTP Client**: FileZilla, WinSCP, or any FTP client
3. **Database Access**: You'll need to create a MySQL database in InfinityFree
4. **Domain/Subdomain**: Your InfinityFree account provides a free subdomain

---

## Step 1: Prepare Your Application

### 1.1 Create .env File

Create a `.env` file in the root directory with the following configuration:

```env
# Application Settings
BASE_URL=https://your-domain.epizy.com
DEBUG_MODE=false
FRONTEND_URL=https://your-frontend-domain.com

# Database Configuration (InfinityFree MySQL)
DB_HOST=sqlXXX.epizy.com
DB_NAME=epiz_XXXXXX_hcare
DB_USER=epiz_XXXXXX
DB_PASS=your_database_password

# Security Keys (Generate strong random keys)
JWT_SECRET=your_very_long_and_random_jwt_secret_key_here_min_32_chars
AES_KEY=your_32_character_aes_encryption_key
JWT_EXPIRY=900
REFRESH_TOKEN_EXPIRY=604800
```

**Important**: 
- Replace `your-domain.epizy.com` with your actual InfinityFree domain
- Replace database credentials with your InfinityFree MySQL credentials
- Generate strong random keys for JWT_SECRET and AES_KEY (minimum 32 characters)

### 1.2 Update Error Reporting for Production

The `public/index.php` file has error reporting enabled. For production, you should disable it or set it based on DEBUG_MODE.

---

## Step 2: Set Up InfinityFree Account

### 2.1 Create Account and Domain

1. Go to [infinityfree.net](https://www.infinityfree.net) and sign up
2. Log in to your account
3. Go to "Add Website" or "Control Panel"
4. Create a new website or use the default subdomain provided
5. Note your domain name (e.g., `yoursite.epizy.com`)

### 2.2 Create MySQL Database

1. In InfinityFree Control Panel, go to "MySQL Databases"
2. Click "Create New Database"
3. Note down:
   - **Database Name**: `epiz_XXXXXX_hcare` (or similar)
   - **Database User**: `epiz_XXXXXX` (or similar)
   - **Database Password**: (set a strong password)
   - **Database Host**: `sqlXXX.epizy.com` (usually shown in database details)

### 2.3 Get FTP Credentials

1. In Control Panel, go to "FTP Accounts"
2. Note down:
   - **FTP Host**: `ftpupload.net` (or similar)
   - **FTP Username**: Your FTP username
   - **FTP Password**: Your FTP password
   - **Port**: Usually 21

---

## Step 3: Upload Files to InfinityFree

### 3.1 Connect via FTP

1. Open your FTP client (FileZilla, WinSCP, etc.)
2. Connect using the credentials from Step 2.3:
   - Host: `ftpupload.net` (or your FTP host)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21

### 3.2 Upload Application Files

**Important**: InfinityFree's web root is typically `htdocs` or `public_html`

1. Navigate to the `htdocs` or `public_html` folder on the server
2. Upload ALL files from your project to this directory:
   - Upload the entire `app` folder
   - Upload the `public` folder
   - Upload the `logs` folder (create it if it doesn't exist)
   - Upload the `.env` file (create it with your production values)
   - Upload `.htaccess` file (if needed in root)

### 3.3 Configure .htaccess for Root Directory

Since InfinityFree serves from `htdocs` or `public_html`, you have two options:

**Option A: Upload public folder contents to root (Recommended)**
- Copy all files from `public` folder to `htdocs/public_html`
- Copy `.htaccess` from `public` to root
- Update paths in `index.php` if needed

**Option B: Use subdirectory**
- Upload entire project structure
- Access via `yoursite.epizy.com/hcare/public/`

**Recommended Structure for Option A:**
```
htdocs/
├── .htaccess (from public folder)
├── index.php (from public folder)
├── app/
├── logs/
└── .env
```

### 3.4 Create .htaccess in Root (if using Option A)

Create a `.htaccess` file in the root (`htdocs`) with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Redirect all requests to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>

# Security: Prevent access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Set PHP version (if needed)
# AddHandler application/x-httpd-php74 .php
```

---

## Step 4: Configure Database

### 4.1 Import Database Schema

1. In InfinityFree Control Panel, go to "phpMyAdmin"
2. Select your database
3. Go to "Import" tab
4. If you have a SQL file, import it
5. If not, you'll need to create tables manually (check your application's database schema)

### 4.2 Verify Database Connection

Update your `.env` file with the correct database credentials from Step 2.2.

---

## Step 5: Configure PHP Settings

### 5.1 Check PHP Version

InfinityFree typically supports PHP 7.4 or 8.0. Check in Control Panel:
1. Go to "Select PHP Version"
2. Choose PHP 7.4 or 8.0 (recommended: 8.0 if available)

### 5.2 Required PHP Extensions

Ensure these extensions are enabled (usually enabled by default):
- `mysqli` or `PDO`
- `mbstring`
- `openssl` (for JWT)
- `json`

### 5.3 Update index.php for Production

Modify `public/index.php` to respect DEBUG_MODE:

```php
// At the top of index.php
$debugMode = getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1';

if ($debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/php_errors.log');
}
```

---

## Step 6: Set File Permissions

Set appropriate permissions via FTP client:
- **Folders**: 755
- **Files**: 644
- **logs folder**: 777 (writable for logging)

---

## Step 7: Test Your Deployment

### 7.1 Test API Endpoint

1. Visit: `https://your-domain.epizy.com/csrf-token`
2. You should receive a JSON response with a CSRF token

### 7.2 Test Database Connection

1. Try to register a user: `POST https://your-domain.epizy.com/register`
2. Check logs folder for any errors

### 7.3 Common Issues and Solutions

**Issue: 500 Internal Server Error**
- Check `.htaccess` syntax
- Verify file permissions
- Check error logs in InfinityFree Control Panel
- Verify `.env` file exists and has correct values

**Issue: Database Connection Failed**
- Verify database credentials in `.env`
- Check if database host allows connections from your server
- Ensure database user has proper permissions

**Issue: Route Not Found (404)**
- Verify `.htaccess` is in correct location
- Check `RewriteBase` in `.htaccess` matches your directory structure
- Verify `index.php` path handling

**Issue: CORS Errors**
- Update `FRONTEND_URL` in `.env` to match your frontend domain
- Check CORS headers in `index.php`

---

## Step 8: Security Considerations

### 8.1 Protect Sensitive Files

Ensure `.env` file is not accessible:
- Add to `.htaccess`:
```apache
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

### 8.2 Secure Logs Directory

Prevent direct access to logs:
```apache
<Directory "logs">
    Order allow,deny
    Deny from all
</Directory>
```

### 8.3 Use HTTPS

InfinityFree provides free SSL certificates. Enable it in Control Panel:
1. Go to "SSL Certificates"
2. Enable "Let's Encrypt" or "Auto SSL"

---

## Step 9: Update Frontend Configuration

Update your frontend application to point to the new API URL:
- API Base URL: `https://your-domain.epizy.com`
- Update CORS settings if needed

---

## Step 10: Monitor and Maintain

### 10.1 Check Logs Regularly

- Check `logs/` folder for application logs
- Check InfinityFree error logs in Control Panel

### 10.2 Backup Database

- Regularly backup your database via phpMyAdmin
- Export SQL files periodically

### 10.3 Performance Optimization

- Enable caching where possible
- Optimize database queries
- Monitor resource usage in InfinityFree Control Panel

---

## Additional Notes

### InfinityFree Limitations

- **Inactivity**: Free accounts may be suspended after 30 days of inactivity
- **Resource Limits**: CPU and memory limits apply
- **Database Size**: Limited database storage
- **No Cron Jobs**: Free accounts don't support cron jobs

### Alternative: Using Subdirectory

If you prefer to keep the project structure intact:

1. Upload entire project to `htdocs/hcare/`
2. Access API at: `https://your-domain.epizy.com/hcare/public/`
3. Update `BASE_URL` in `.env` accordingly
4. Adjust `.htaccess` `RewriteBase` if needed

---

## Support Resources

- **InfinityFree Support**: [infinityfree.net/support](https://www.infinityfree.net/support)
- **InfinityFree Documentation**: [infinityfree.net/docs](https://www.infinityfree.net/docs)
- **phpMyAdmin**: Access via InfinityFree Control Panel

---

## Quick Checklist

- [ ] Created InfinityFree account
- [ ] Created MySQL database
- [ ] Created `.env` file with production values
- [ ] Uploaded all files via FTP
- [ ] Configured `.htaccess` correctly
- [ ] Set file permissions
- [ ] Imported database schema
- [ ] Tested API endpoints
- [ ] Enabled SSL/HTTPS
- [ ] Updated frontend API URL
- [ ] Secured sensitive files
- [ ] Tested all major features

---

**Good luck with your deployment!** 🚀

