# Quick Start - InfinityFree Deployment

## Essential Steps (TL;DR)

### 1. Get InfinityFree Credentials
- Sign up at [infinityfree.net](https://www.infinityfree.net)
- Create MySQL database → Note: Host, Name, User, Password
- Get FTP credentials → Note: Host, Username, Password

### 2. Create .env File
Create `.env` in project root:
```env
BASE_URL=https://your-domain.epizy.com
DEBUG_MODE=false
FRONTEND_URL=https://your-frontend-domain.com
DB_HOST=sqlXXX.epizy.com
DB_NAME=epiz_XXXXXX_hcare
DB_USER=epiz_XXXXXX
DB_PASS=your_password
JWT_SECRET=generate_32_char_random_string
AES_KEY=generate_exactly_32_char_string
JWT_EXPIRY=900
REFRESH_TOKEN_EXPIRY=604800
```

### 3. Upload Files via FTP
- Connect to `ftpupload.net` (or your FTP host)
- Upload to `htdocs` or `public_html`:
  - Copy `public/` folder contents to root
  - Copy `app/` folder
  - Copy `logs/` folder
  - Copy `.env` file
  - Copy `.htaccess` from root

### 4. Set Permissions
- Folders: 755
- Files: 644
- logs/: 777

### 5. Import Database
- Access phpMyAdmin in Control Panel
- Import your database schema

### 6. Test
Visit: `https://your-domain.epizy.com/csrf-token`

---

**For detailed instructions, see `DEPLOYMENT_GUIDE.md`**

