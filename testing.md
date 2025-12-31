# Full Project Postman Testing Guide

This guide provides a detailed, step-by-step process for testing all 11 modules of the Hcare API using Postman.

## 1. Postman Environment Setup

Create a new Environment in Postman and add the following variables:

| Variable | Initial Value | Description |
|---|---|---|
| `baseUrl` | `http://localhost/Healthcare/backup-final/backend/public/api` | API Base URL |
| `aesKey` | `s3cr3t_k3y_for_hc4r3_app_2025!@#` | AES Encryption Key from `.env` |
| `accessToken` | | (Auto-filled by script) |
| `csrfToken` | | (Auto-filled by script) |
| `refreshToken` | | (Auto-filled by script) |

---

## 2. Automation Scripts (Collection Level)

To automate encryption and token management, add these scripts to your Postman Collection.

### Pre-request Script (Encryption)
This script converts `raw_body` into an encrypted `payload`.

```javascript
const rawBody = pm.request.body.raw;
if (rawBody && !pm.request.headers.has('No-Encrypt')) {
    const cryptoJS = require('crypto-js');
    const key = CryptoJS.enc.Utf8.parse(pm.environment.get('aesKey'));
    const iv = CryptoJS.lib.WordArray.random(16);
    
    const encrypted = CryptoJS.AES.encrypt(rawBody, key, {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7
    });

    const combined = iv.clone().concat(encrypted.ciphertext);
    const payload = CryptoJS.enc.Base64.stringify(combined);
    
    pm.request.body.raw = JSON.stringify({ payload: payload });
}
```

### Test Script (Decryption & Token Capture)
This script decrypts the response and extracts tokens.

```javascript
const cryptoJS = require('crypto-js');
const key = CryptoJS.enc.Utf8.parse(pm.environment.get('aesKey'));

if (pm.response.code === 200 || pm.response.code === 201) {
    const responseData = pm.response.json();
    if (responseData.payload) {
        const decodedData = CryptoJS.enc.Base64.parse(responseData.payload);
        const iv = CryptoJS.lib.WordArray.create(decodedData.words.slice(0, 4));
        const ciphertext = CryptoJS.lib.WordArray.create(decodedData.words.slice(4));
        
        const decrypted = CryptoJS.AES.decrypt({ ciphertext: ciphertext }, key, {
            iv: iv,
            mode: CryptoJS.mode.CBC,
            padding: CryptoJS.pad.Pkcs7
        });

        const result = JSON.parse(decrypted.toString(CryptoJS.enc.Utf8));
        console.log("Decrypted Response:", result);

        // Auto-update Environment Variables
        if (result.accessToken) pm.environment.set("accessToken", result.accessToken);
        if (result.csrfToken) pm.environment.set("csrfToken", result.csrfToken);
        if (result.refreshToken) pm.environment.set("refreshToken", result.refreshToken);
        
        // Expose decrypted result for easy viewing in Postman UI
        pm.globals.set("lastResponse", JSON.stringify(result));
    }
}
```

---

## 3. Module-by-Module Testing

### 🟢 Module 1: Authentication & Tenant Management
1. **Get CSRF Token**
   - Method: `GET`
   - URL: `{{baseUrl}}/csrf-token`
   - Status: 200 OK. `csrfToken` is saved.

2. **Register Admin**
   - Method: `POST`
   - URL: `{{baseUrl}}/register`
   - Body: `{"name": "Admin User", "email": "admin@hcare.com", "password": "password123", "role": "Admin", "tenant_id": 1}`
   - Status: 201 Created.

3. **Login**
   - Method: `POST`
   - URL: `{{baseUrl}}/login`
   - Body: `{"email": "admin@hcare.com", "password": "password123"}`
   - Status: 200 OK. `accessToken` saved.

### 🔵 Module 2: User & Role Management
1. **Get All Users**
   - Method: `GET`
   - URL: `{{baseUrl}}/users`
   - Headers: `Authorization: Bearer {{accessToken}}`

