# Ephemeral Pass - One-Time Password Sharing API 🔐

A secure, API-only Laravel service for sharing sensitive passwords via self-destructing, one-time-use links.

## Features

✅ Encrypted Storage – Passwords are encrypted using AES-256.  
✅ One-Time Access – Secrets are deleted immediately after retrieval.  
✅ Optional Passphrase Protection – Users can add an extra decryption step.  
✅ Expiration Control – Secrets automatically expire after a set time.  
✅ API-Only Design – No UI, built for integration.

## API Endpoints

### 1️⃣ Create a Secret

```http
POST /api/secrets
```
#### Request Body:
```json
{
  "password": "mySecret123",
  "passphrase": "optionalPassphrase",
  "expires_in": 60
}
```
- Max length for `password` and `passphrase` is 255 characters.
- `passphrase` is optional.
- `expires_in` is in minutes (default: 60). Minimum 5 minutes.

#### Response (if no passphrase):
```json
{
  "url": "https://ephemeral-pass.io/api/secrets/abcd1234?token=xyz987",
  "expires_at": "2025-03-02T12:00:00Z"
}
```

#### Response (if passphrase is set):
```json
{
  "url": "https://ephemeral-pass.io/api/secrets/abcd1234?passphrase=",
  "note": "This secret requires the passphrase at the end of the URL to decrypt.",
  "expires_at": "2025-03-02T12:00:00Z"
}
```

---

### 2️⃣ Retrieve a Secret without a passphrase

```http
GET /api/secrets/{id}?token={decryption_key}
```

- Returns the decrypted password (if valid).
- Deletes the secret after access.

---

### 3️⃣ Decrypt with a Passphrase

```http
GET /api/secrets/{id}?passphrase={userPassphrase}
```

- If the passphrase is correct, returns the decrypted password.
- Deletes the secret after retrieval.

---

## Security Features
🔒 AES-256 Encryption – Ensures password confidentiality.  
🔒 Self-Destructing Secrets – Data is wiped after first access or after access when is expired.  
🔒 HTTPS-Only – Protects against man-in-the-middle attacks.
