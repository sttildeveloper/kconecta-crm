# Store Compliance API Contract (iOS / Android)

Last updated: 2026-05-22

## Base
- Base URL: `https://kconecta.com/api`
- Auth: Bearer token (`auth:sanctum`) where required.
- Standard response shape:
  - `success` (bool)
  - `data` (mixed)
  - `meta` (mixed|null)
  - `message` (string|null)
  - `errors` (object|null)

## 1) Forgot password
- `POST /forgot-password`
- Auth: public
- Rate limit: `throttle:5,1`
- Payload:
```json
{
  "email": "user@example.com"
}
```
- Success (`200`):
```json
{
  "success": true,
  "data": null,
  "meta": null,
  "message": "Si el correo existe, recibiras instrucciones para restablecer tu contraseña.",
  "errors": null
}
```
- Validation error (`422`): invalid email payload.

## 2) Reset password
- `POST /reset-password`
- Auth: public
- Rate limit: `throttle:5,1`
- Payload:
```json
{
  "email": "user@example.com",
  "token": "token_recibido",
  "password": "NuevaPassword123!",
  "password_confirmation": "NuevaPassword123!"
}
```
- Success (`200`):
```json
{
  "success": true,
  "data": null,
  "meta": null,
  "message": "Contraseña actualizada correctamente.",
  "errors": null
}
```
- Invalid/expired token (`400`):
```json
{
  "success": false,
  "data": null,
  "meta": null,
  "message": "No se pudo restablecer la contraseña. El token puede ser invalido o haber expirado.",
  "errors": {
    "token": [
      "..."
    ]
  }
}
```

## 3) Delete account (store-compliance)
- Primary: `DELETE /me`
- Compatibility alias: `POST /account/delete`
- Auth: required (`Bearer`)
- Payload:
```json
{
  "password": "contraseña_actual",
  "reason": "opcional"
}
```
- Success (`200`):
```json
{
  "success": true,
  "data": null,
  "meta": null,
  "message": "Cuenta eliminada correctamente.",
  "errors": null
}
```
- Wrong password (`401`): `Credenciales incorrectas.`
- Validation (`422`): missing password.

## Deletion business behavior
- Requires password confirmation.
- Revokes all Sanctum tokens.
- Anonymizes direct user PII fields.
- Disables account login (`is_active=0` when column exists).
- Removes profile address rows in `user_address` when present.

## Public legal URLs (HTML)
- `https://kconecta.com/legal/privacy`
- `https://kconecta.com/legal/terms`
- `https://kconecta.com/legal/account-deletion`
