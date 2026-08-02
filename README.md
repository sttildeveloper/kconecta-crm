# Kconecta CRM

## Context checkpoint updated: 2026-08-03 (public map and provider media)

- La ficha del proveedor tiene como raíz canónica `user.id`; el proveedor no publica servicios individuales.
- Multimedia de proveedor migrada a `provider_user_id` en `cover_image`, `more_images` y `video`.
- Datos públicos en columnas `provider_*`, dirección en `user_address` y especialidades en `provider_services`.
- Nuevas rutas canónicas web/API para actualizar una única ficha.
- CRUD `/agent/services` conservado solo como alias transitorio y sin creación/borrado de publicaciones.
- `service` y `service_address` todavía no se eliminan; su retirada queda condicionada a QA local/productivo.
- Detalle técnico y plan de retirada: `docs/provider-profile-ownership-refactor.md`.
- Backfill local de multimedia genérica validado para los 2.903 proveedores; procedimiento en `docs/provider-default-media.md`.

- Las tres migraciones de propiedad multimedia ya estan aplicadas en produccion.
- La replica productiva de portadas y galerias genericas sigue pendiente; los archivos
  locales de prueba no se distribuyen mediante Git.

CRM inmobiliario de Kconecta migrado desde un proyecto legacy.

## Current Production Checkpoint (2026-08-03)

- El home publico permite buscar y contactar proveedores sin registro.
- El registro de cliente solo es obligatorio para valorar proveedores.
- El home usa Google Places y envia direccion, servicio y coordenadas a la pagina
  existente `/result/services` en modo mapa.
- Cuando Google no entrega ciudad/provincia, la busqueda usa proximidad por
  coordenadas con un radio configurable de 30 km.
- El boton Atrás del navegador vuelve al home; el mapa ya no llena el historial.
- La clave web de Google esta activa en Dokploy y separada de las claves nativas.
- La base productiva tiene 2.895 proveedores geolocalizados saneados, 0 ciudades
  sin resolver y 0 direcciones pendientes.
- Ocho perfiles sin coordenadas permanecen fuera del buscador hasta completar su
  ubicacion, conforme a la regla de registro de proveedor sin direccion obligatoria.
- El filtro vacio `sti[]=` se descarta y ya no se convierte en una especialidad `0`.
- El mapa Google agrupa marcadores densos y conserva el centro solicitado con zoom
  minimo `13` para busquedas resueltas desde una direccion.
- Al pulsar un cluster se abre una lista desplazable con todos sus proveedores; no
  se intenta separar coordenadas exactamente iguales mediante zoom o desplazamiento.
- Releases de estabilizacion desplegados: `52a3e39`, `55012b5` y `13ac8da`.
- Validacion enfocada final: 18 pruebas y 182 aserciones correctas; JavaScript del
  mapa validado con `node --check`.
- Documento tecnico: [docs/public-provider-search.md](./docs/public-provider-search.md).
- Runbook geografico: [docs/provider-address-sanitization.md](./docs/provider-address-sanitization.md).
- Fotos de proveedor: 2.903 de 2.903 proveedores tienen foto; 2.902 usan copias
  individuales temporales del logo Kconecta hasta subir una propia.
- Runbook de fotos: [docs/provider-default-photos.md](./docs/provider-default-photos.md).

## Provider Canonical Rule
- El `Proveedor de servicios` no publica servicios individuales.
- El proveedor:
- crea su cuenta desde un registro validado,
- accede al CRM con sus credenciales,
- completa su perfil publico,
- gestiona solo su informacion de proveedor:
- logo/foto,
- direccion,
- tipos de servicio que ofrece,
- galeria de imagenes,
- video,
- y su reputacion/media.
- Cualquier referencia historica en el repo a `publicar servicios`, `createService()` o CRUD de servicios de proveedor debe considerarse legacy u obsoleta frente a esta regla de negocio.

## Provider Service Types Canonical Model
- Catalogo oficial de tipos de servicio: tabla `service_type`.
- Relacion oficial entre proveedor y tipos de servicio: tabla `provider_services`.
- `provider_services` guarda:
- `provider_id`
- `service_type_id`
- timestamps
- Restriccion funcional:
- no se permiten duplicados de la pareja `provider_id + service_type_id`.
- Nomenclatura canonica en payloads internos y vistas:
- `specialties`
- `specialty_ids`
- Compatibilidad transitoria en algunas respuestas API:
- se conservan aliases `service_types` y `service_type_ids` mientras existan consumidores legacy.
- La antigua tabla relacional `service_types` deja de formar parte del modelo vigente proveedor ↔ tipo de servicio.

## Repository
- GitHub: `https://github.com/sttildeveloper/kconecta-crm`
- Branch principal: `main`
- Remote activo: `origin`

## Stack
- Laravel 12
- PHP 8.2
- MySQL 8
- Docker Compose

