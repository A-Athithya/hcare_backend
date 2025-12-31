# CSRF Protection - Implementation Details

> Understanding the CSRF protection strategy in this Healthcare Management System

---

## ❓ Your Question: Do We Use Hidden CSRF Fields in Forms?

**Answer: NO** ❌

This project uses a **modern, header-based CSRF protection** approach instead of traditional hidden form fields. This is the recommended approach for Single Page Applications (SPAs) like React.

---

## 🎯 Traditional vs Modern Approach

### ❌ Traditional Approach (NOT Used)

```html
<!-- Traditional PHP/HTML Form -->
<form method="POST" action="/submit">
    <input type="hidden" name="csrf_token" value="abc123xyz789...">
    <input type="text" name="username">
    <input type="password" name="password">
    <button type="submit">Login</button>
</form>
```

**Problems with this approach:**
1. ❌ Tightly coupled to HTML forms
2. ❌ Doesn't work well with AJAX/Fetch/Axios
3. ❌ Must manually add to every form
4. ❌ Not ideal for REST APIs
5. ❌ Harder to maintain in SPAs

---

### ✅ Modern Approach (What We Use)

```javascript
// Component - NO hidden field!
<Box component="form" onSubmit={handleLogin}>
  <TextField label="Email" />
  <TextField label="Password" type="password" />
  {/* ← No hidden input here! */}
  <Button type="submit">Sign In</Button>
</Box>
```

**Token sent via HTTP header automatically:**
```
POST /login HTTP/1.1
Host: api.example.com
Content-Type: application/json
X-CSRF-Token: abc123xyz789...    ← Automatic!
Authorization: Bearer eyJhbGc...

{ "email": "user@example.com", "password": "..." }
```

**Benefits:**
1. ✅ Decoupled from UI/forms
2. ✅ Works perfectly with AJAX/REST APIs
3. ✅ Automatic via request interceptor
4. ✅ Single source of truth (Redux state)
5. ✅ Easy to maintain

---

## 🔄 Complete CSRF Flow

### Step 1: Component Mounts (Fetch CSRF Token)

```javascript
// LoginPage.js (Lines 36-61)
useEffect(() => {
  api.get("/csrf-token")
    .then(res => {
      const token = res.data.csrfToken || res.data.csrf_token;
      if (token) {
        dispatch(setCsrfToken(token));  // ← Store in Redux
      }
    });
}, []);
```

**Backend Handler:**
```php
// AuthController.php::csrf()
public function csrf() {
    Response::json([
        'csrfToken' => CsrfMiddleware::generate()
    ]);
}
```

**Token Generation:**
```php
// CsrfMiddleware.php
public static function generate() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
```

---

### Step 2: Store in Redux

```javascript
// authSlice.js (Lines 77-79)
setCsrfToken(state, action) {
  state.csrfToken = action.payload;
}
```

**State structure:**
```javascript
state.auth = {
  user: { id, name, email, role },
  accessToken: "eyJhbGc...",
  csrfToken: "abc123xyz789...",  // ← Stored here
  loading: false,
  error: null
}
```

---

### Step 3: Automatic Header Injection

```javascript
// client.js (Lines 69-93) - Request Interceptor
api.interceptors.request.use((config) => {
  const state = store.getState();
  const token = state.auth.accessToken;
  const csrf = state.auth.csrfToken;

  // Add Authorization header
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Add CSRF header (AUTOMATIC!)
  if (csrf) {
    config.headers['X-CSRF-Token'] = csrf;  // ← Magic happens here
  }

  // Encrypt body...
  return config;
});
```

**Result:** Every POST/PUT/PATCH/DELETE request automatically includes CSRF token!

---

### Step 4: Backend Validation

