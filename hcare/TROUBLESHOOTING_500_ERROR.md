# Troubleshooting HTTP 500 Error

You're experiencing an HTTP 500 Internal Server Error. Follow these steps to diagnose and fix the issue.

## Step 1: Run Diagnostic Test

1. Upload the `test.php` file to your `public` folder (if not already there)
2. Visit: `https://cap-minds.gt.tc/hcare/public/test.php`
3. Review all the test results and fix any ❌ errors

## Step 2: Common Causes and Solutions

### Issue 1: Missing or Incorrect .env File

**Symptoms:**
- Error message about .env file not found
- Database connection errors

**Solution:**
1. Ensure `.env` file exists in `/htdocs/hcare/` (root of your project, not in public folder)
2. Verify the file has all required variables:
   ```env
   BASE_URL=https://cap-minds.gt.tc/hcare/public
   DEBUG_MODE=true
   FRONTEND_URL=https://your-frontend-url.com
   DB_HOST=sqlXXX.epizy.com
   DB_NAME=epiz_XXXXXX_hcare
   DB_USER=epiz_XXXXXX
   DB_PASS=your_password
   JWT_SECRET=your_jwt_secret_32_chars_min
   AES_KEY=your_aes_key_exactly_32_chars
   JWT_EXPIRY=900
   REFRESH_TOKEN_EXPIRY=604800
   ```
3. Check file permissions (should be 644)

### Issue 2: Database Connection Failed

**Symptoms:**
- Database connection errors in test.php
- "Connection refused" or "Access denied" errors

**Solution:**
1. Verify database credentials in InfinityFree Control Panel
2. Check that database host, name, user, and password are correct
3. Ensure database exists and user has proper permissions
4. Test connection in phpMyAdmin

### Issue 3: Missing PHP Extensions

**Symptoms:**
- "Class not found" errors
- "Call to undefined function" errors

**Solution:**
1. Check PHP version in InfinityFree Control Panel (should be 7.4+)
2. Verify required extensions are enabled:
   - mysqli
   - pdo
   - pdo_mysql
   - mbstring
   - openssl
   - json

### Issue 4: File Permissions

**Symptoms:**
- "Permission denied" errors
- Cannot write to logs folder

**Solution:**
Set correct permissions via FTP or File Manager:
- Folders: **755**
- Files: **644**
- `logs/` folder: **777** (writable)

### Issue 5: Incorrect File Paths

**Symptoms:**
- "File not found" errors
- "require_once failed" errors

**Solution:**
1. Verify file structure matches expected layout
2. Ensure `BASE_PATH` is correctly set (should point to `/htdocs/hcare/`)
3. Check that all required files exist

### Issue 6: PHP Syntax Errors

**Symptoms:**
- Parse errors
- Fatal errors

**Solution:**
1. Enable error display (already done in updated index.php)
2. Check error logs in `/htdocs/hcare/logs/php_errors.log`
3. Review InfinityFree error logs in Control Panel

### Issue 7: .htaccess Configuration Issues

**Symptoms:**
- 500 errors on all requests
- Rewrite rules not working

**Solution:**
1. Verify `.htaccess` is in `public` folder
2. Check that mod_rewrite is enabled (usually is on InfinityFree)
3. Try temporarily renaming `.htaccess` to `.htaccess.bak` to test
4. If that works, the issue is with .htaccess - check syntax

## Step 3: Enable Error Display Temporarily

The `index.php` has been updated to show errors. If you still don't see errors:

1. Check your `.env` file and set:
   ```env
   DEBUG_MODE=true
   ```

2. Or modify `public/index.php` line 23:
   ```php
   $showErrors = true; // Already set
   ```

## Step 4: Check Error Logs

1. **Application Logs:**
   - Check `/htdocs/hcare/logs/php_errors.log`
   - Check `/htdocs/hcare/logs/debug_auth.log`

2. **InfinityFree Error Logs:**
   - Go to InfinityFree Control Panel
   - Navigate to "Error Logs" or "Logs"
   - Review recent errors

## Step 5: Test Step by Step

1. **Test basic PHP:**
   - Create `public/info.php` with: `<?php phpinfo(); ?>`
   - Visit: `https://cap-minds.gt.tc/hcare/public/info.php`
   - If this works, PHP is functioning

2. **Test .env loading:**
   - Run `test.php` (created above)
   - Check if .env file is found and loaded

3. **Test database:**
   - Use test.php to verify database connection
   - Test connection in phpMyAdmin

4. **Test routing:**
   - After fixing other issues, test: `https://cap-minds.gt.tc/hcare/public/csrf-token`

## Step 6: Quick Fixes to Try

### Fix 1: Verify .env File Location
```
/htdocs/hcare/.env  ✅ Correct
/htdocs/hcare/public/.env  ❌ Wrong
```

### Fix 2: Check BASE_URL in .env
```env
BASE_URL=https://cap-minds.gt.tc/hcare/public
```

### Fix 3: Temporarily Disable Error Suppression
In `public/index.php`, ensure:
```php
$showErrors = true; // For debugging
```

### Fix 4: Check File Structure
```
/htdocs/hcare/
├── .env
├── .htaccess
├── app/
│   ├── Config/
│   ├── Controllers/
│   └── ...
├── logs/
└── public/
    ├── .htaccess
    ├── index.php
    └── test.php
```

## Step 7: After Fixing

1. **Disable error display:**
   - Set `DEBUG_MODE=false` in `.env`
   - Set `$showErrors = false;` in `index.php`

2. **Delete test files:**
   - Remove `public/test.php`
   - Remove `public/info.php` (if created)

3. **Test your API:**
   - `GET https://cap-minds.gt.tc/hcare/public/csrf-token`
   - Should return JSON with CSRF token

## Still Having Issues?

1. Check InfinityFree Control Panel for server-side errors
2. Verify your account is active (free accounts may suspend after inactivity)
3. Check if there are any resource limits being hit
4. Review the full error message from test.php
5. Check InfinityFree status page for service issues

## Security Reminder

⚠️ **IMPORTANT:** After fixing the issue, remember to:
- Set `DEBUG_MODE=false` in production
- Set `$showErrors = false;` in index.php
- Delete `test.php` and any diagnostic files
- Protect `.env` file with proper .htaccess rules

