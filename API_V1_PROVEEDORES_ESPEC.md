## API v1 Proveedores (`/api/agent/services`) - Legacy

### Estado de vigencia
- Esta especificacion queda marcada como `legacy`.
- Regla de negocio vigente:
- el `Proveedor de servicios` no publica servicios individuales.
- El proveedor mantiene exclusivamente su ficha publica:
- datos personales/publicos,
- logo/foto,
- direccion,
- tipos de servicio que ofrece,
- galeria,
- video,
- valoraciones y metricas.
- Cualquier continuidad de `/api/agent/services` debe entenderse solo como compatibilidad transitoria mientras se migra a endpoints centrados en `perfil de proveedor`.
- Estado del modelo de datos relacionado a tipos:
- catalogo oficial: `service_type`
- relacion oficial proveedor ↔ tipo: `provider_services`
- nomenclatura canonica de lectura: `specialties` y `specialty_ids`
- aliases legacy API todavia posibles: `service_types` y `service_type_ids`

Autenticacion: `Authorization: Bearer <token>` con `auth:sanctum`.

### Endpoints
1. `GET /api/agent/services`
- Lista paginada de servicios del proveedor autenticado.
- Query opcional: `per_page` (1-100).

2. `POST /api/agent/services`
- Crea servicio del proveedor autenticado.
- `multipart/form-data`.
- Campos requeridos: `availability`, `description`, `service_type[]`, `cover_image`.
- Campos opcionales: `title`, `page_url`, `document_number`, `more_images[]`, `video`, `address`, `city`, `province`, `postal_code`, `country`, `latitude`, `longitude`.
- Limites media v1: `cover_image` y `more_images[]` max `5MB` c/u; `video` max `50MB`.

3. `GET /api/agent/services/{id}`
- Devuelve detalle de un servicio propio.

4. `PUT/PATCH /api/agent/services/{id}`
- Actualiza un servicio propio.
- Soporta reemplazo de portada/video y alta de `more_images[]`.
- `delete_more_images[]` permite borrar imagenes adicionales por id.

5. `DELETE /api/agent/services/{id}`
- Elimina un servicio propio junto con sus relaciones (`cover`, `more_images`, `video`, `address`).
- La relacion proveedor ↔ especialidades ya no depende de `service_types`; se resuelve por `provider_services`.

### Contrato de respuesta
Todas las respuestas usan:

```json
{
  "success": true,
  "data": {},
  "meta": null,
  "message": "string|null",
  "errors": null
}
```

Errores:

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "message": "string",
  "errors": {}
}
```

### Codigos de estado
- `200` OK
- `201` Created
- `401` No autenticado
- `403` No autorizado (rol no proveedor)
- `404` Servicio no encontrado o no pertenece al proveedor
- `422` Validacion fallida

### Politica 403 vs 404
- `403`: usuario autenticado sin rol `Proveedor de servicios`.
- `404`: usuario proveedor intenta operar un servicio inexistente o que no le pertenece (ownership estricto).

### Ejemplo rapido mobile
1. Login:
- `POST /api/login`
- Recibir `token`.

2. Crear servicio:

```bash
curl -X POST https://kconecta.com/api/agent/services \
  -H "Authorization: Bearer TOKEN" \
  -F "title=Fontanero urgente" \
  -F "availability=Lun-Vie 08:00-20:00" \
  -F "description=Reparaciones y mantenimiento" \
  -F "service_type[]=1" \
  -F "cover_image=@cover.jpg"
```

3. Actualizar servicio:

```bash
curl -X PATCH https://kconecta.com/api/agent/services/123 \
  -H "Authorization: Bearer TOKEN" \
  -F "description=Atencion 24h" \
  -F "video=@promo.mp4"
```