```php
// CsrfMiddleware.php (Lines 44-75)
public static function validate() {
    // Skip for GET requests (safe methods)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return true;
    }

    // Read token from header
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    // Fallback: Check POST body or decoded_input
    if (!$provided) {
        $decoded = $_REQUEST['decoded_input'] ?? null;
        if ($decoded && isset($decoded['csrf_token'])) {
            $provided = $decoded['csrf_token'];
        }
    }

    // Validate
    if (!$provided) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing CSRF token']);
        exit;
    }

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $provided)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    return true;
}
```

---

## 🔍 Real Examples from Your Code

### Example 1: Login Form (NO Hidden Field)

**File: `LoginPage.js`**

```javascript
// Lines 190-270
<Box component="form" onSubmit={handleLogin}>
  {/* Role Selection */}
  <FormControl fullWidth>
    <Select value={loginForm.role}>
      {roles.map(r => <MenuItem value={r.value}>{r.label}</MenuItem>)}
    </Select>
  </FormControl>

  {/* Email */}
  <TextField
    label="Email"
    value={loginForm.email}
    onChange={(e) => setLoginForm({...loginForm, email: e.target.value})}
  />

  {/* Password */}
  <TextField
    label="Password"
    type="password"
    value={loginForm.password}
    onChange={(e) => setLoginForm({...loginForm, password: e.target.value})}
  />

  {/* ❌ NO HIDDEN CSRF FIELD! */}

  {/* Submit */}
  <Button type="submit">Sign In</Button>
</Box>
```

**Handle Submit:**
```javascript
const handleLogin = (e) => {
  e.preventDefault();
  dispatch(loginStart(loginForm));  // { email, password, role }
  // ← CSRF token NOT in form data
  // ← CSRF token added by Axios interceptor!
};
```

---

### Example 2: Invoice Form (NO Hidden Field)

**File: `InvoiceForm.js`**

```javascript
// Lines 45-125
<Form form={form} layout="vertical" onFinish={onFinish}>
  <Form.Item name="patientId">
    <Select placeholder="Select Patient">
      {patients.map(p => <Option value={p.id}>{p.name}</Option>)}
    </Select>
  </Form.Item>

  <Form.Item name="totalAmount">
    <InputNumber placeholder="Total Amount" />
  </Form.Item>

  {/* ❌ NO <input type="hidden" name="csrf_token"> */}

  <Form.Item>
    <Button type="primary" htmlType="submit">
      Create Invoice
    </Button>
  </Form.Item>
</Form>
```

**On Submit:**
```javascript
const onFinish = (values) => {
  const payload = {
    patientId: values.patientId,
    totalAmount: values.totalAmount,
    // ← csrf_token NOT here
  };

  dispatch({ type: 'billing/createStart', payload });
  // ← Saga will call postData()
  // ← postData() uses Axios
  // ← Axios interceptor adds CSRF header automatically
};
```

---

### Example 3: Register Form (NO Hidden Field)

**File: `RegisterPage.js`**

```javascript
// Lines 252-460
<Box component="form" onSubmit={onSubmit}>
  <Grid container spacing={2}>
    {/* Role */}
    <Grid item xs={12} sm={6} md={4}>
      <Select value={role} onChange={(e) => setRole(e.target.value)}>
        <MenuItem value="patient">Patient</MenuItem>
        <MenuItem value="doctor">Doctor</MenuItem>
      </Select>
    </Grid>

    {/* Name, Email, Password... */}
    <Grid item xs={12} sm={6} md={4}>
      <TextField label="Full Name" value={form.name} />
    </Grid>

    {/* ❌ NO CSRF HIDDEN FIELD */}

    <Grid item xs={12}>
      <Button type="submit">Register</Button>
    </Grid>
  </Grid>
</Box>
```

---

## 📊 Comparison Table

