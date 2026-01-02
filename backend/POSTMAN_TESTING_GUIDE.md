# 🚀 Postman Testing Guide: HCare API

Follow these steps exactly to set up your Postman for testing everything from Authentication to Pharmacy and Billing.

---

## 🏗️ Step 1: Create a Postman Environment
Do not skip this! It makes your tokens automatic.

1.  In Postman, click **Environments** (left sidebar) -> **+** (New Environment).
2.  Name it: `HCare-Local`.
3.  Add these rows:
    *   `base_url`: `http://localhost/Backend/Hcare_Backend/backend/public`
    *   `accessToken`: (leave blank)
    *   `csrfToken`: (leave blank)
4.  **Select this environment** from the dropdown in the top-right corner.

---

## 🛠️ Step 2: Configure the Collection (Global Settings)
Instead of adding headers to every single request, do it once here:

1.  Create a new **Collection** called `HCare API`.
2.  Go to the **Authorization** tab:
    *   Type: `Bearer Token`
    *   Token: `{{accessToken}}`
3.  Go to the **Headers** tab:
    *   Add Key: `X-Debug-Mode`, Value: `true` (This unlocks plain JSON responses).
    *   Add Key: `X-CSRF-TOKEN`, Value: `{{csrfToken}}`.
4.  **Save** the Collection.

---

## 🔑 Step 3: The "Magic" Login Sequence
These two requests "unlock" your entire API.

### Request A: Fetch CSRF (Public)
*   **Method:** `GET`
*   **URL:** `{{base_url}}/csrf-token`
*   **Scripts -> Post-response:** Paste this:
    ```javascript
    const jsonData = pm.response.json();
    pm.environment.set("csrfToken", jsonData.csrfToken);
    console.log("CSRF Token saved!");
    ```

### Request B: Admin Login
*   **Method:** `POST`
*   **URL:** `{{base_url}}/login`
*   **Body (raw JSON):**
    ```json
    {
        "email": "admin@gmail.com",
        "password": "password123",
        "role": "admin"
    }
    ```
*   **Scripts -> Post-response:** Paste this:
    ```javascript
    const jsonData = pm.response.json();
    const data = jsonData.debug; // Decoded by X-Debug-Mode
    if (data && data.accessToken) {
        pm.environment.set("accessToken", data.accessToken);
        pm.environment.set("csrfToken", data.csrfToken);
        console.log("Login Success: Access Token saved.");
    }
    ```

---

## 📂 Step 4: Endpoint Reference (Copy-Paste)

### 🏥 1. Patients
| Task | Method | URL | Body (JSON) |
| :--- | :--- | :--- | :--- |
| **List All** | `GET` | `{{base_url}}/patients` | None |
| **Register** | `POST` | `{{base_url}}/patients` | `{"name":"John Doe", "dob":"1995-10-20", "gender":"Male"}` |
| **Upate** | `PUT` | `{{base_url}}/patients/1`| `{"phone":"9876543210"}` |

### 📅 2. Appointments & Calendar
| Task | Method | URL | Body (JSON) |
| :--- | :--- | :--- | :--- |
| **Calendar** | `GET` | `{{base_url}}/appointments/calendar` | None |
| **Book New** | `POST` | `{{base_url}}/appointments` | `{"patient_id":1, "doctor_id":1, "appointment_date":"2026-05-01 09:00:00"}` |

### 💊 3. Pharmacy & Medicines
| Task | Method | URL | Body (JSON) |
| :--- | :--- | :--- | :--- |
| **Stock List**| `GET` | `{{base_url}}/medicines` | None |
| **Prescribe** | `POST` | `{{base_url}}/prescriptions` | `{"appointment_id":1, "medicines":[{"id":1, "dose":"1-0-1"}]}` |
| **Dispense** | `PATCH`| `{{base_url}}/prescriptions/1/status` | `{"status":"Dispensed"}` |

### 💰 4. Billing
| Task | Method | URL | Body (JSON) |
| :--- | :--- | :--- | :--- |
| **Summary** | `GET` | `{{base_url}}/billing/summary` | None |
| **Create Bill**| `POST` | `{{base_url}}/billing` | `{"patient_id":1, "amount":250.00, "description":"Checkup"}` |

---

## 🚨 Troubleshooting
*   **"Unauthorized (401)"**: Your `accessToken` environment variable is either empty or expired. Re-run the **Login** request.
*   **"Invalid CSRF (403)"**: Re-run the **GET /csrf-token** request.
*   **"Random Gibberish in Response"**: Make sure the `X-Debug-Mode: true` header is active in your request or collection.
*   **"404 Not Found"**: Double check your `base_url`. WAMP paths often require `/public` at the end of the folder name.

---
*Generated: 2026-01-02*
