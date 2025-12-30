# HCare Backend - InfinityFree Deployment Checklist

Use this checklist to ensure a smooth deployment process.

## Pre-Deployment

- [ ] Review `DEPLOYMENT_GUIDE.md` thoroughly
- [ ] Generate strong random keys for JWT_SECRET and AES_KEY
- [ ] Prepare database schema SQL file (if available)
- [ ] Test application locally to ensure it works
- [ ] Document all environment variables needed

## InfinityFree Account Setup

- [ ] Created InfinityFree account at infinityfree.net
- [ ] Verified email address
- [ ] Created or selected a domain/subdomain
- [ ] Noted domain name: `___________________________`
- [ ] Created MySQL database
- [ ] Noted database credentials:
  - Host: `___________________________`
  - Name: `___________________________`
  - User: `___________________________`
  - Password: `___________________________`
- [ ] Obtained FTP credentials:
  - Host: `___________________________`
  - Username: `___________________________`
  - Password: `___________________________`
  - Port: `___________________________`

## File Preparation

- [ ] Created `.env` file from `.env.example`
- [ ] Updated `.env` with production values:
  - [ ] BASE_URL
  - [ ] DEBUG_MODE set to `false`
  - [ ] FRONTEND_URL
  - [ ] Database credentials
  - [ ] Security keys (JWT_SECRET, AES_KEY)
- [ ] Verified `public/index.php` has production error handling
- [ ] Prepared `.htaccess` file for root directory

## File Upload

- [ ] Connected to FTP server successfully
- [ ] Navigated to `htdocs` or `public_html` directory
- [ ] Uploaded all application files:
  - [ ] `app/` folder (entire directory)
  - [ ] `public/` folder contents (if deploying to root)
  - [ ] `logs/` folder (created if needed)
  - [ ] `.env` file
  - [ ] `.htaccess` file (in appropriate location)
- [ ] Verified all files uploaded correctly

## File Permissions

- [ ] Set folder permissions to 755
- [ ] Set file permissions to 644
- [ ] Set `logs/` folder permissions to 777 (writable)

## Database Setup

- [ ] Accessed phpMyAdmin via InfinityFree Control Panel
- [ ] Selected the correct database
- [ ] Imported database schema (if SQL file available)
- [ ] OR created tables manually
- [ ] Verified database connection from `.env` file

## PHP Configuration

- [ ] Checked PHP version in Control Panel (recommended: 7.4 or 8.0)
- [ ] Verified required PHP extensions are enabled:
  - [ ] mysqli/PDO
  - [ ] mbstring
  - [ ] openssl
  - [ ] json

## Security Configuration

- [ ] Verified `.env` file is protected by `.htaccess`
- [ ] Verified `logs/` directory is protected
- [ ] Enabled SSL/HTTPS certificate in Control Panel
- [ ] Updated `BASE_URL` in `.env` to use HTTPS

## Testing

- [ ] Tested API endpoint: `GET /csrf-token`
  - Response: `___________________________`
- [ ] Tested database connection (try registration endpoint)
  - Result: `___________________________`
- [ ] Tested authentication: `POST /login`
  - Result: `___________________________`
- [ ] Verified CORS headers are working
- [ ] Checked error logs for any issues
- [ ] Tested at least one protected endpoint with authentication

## Frontend Integration

- [ ] Updated frontend API base URL
- [ ] Tested frontend-backend communication
- [ ] Verified CORS allows frontend domain
- [ ] Tested authentication flow from frontend

## Post-Deployment

- [ ] Documented production URL: `___________________________`
- [ ] Documented database credentials (stored securely)
- [ ] Created backup of database
- [ ] Set up monitoring/alerting (if applicable)
- [ ] Documented any custom configurations made

## Troubleshooting Notes

Document any issues encountered and their solutions:

1. Issue: `___________________________`
   Solution: `___________________________`

2. Issue: `___________________________`
   Solution: `___________________________`

3. Issue: `___________________________`
   Solution: `___________________________`

## Maintenance Reminders

- [ ] Schedule regular database backups
- [ ] Monitor InfinityFree account activity (to prevent suspension)
- [ ] Check error logs weekly
- [ ] Update security keys periodically
- [ ] Monitor resource usage in Control Panel

---

## Quick Reference

**API Base URL:** `___________________________`
**Database Host:** `___________________________`
**FTP Host:** `___________________________`
**Control Panel URL:** `___________________________`

---

**Deployment Date:** `___________________________`
**Deployed By:** `___________________________`
**Status:** `___________________________` (✅ Success / ⚠️ Issues / ❌ Failed)

