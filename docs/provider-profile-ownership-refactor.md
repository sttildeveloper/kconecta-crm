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

## Estado de produccion (2026-08-03)

- Las migraciones `2026_08_02_170000`, `170100` y `170200` estan aplicadas.
- Se registraron manualmente en lotes `16`, `17` y `18` porque produccion conserva
  el esquema legacy de la tabla `migrations`.
- Backup previo: `/root/kconecta_backups/20260802_2055_pre_provider_media_migrations/db_production.sql.gz`.
- SHA-256: `7ea1e9c0f8594a2fd72c82840fbdd3dbd46752247b22b2f5868847ded80b671c`.
- El endpoint `/api/services_for_map` volvio a responder HTTP `200` despues de las
  migraciones y la limpieza de cache.
- El backfill productivo de portadas y galerias genericas aun no se ha ejecutado.

## Validación local

- Backfill local comprobado: 3 portadas, 7 imágenes de galería y 1 vídeo migrados.
- Asociaciones dobles después del backfill: 0.
- Suite completa tras el backfill genérico: 138 pruebas, 1.292 aserciones.
- Pruebas dirigidas posteriores: 12 pruebas, 68 aserciones.

## Fase final pendiente

1. Crear backup fresco de base de datos y volumen multimedia antes del backfill productivo.
2. Ejecutar simulacion y luego, con autorizacion, el backfill generico en produccion.
3. Validar edición de ficha web, API móvil, búsqueda, detalle público y métricas.
4. Confirmar que no se crean nuevas filas en `service`.
5. Retirar aliases y código CRUD legacy.
6. Eliminar referencias `service_id` redundantes.
7. Eliminar `service_address` y finalmente `service`, mediante una migración separada.