## Local Run
```powershell
cd C:\MeegDev\kconecta-crm\web
docker compose -p kconecta up -d --build
```

App local:
- `http://localhost:8010`

## Database
- Schema local docker: `kconecta_schema`
- Schema productivo: `kconecta-mysql`

## Backup Workspace
- Carpeta local de respaldo operativo: `backups/`
- Uso previsto antes de cambios sensibles o limpieza de media:
- dump de BD local
- dump/export de BD productiva
- inventario de media investigada
- copia de media productiva descargada desde host cuando aplique
- Convencion recomendada de carpetas:
- `backups/YYYYMMDD_HHMM_pre_commit_sync/`
- Dentro de cada carpeta:
- `db_local.sql`
- `db_production.sql`
- `media_production/`
- `notes.md`

## UTF-8 Restore Note
- El proyecto trabaja con `utf8mb4` en Laravel y MySQL.
- Si se importa un dump `.sql` desde PowerShell como texto, los caracteres especiales pueden degradarse (`España` -> `Espa?a` o `Espa??a`).
- Para restaurar dumps locales, copiar primero el archivo al contenedor MySQL y ejecutar la importacion en bruto:

```powershell
docker compose cp "C:\ruta\al\db_production.sql" mysql:/tmp/db_production.sql
docker compose exec -T mysql mysql -uroot -psecret -e "DROP DATABASE IF EXISTS kconecta_schema; CREATE DATABASE kconecta_schema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker compose exec -T mysql sh -lc "mysql --default-character-set=utf8mb4 -uroot -psecret kconecta_schema < /tmp/db_production.sql"
```

- No usar `Get-Content ... | mysql` para restaurar dumps UTF-8 de produccion.

## Migration Note
Se agrego una migracion para asegurar compatibilidad de hashes de password:
- `database/migrations/2026_03_01_010900_expand_user_password_column.php`

## Production Status (2026-04-21)
- Entorno productivo activo en Dokploy.
- URL: `https://kconecta.com/`
- Deploy automatico activo sobre `main`.
- Home y login operativos.
- Login y panel autenticado validados manualmente en produccion.
- Warning de Apache `ServerName` ya resuelto.
- Las altas y ediciones de propiedades requieren direccion valida resuelta por Google Maps.
- El formulario web de propiedades usa un flujo compatible con `Places API (New)`.
- Las imagenes no WebP se convierten a `.webp` antes de persistirse.
- Los principales flujos de alta por tipo ya fueron validados online en produccion.
- La edicion con reemplazo de multimedia ya fue validada online.
- Alta y edicion de `Garaje` quedaron validadas online tanto para venta como para alquiler.
- Gala probo online el flujo de `Garaje` y reporto funcionamiento correcto.
- La edicion de propiedades ya permite seleccionar multiples imagenes para la galeria en un solo paso.
- El fix de carga multiple en galeria fue validado en local para `Piso` y `Local o nave`.
- El fix de carga multiple en galeria fue validado online tras deploy sin incidencias reportadas.
- Registro local de `Terreno` validado para alquiler y venta.
- Edicion de `Terreno` ajustada para corregir layout de titulo y descripcion.
- El modulo de `Terreno` fue ampliado para separar `Tipo de terreno` de `Uso`.
- `Terreno` ahora usa:
- `Tipo de terreno`: `Urbano`, `Urbanizable`, `Rústico`
- `Uso`: `Servicios`, `Residencial`, `Industrial`, `Agrícola`
- El detalle publico de `Terreno` ahora muestra arriba los recuadros `Tipo de terreno` y `Uso`.
- El alta de `Terreno` en produccion quedo validada despues de aplicar migraciones y limpiar cache.
- El bloque operativo de `Terreno` implementado en esta ronda se considera cerrado.
- Las vistas toleran propiedades sin portada y muestran placeholder sin `500`.
- Backup operativo previo a cambios productivos creado en host:
- `/root/kconecta_backups/20260415_1656_pre_commit_sync`
- Backup operativo previo al cambio de `Terreno` creado en host:
- `/root/kconecta_backups/20260420_2313_pre_terreno`
- Contenedor MySQL usado para backup y verificacion en produccion:
- `kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3`
- Contenedor app usado para migraciones y cache clear en produccion:
- `kconecta-kconectacrm-5oikfs.1.8j4e7feeo9l3yxw5hap9vhw8k`
- Persistencia de media en produccion corregida en Dokploy con volumenes para:
- `/var/www/html/public/img/uploads`
- `/var/www/html/public/video/uploads`
- `/var/www/html/public/img/photo_profile`
- La restauracion de media desde backup fue validada.
- Un redeploy posterior mantuvo las imagenes existentes.
- Una subida nueva posterior al fix tambien persistio tras redeploy.