2. **Create New User (Nurse)**
   - Method: `POST`
   - URL: `{{baseUrl}}/users`
   - Body: `{"name": "Jane Nurse", "email": "jane@hcare.com", "role": "Nurse", "password": "password123"}`

### 🔴 Module 3: Patient Management
1. **Create Patient**
   - Method: `POST`
   - URL: `{{baseUrl}}/patients`
   - Body: `{"name": "John Doe", "email": "john@doe.com", "medical_history": "N/A", "allergies": "Peanuts"}`
   - Note: Fields will be encrypted automatically by the backend.

2. **List Patients**
   - Method: `GET`
   - URL: `{{baseUrl}}/patients`

### 🟣 Module 4: Appointment & Scheduling
1. **Book Appointment**
   - Method: `POST`
   - URL: `{{baseUrl}}/appointments`
   - Body: `{"patientId": 1, "doctorId": 1, "appointmentDate": "2025-12-25", "appointmentTime": "10:00:00", "reason": "Monthly Checkup"}`

2. **Get Upcoming**
   - Method: `GET`
   - URL: `{{baseUrl}}/appointments/upcoming`

### 🟡 Module 5: Prescription & Pharmacy
1. **Create Prescription**
   - Method: `POST`
   - URL: `{{baseUrl}}/prescriptions`
   - Body: `{"patient_id": 1, "doctor_id": 1, "medicines": [{"name": "Aspirin", "dosage": "100mg"}], "instructions": "Daily after breakfast"}`

2. **Update Status (Pharmacist)**
   - Method: `PATCH`
   - URL: `{{baseUrl}}/prescriptions/1/status`
   - Body: `{"status": "Dispensed"}`

### 🟠 Module 6: Dashboard & Reports
1. **Tenant Dashboard**
   - Method: `GET`
   - URL: `{{baseUrl}}/dashboard`

2. **Global Analytics (Admin Only)**
   - Method: `GET`
   - URL: `{{baseUrl}}/dashboard/analytics`

### 🏥 Module 7: Communication (Notes)
1. **Add Appointment Note**
   - Method: `POST`
   - URL: `{{baseUrl}}/communication/notes`
   - Body: `{"appointment_id": 1, "content": "Patient is stable and recovering well."}`

### 💸 Module 8: Billing & Payment
1. **Generate Invoice**
   - Method: `POST`
   - URL: `{{baseUrl}}/billing`
   - Body: `{"patient_id": 1, "appointment_id": 1, "total_amount": 150.00, "description": "Consultation Fee"}`

2. **Update Payment Status**
   - Method: `PATCH`
   - URL: `{{baseUrl}}/billing/1/status`
   - Body: `{"status": "Paid", "paid_amount": 150.00}`

### 👩‍⚕️ Module 9: Staff Management
1. **List Doctors**
   - Method: `GET`
   - URL: `{{baseUrl}}/staff?role=doctors`

2. **Create Nurse**
   - Method: `POST`
   - URL: `{{baseUrl}}/staff`
   - Body: `{"name": "Alice Nurse", "role": "Nurse", "gender": "Female", "shift": "Day"}`

### 📅 Module 10: Calendar API
1. **Fetch Calendar Range**
   - Method: `GET`
   - URL: `{{baseUrl}}/appointments/calendar?start=2025-12-01&end=2025-12-31`

2. **Get Tooltip Details**
   - Method: `GET`
   - URL: `{{baseUrl}}/appointments/1/tooltip`

### 🛡️ Module 11: Settings & Security
1. **Change Password**
   - Method: `POST`
   - URL: `{{baseUrl}}/change-password`
   - Body: `{"oldPassword": "password123", "newPassword": "newPassword456"}`

2. **Regenerate CSRF**
   - Method: `POST`
   - URL: `{{baseUrl}}/csrf-regenerate`

3. **Logout**
   - Method: `POST`
   - URL: `{{baseUrl}}/logout`
   - Body: `{"refreshToken": "{{refreshToken}}"}`