| Feature | Hidden Form Fields | HTTP Headers (Your Approach) |
|---------|-------------------|------------------------------|
| **Requires changes to forms?** | Yes, every form | No, automatic |
| **Works with AJAX?** | Needs manual extraction | Perfect fit |
| **Works with REST APIs?** | Not ideal | Perfect |
| **Maintenance effort** | High | Low |
| **Code duplication** | High (add to each form) | None (one interceptor) |
| **Error-prone** | Yes (easy to forget) | No (automatic) |
| **SPA Friendly** | ❌ | ✅ |
| **Security Level** | ✅ Same | ✅ Same |

---

## 🎨 Visual Flow

```
┌─────────────────────────────────────────────────────────┐
│                   USER INTERACTION                      │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ↓
        ┌───────────────────────────────┐
        │   User submits form           │
        │   (NO csrf token in form)     │
        └───────────────┬───────────────┘
                        │
                        ↓
        ┌───────────────────────────────┐
        │   handleSubmit()              │
        │   dispatch(action(formData))  │
        └───────────────┬───────────────┘
                        │
                        ↓
        ┌───────────────────────────────┐
        │   Saga calls postData()       │
        └───────────────┬───────────────┘
                        │
                        ↓
┌───────────────────────────────────────────────────────────┐
│              AXIOS REQUEST INTERCEPTOR                    │
│  ┌─────────────────────────────────────────────────────┐ │
│  │ 1. Get CSRF from Redux:                             │ │
│  │    const csrf = store.getState().auth.csrfToken     │ │
│  │                                                       │ │
│  │ 2. Add to header:                                    │ │
│  │    config.headers['X-CSRF-Token'] = csrf            │ │
│  │                                                       │ │
│  │ 3. Encrypt body (if needed)                          │ │
│  │                                                       │ │
│  │ 4. Send request                                      │ │
│  └─────────────────────────────────────────────────────┘ │
└───────────────────────┬───────────────────────────────────┘
                        │
                        ↓ HTTP Request
┌───────────────────────────────────────────────────────────┐
│  POST /api/endpoint HTTP/1.1                              │
│  X-CSRF-Token: abc123xyz789...  ← Automatically added!    │
│  Content-Type: application/json                           │
│                                                            │
│  { "name": "John", "age": 30 }                            │
└───────────────────────┬───────────────────────────────────┘
                        │
                        ↓
┌───────────────────────────────────────────────────────────┐
│                   BACKEND VALIDATION                      │
│  ┌─────────────────────────────────────────────────────┐ │
│  │ CsrfMiddleware::validate()                          │ │
│  │  1. Check HTTP_X_CSRF_TOKEN header                  │ │
│  │  2. Compare with $_SESSION['csrf_token']            │ │
│  │  3. Allow or deny request                           │ │
│  └─────────────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────────────┘
```

---

## 🔧 How to Use This in Your Forms

### ✅ Correct Way (What You're Doing)

```javascript
import { postData } from '../api/client';

const MyForm = () => {
  const [formData, setFormData] = useState({ name: '', email: '' });

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Just send your data - CSRF is automatic!
    try {
      const result = await postData('/endpoint', formData);
      message.success('Success!');
    } catch (error) {
      message.error('Failed');
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input name="name" value={formData.name} />
      <input name="email" value={formData.email} />
      {/* ← NO CSRF field needed! */}
      <button type="submit">Submit</button>
    </form>
  );
};
```

---

### ❌ Wrong Way (Don't Do This)

```javascript
// ❌ Don't manually add CSRF to form data
const handleSubmit = async (e) => {
  e.preventDefault();
  
  // ❌ DON'T DO THIS - It's automatic!
  const csrf = store.getState().auth.csrfToken;
  const payload = {
    ...formData,
    csrf_token: csrf  // ← Unnecessary!
  };
  
  await postData('/endpoint', payload);
};
```

```jsx
{/* ❌ DON'T ADD HIDDEN FIELD - It's automatic! */}
<form>
  <input type="text" name="name" />
  <input type="hidden" name="csrf_token" value={csrf} /> {/* ← Don't! */}
  <button type="submit">Submit</button>
</form>
```