## Production Validation Snapshot
Flujos de alta validados online:
- `Casa o chalet`
- `Piso`
- `Local o nave`
- `Garaje`
- `Terreno`
- `Casa rustica`

Flujos de edicion validados online:
- edicion de `Piso`
- seleccion multiple de imagenes en galeria al editar `Piso`
- seleccion multiple de imagenes en galeria al editar `Local o nave`
- edicion de `Garaje` en venta y alquiler
- reemplazo de portada
- agregado de imagenes adicionales
- borrado diferido de imagenes adicionales
- reemplazo de video
- alta de `Terreno` con `Uso` y `Tipo de terreno` separados
- detalle publico de `Terreno` con recuadros superiores de `Tipo de terreno` y `Uso`

## Terrain Change (2026-04-21)
- Commit publicado:
- `eadae0a` - `Add terrain use support and normalize land forms`
- Cambio de datos implementado:
- nueva tabla `terrain_use`
- nueva columna nullable `property.terrain_use_id`
- migracion que garantiza la presencia de `Urbanizable` en `type_of_terrain`
- Cambio funcional implementado solo para `Terreno`:
- formularios web de alta y edicion muestran `Uso`
- backend web guarda `terrain_use_id`
- API de propiedades expone y acepta `terrain_use`
- detalle publico recibe y muestra `terrain_use`
- Estrategia de compatibilidad:
- no se borraron de BD los valores legacy de `type_of_terrain` (`Servicios`, `Industrial`, `Afectado`)
- esos valores legacy dejaron de exponerse en el formulario/catalogo de `Terreno`
- los `Terreno` existentes en produccion usaban solo `Urbano` al momento del cambio

## Production Backup Drill
- Ruta de backup validada para este cambio:
- `/root/kconecta_backups/20260420_2313_pre_terreno`
- Archivos validados:
- `db_production.sql.gz`
- `type_of_terrain.tsv`
- `terrain_properties.tsv`
- Comando funcional para dump productivo:
```bash
docker exec kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3 sh -lc 'mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > "$BKP_DIR/db_production.sql"
```
- Comando funcional para snapshot del catalogo `type_of_terrain`:
```bash
docker exec kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3 sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT id,name FROM type_of_terrain ORDER BY id"' > "$BKP_DIR/type_of_terrain.tsv"
```
- Comando funcional para snapshot de propiedades `Terreno`:
```bash
docker exec kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3 sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT id,reference,title,type_id,type_of_terrain_id FROM property WHERE type_id=9 ORDER BY id"' > "$BKP_DIR/terrain_properties.tsv"
```

## Migration Caveat
- La tabla `migrations` de este entorno legacy no coincide con la expectativa estandar de Laravel.
- Columnas observadas:
- `id`, `migration`, `version`, `class`, `group`, `namespace`, `time`, `batch`
- Resultado operativo:
- `php artisan migrate` ejecuta el SQL pero falla al registrar la migracion si no se completan esos campos extra.
- En produccion se resolvio registrando manualmente las migraciones ya ejecutadas mediante `php artisan tinker --execute ... updateOrInsert(...)`.
- Este comportamiento debe tenerse en cuenta antes de futuras migraciones productivas.

## Google Maps Requirements
Para que el flujo de direcciones funcione en local y produccion:
- Variable de entorno:
- `GOOGLE_MAPS_API_KEY`
- Radio de busqueda publica:
- `PROVIDER_SEARCH_RADIUS_KM` (default `30`)
- APIs requeridas en Google Cloud:
- `Maps JavaScript API`
- `Places API`
- `Places API (New)`
- `Geocoding API`
- Hardening recomendado en Google Cloud:
- para el CRM web, restringir la API key por `HTTP referrers`
- permitir solo:
- `https://kconecta.com/*`
- `https://www.kconecta.com/*`
- para la app movil, usar una key separada
- en Android, restringir por `package name + SHA-1`
- en iOS, restringir por `bundle identifier`
- no reutilizar la misma key restringida por referrer en SDKs nativos
- mantener facturacion activa en el proyecto vinculado a la key

## Upload Limit
- Env var:
- `VIDEO_MAX_UPLOAD_MB` (default `40`)
- Keep this value aligned with Dokploy reverse-proxy body size limit to avoid `413 Content Too Large`.

## Deployment Workflow
Politica operativa para cambios que afecten el CRM:
1. Validar el cambio en local.
2. Crear `commit` en `main`.
3. Hacer `push` a `origin/main`.
4. Esperar el `autodeploy` de Dokploy.
5. Verificar rutas criticas, login y el flujo tocado en el entorno desplegado.

