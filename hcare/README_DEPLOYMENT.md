# HCare Backend - Deployment Documentation

This directory contains all the documentation and configuration files needed to deploy your HCare backend application to InfinityFree hosting.

## 📚 Documentation Files

1. **DEPLOYMENT_GUIDE.md** - Complete step-by-step deployment guide with detailed instructions
2. **QUICK_START.md** - Quick reference for essential deployment steps
3. **DEPLOYMENT_CHECKLIST.md** - Printable checklist to track your deployment progress
4. **README_DEPLOYMENT.md** - This file (overview and navigation)

## 📁 Configuration Files

1. **.htaccess** - Apache configuration for URL rewriting and security (place in web root)
2. **.env.example** - Template for environment variables (copy to `.env` and fill in your values)

## 🚀 Quick Navigation

### For First-Time Deployment
1. Start with **DEPLOYMENT_GUIDE.md** for comprehensive instructions
2. Use **DEPLOYMENT_CHECKLIST.md** to track your progress
3. Refer to **QUICK_START.md** for quick reminders

### For Quick Reference
- See **QUICK_START.md** for essential steps only

## ⚙️ What Has Been Prepared

✅ **Production-Ready Error Handling**
- `public/index.php` now respects `DEBUG_MODE` from `.env`
- Errors are logged to file in production mode
- Display errors disabled in production

✅ **Security Configuration**
- `.htaccess` file protects sensitive files (`.env`, `logs/`)
- Prevents directory listing
- Enables URL rewriting for clean API routes

✅ **Environment Template**
- `.env.example` provides template for all required variables

## 📋 Pre-Deployment Checklist

Before starting deployment, ensure you have:

- [ ] InfinityFree account created
- [ ] MySQL database created in InfinityFree
- [ ] FTP credentials ready
- [ ] Domain/subdomain assigned
- [ ] Strong random keys generated for JWT_SECRET and AES_KEY
- [ ] Database schema ready (SQL file or migration scripts)

## 🔑 Key Configuration Values Needed

When creating your `.env` file, you'll need:

1. **BASE_URL** - Your InfinityFree domain (e.g., `https://yoursite.epizy.com`)
2. **Database Credentials** - From InfinityFree MySQL setup
3. **Security Keys** - Generate strong random strings:
   - `JWT_SECRET` - Minimum 32 characters
   - `AES_KEY` - Exactly 32 characters
4. **FRONTEND_URL** - Your frontend application URL

## 🛠️ Generating Security Keys

### On Linux/Mac:
```bash
# For JWT_SECRET (32+ characters)
openssl rand -base64 32

# For AES_KEY (exactly 32 characters)
openssl rand -hex 16
```

### On Windows (PowerShell):
```powershell
# For JWT_SECRET
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | % {[char]$_})

# For AES_KEY (32 hex characters)
-join ((48..57) + (97..102) | Get-Random -Count 32 | % {[char]$_})
```

### Online Tools:
- Use a password generator to create random strings
- Ensure JWT_SECRET is at least 32 characters
- Ensure AES_KEY is exactly 32 characters

## 📞 Support

### InfinityFree Resources
- **Website**: [infinityfree.net](https://www.infinityfree.net)
- **Support**: [infinityfree.net/support](https://www.infinityfree.net/support)
- **Documentation**: [infinityfree.net/docs](https://www.infinityfree.net/docs)

### Common Issues
See the "Common Issues and Solutions" section in **DEPLOYMENT_GUIDE.md**

## 📝 Notes

- InfinityFree free accounts may suspend after 30 days of inactivity
- Free accounts have resource limits (CPU, memory, database size)
- SSL certificates are available for free via Let's Encrypt
- Cron jobs are not available on free accounts

## 🎯 Next Steps

1. Read **DEPLOYMENT_GUIDE.md** thoroughly
2. Create your `.env` file from `.env.example`
3. Follow the step-by-step instructions
4. Use **DEPLOYMENT_CHECKLIST.md** to track progress
5. Test your deployment thoroughly

---

**Good luck with your deployment!** 🚀

If you encounter any issues, refer to the troubleshooting section in the deployment guide.

