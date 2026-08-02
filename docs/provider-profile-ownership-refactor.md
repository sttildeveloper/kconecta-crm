# Refactor de propiedad de la ficha del proveedor

Fecha: 2026-08-02

## Regla canónica

El proveedor de servicios mantiene una única ficha pública. No publica servicios individuales.

La raíz del agregado es `user.id` para usuarios con `user_level_id = 4`:

- datos públicos: columnas `provider_*` de `user`
- dirección: `user_address.user_id`
- especialidades: `provider_services.provider_id` -> `service_type.id`
- portada, galería y vídeo: `provider_user_id` en las tablas de multimedia
- valoraciones, códigos, visitas y contactos: `provider_user_id`

## Cambios implementados

- `cover_image`, `more_images` y `video` incorporan `provider_user_id` indexado.
- El backfill mueve multimedia legacy de proveedores desde `service_id` hacia `provider_user_id`.
- Después del backfill, la multimedia de proveedor conserva `service_id = null`.
- `provider_media_legacy_links` conserva temporalmente el vínculo anterior exacto para permitir rollback durante QA; la aplicación no consulta esta tabla.
- Las copias provisionales se identifican con `is_provider_default` y `source_provider_user_id`, de modo que puedan sustituirse sin mezclar contenido genérico y real.
- La multimedia de propiedades y registros no pertenecientes a proveedores no se modifica.
- Los campos públicos de la ficha se almacenan directamente en `user`.
- Las direcciones legacy se copian a `user_address` solo cuando faltan datos canónicos.
- Las nuevas rutas canónicas son:
  - web: `GET /post/provider-profile/edit`, `POST /post/provider-profile`
  - API: `GET /api/agent/provider-profile`, `PATCH /api/agent/provider-profile`
- Los aliases `/api/agent/services*` permanecen temporalmente para compatibilidad, pero ya no crean publicaciones.
- El borrado legacy de servicios queda bloqueado y devuelve `410`.
- Las métricas nuevas guardan `provider_user_id` y dejan `service_id = null`.

## Compatibilidad temporal

La tabla `service`, `service_address`, las columnas `service_id` y las lecturas públicas legacy permanecen en esta fase. No deben eliminarse hasta completar pruebas locales, despliegue controlado, QA web/móvil y auditoría de referencias en producción.

## Validación local

- Backfill local comprobado: 3 portadas, 7 imágenes de galería y 1 vídeo migrados.
- Asociaciones dobles después del backfill: 0.
- Suite completa tras el backfill genérico: 138 pruebas, 1.292 aserciones.
- Pruebas dirigidas posteriores: 12 pruebas, 68 aserciones.

## Fase final pendiente

1. Crear backup de base de datos y volúmenes multimedia de producción.
2. Ejecutar las dos migraciones y registrar sus metadatos en la tabla legacy `migrations`.
3. Validar edición de ficha web, API móvil, búsqueda, detalle público y métricas.
4. Confirmar que no se crean nuevas filas en `service`.
5. Retirar aliases y código CRUD legacy.
6. Eliminar referencias `service_id` redundantes.
7. Eliminar `service_address` y finalmente `service`, mediante una migración separada.