Notas:
- Evitar `manual redeploy` salvo que el despliegue automatico falle o queden endpoints caidos.
- No subir dumps, backups ni secretos al repo.
- Si se agregan mounts/volumenes nuevos en Dokploy para media, poblarlos antes de dar por buena la persistencia.
- Las rutas de media que deben mantenerse persistentes en produccion son:
- `/var/www/html/public/img/uploads`
- `/var/www/html/public/video/uploads`
- `/var/www/html/public/img/photo_profile`

## Version Tags
- Usar tags anotados sobre commits importantes ya listos en `main`.
- Esquema: `vMAJOR.MINOR.PATCH`.
- Tags publicados:
- `v0.1.0`
- `v0.1.1`
- Guia detallada: [VERSIONING.md](./VERSIONING.md)

## Current Priorities
- completar QA manual de geolocalizacion permitida/denegada, marcadores y contactos
- validar expresamente el fallback Leaflet/OpenStreetMap en produccion
- decidir con negocio si el radio publico predeterminado de 30 km debe ajustarse
- completar la ubicacion de los 8 perfiles no publicables sin inventar coordenadas
- revisar la navegacion visual legacy de la pagina de resultados
- continuar el trabajo con revision orquestada entre DeepSeek, Mistral, Gemma y Qwen, usando contextos pequenos y responsabilidades separadas
- endurecer seguridad del flujo de autenticacion legacy
- alinear mensaje/limite real de video y preparar compresion frontend
- consolidar el modelo canonico de ficha de proveedor y retirar compatibilidades legacy cuando ya no existan consumidores
- investigar y corregir drift entre referencias en BD y archivos fisicos de media
- igualar formularios web y movil
- definir pipeline consistente de video e imagenes para web y movil

## Mobile API Native Registration (2026-06-28)
Endpoints de registro nativo disponibles para la App Móvil:
- `POST /api/mobile/register-provider`: Registro de Proveedores (`user_level_id = 4`). Revisa al menos `company_name` o `first_name`.
- `POST /api/mobile/register-client`: Registro de Clientes Finales (`user_level_id = 6`).
- Respuestas en JSON estandarizado (`201 Created` / `422 Unprocessable Entity`) con token personal Sanctum para inicio de sesión automático inmediato.

## Project Control Files
- Estado y plan: [tasks.md](./tasks.md)
- Contexto operativo: [agent.md](./agent.md)
- Roadmap operativo: [roadmap.md](./roadmap.md)

## Local Home Redesign Checkpoint (2026-07-26)

Estado historico: implementado y validado inicialmente en local. El release fue
autorizado, desplegado y validado en produccion el 2026-08-02; consultar el
checkpoint superior y `docs/public-provider-search.md`.

El home público `/` se ha sustituido por el diseño aprobado orientado a profesionales de reformas, mantenimiento y reparaciones:

- cabecera responsive, hero y búsqueda por especialidad/zona;
- geolocalización solicitada solo al pulsar `Usar mi ubicación`;
- cuadrícula de servicios con el logo oficial de Kconecta en el centro;
- directorio alfabético con datos reales de proveedores, zonas, contactos y valoraciones;
- proximidad únicamente cuando existen coordenadas válidas;
- bloques `Así de fácil`, alta profesional y footer compacto;
- sin referencias visuales a inmuebles, enfermería o sanidad.

Implementación principal:

- `app/Http/Controllers/PageController.php`
- `resources/views/layouts/home.blade.php`
- `resources/views/page/index.blade.php`
- `resources/views/page/partials/home/`
- `public/css/page/home.css`
- `public/js/home.js`
- `public/img/home-services-hero-v2.webp`
- `tests/Feature/HomePageTest.php`

Validación local:

- URL: `http://localhost:8010/`
- Laravel `12.44.0`
- Pint, Blade cache, JavaScript y Vite build correctos
- `14` tests / `130` assertions en verde
- capturas en `screenshots/home-redesign-desktop-final.png` y `screenshots/home-redesign-mobile-final.png`

Node local es `20.18.3`; Vite recomienda `20.19+` o `22.12+`. La compilación termina correctamente, pero conviene actualizar Node antes de futuras renovaciones.

### Orquestación local de IA

Disponibles en Ollama: `deepseek-coder-v2:16b`, `mistral-nemo:latest`, `gemma3:4b`, `qwen3.5:9b` y el legacy `qwen2.5-coder:14b`.

El orquestador Laravel está configurado con DeepSeek, Mistral, Gemma y Qwen 3.5. Qwen participa como `worker-reviewer` para revisión transversal y simplificación post-integración.

Reparto recomendado:

- DeepSeek: backend, consultas y pruebas;
- Mistral: Blade, CSS, responsive y accesibilidad;
- Gemma: auditoría, nulos y regresiones;
- Qwen 3.5: segunda revisión y simplificación cuando se integre;
- agente principal: contexto, integración y validación final.

Mantener un máximo de seis rutas por worker y evitar enviar o analizar el repositorio completo en cada modelo.