---

## 🐛 Troubleshooting

### Issue: "Missing CSRF token" Error

**Cause:** CSRF token not fetched or not in Redux state

**Solution:**
```javascript
// Make sure component fetches CSRF on mount
useEffect(() => {
  api.get('/csrf-token').then(res => {
    dispatch(setCsrfToken(res.data.csrfToken));
  });
}, []);

// Check if token exists
console.log(store.getState().auth.csrfToken); // Should not be null
```

---

### Issue: "Invalid CSRF token" Error

**Causes:**
1. Frontend and backend sessions don't match
2. Token expired (session expired)
3. Token mismatch

**Solutions:**
```javascript
// 1. Regenerate CSRF token
api.post('/csrf-regenerate').then(res => {
  dispatch(setCsrfToken(res.data.csrf_token));
});

// 2. Check cookies (session cookie should exist)
console.log(document.cookie); // Should see PHP session cookie
```

---

### Issue: CSRF Works on Some Routes But Not Others

**Cause:** Route not applying CSRF middleware

**Check Backend Routes:**
```php
// api.php
Route::post('endpoint', 'Controller@method', ['CsrfMiddleware']);
//                                             ↑ Make sure this is here!
```

**Public routes (no CSRF):**
```php
Route::post('login', 'AuthController@login');  // No CSRF middleware
Route::post('register', 'AuthController@register');  // No CSRF
```

---

## 📋 Checklist: CSRF Implementation

**Frontend:**
- [x] Component fetches CSRF token on mount
- [x] CSRF stored in Redux (`authSlice`)
- [x] Request interceptor adds `X-CSRF-Token` header
- [x] No hidden CSRF fields in forms

**Backend:**
- [x] CSRF token generated with `bin2hex(random_bytes(32))`
- [x] Token stored in PHP session
- [x] Middleware validates `HTTP_X_CSRF_TOKEN` header
- [x] Protected routes include `CsrfMiddleware`

---

## 🎓 Why This Approach is Better

### 1. **Developer Experience**
```javascript
// With hidden fields (old way)
<form>
  <input type="hidden" name="csrf" value={getCsrf()} /> ← Manual
  <TextField />
  <TextField />
</form>

// With headers (modern way)
<form>
  <TextField />  ← Clean!
  <TextField />
</form>
```

### 2. **API-First Design**
Perfect for REST APIs where multiple clients (web, mobile, desktop) consume the API.

### 3. **Single Source of Truth**
CSRF token managed in one place (Redux), not scattered across forms.

### 4. **Works Everywhere**
- Traditional forms ✅
- AJAX calls ✅
- File uploads ✅
- WebSocket handshakes ✅

### 5. **Future-Proof**
Easy to migrate to GraphQL, gRPC, or other protocols.

---

## 🔒 Security Note

**Both approaches provide equal security:**

The key is that the token is:
1. **Unpredictable** (cryptographically random)
2. **Server-verified** (compared with session)
3. **Short-lived** (expires with session)

Whether it's in:
- Form body (`<input type="hidden">`)
- HTTP header (`X-CSRF-Token`)

...doesn't affect security. What matters is the token itself!

---

## 📚 References

- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [MDN: CSRF](https://developer.mozilla.org/en-US/docs/Glossary/CSRF)
- [Double Submit Cookie Pattern](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#double-submit-cookie)

---

## ✅ Summary

**Question:** Do we use hidden CSRF fields in forms?

**Answer:** **NO!** 

We use a **modern, header-based approach** that:
- Automatically injects CSRF tokens via Axios interceptor
- Stores tokens in Redux state (not form fields)
- Sends tokens via `X-CSRF-Token` HTTP header
- Provides better developer experience
- Is perfect for React/SPA architecture
- Maintains the same security level

**Your forms are clean and don't need hidden fields!** 🎉

---

**Last Updated:** 2025-12-26  
**Version:** 1.0
