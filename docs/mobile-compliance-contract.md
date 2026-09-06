# Contrato móvil de cumplimiento

Estado: preparado en CRM, pendiente de migrar/desplegar coordinadamente. No asumir disponibilidad en producción hasta confirmación.

## Eliminación de cuenta

- `DELETE /api/me` o alias temporal `POST /api/account/delete`.
- Bearer token obligatorio.
- JSON: `{ "password": "...", "reason": "opcional, máximo 500" }`.
- Éxito `200`; validación `422`; contraseña incorrecta `401`. Tras el éxito el token deja de ser válido, por lo que una repetición normal devuelve `401`.
- La app debe borrar token y estado local al recibir éxito.

## Denuncias

- `POST /api/reports`, autenticado, límite 5/minuto.
- JSON: `reported_user_id`, `content_type` (`user` o `provider_profile`), `content_id` opcional, `reason`, `details` opcional (máximo 2000).
- Motivos: `impersonation`, `fraud`, `harassment`, `discrimination`, `sexual_content`, `illegal_service`, `spam`, `personal_data`, `intellectual_property`, `other`.
- Estados: `pending`, `reviewing`, `resolved`, `rejected`.
- Autodenuncia: `422`; denuncia activa repetida: `409`.

## Bloqueos

- `GET /api/me/blocks`.
- `POST /api/users/{user_id}/block` (idempotente).
- `DELETE /api/users/{user_id}/block` (idempotente).
- Autobloqueo: `422`.
- `GET /api/providers/{id}` añade `data.is_blocked` cuando se consulta con Bearer token. La app debe ocultar o marcar las interacciones con ese proveedor.

## Aceptaciones legales

- `GET /api/legal/documents`: versiones vigentes, URL y `required_on_registration`.
- `GET /api/me/legal-acceptances` y `POST /api/me/legal-acceptances` autenticados.
- Registro opcional compatible hoy:

```json
{
  "legal_acceptances": [
    {"type": "terms", "version": "VERSION_DEVUELTA_POR_API"},
    {"type": "privacy", "version": "VERSION_DEVUELTA_POR_API"}
  ]
}
```

`LEGAL_ACCEPTANCE_REQUIRED_ON_REGISTRATION=false` mantiene compatibles las versiones móviles existentes. Antes de activarlo hay que: definir versiones, publicar textos definitivos, lanzar la app que envía ambas aceptaciones y después habilitar el flag.

## Cambio incompatible de correo web (no móvil)

Compartir por correo pasa de GET a `POST /api/send/message/email_share`; el destinatario del proveedor nunca se recibe desde el cliente y se resuelve mediante `property_id` o `provider_user_id` interno.
