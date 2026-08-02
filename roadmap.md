# Kconecta CRM - Roadmap

## Plan activo (2026-07-31) - Refactor del home público
- Documento maestro: `HOME_REFACTOR_PLAN.md`.
- Objetivo: alinear el home con el nuevo diseño aprobado y convertirlo en la entrada pública para buscar y contactar proveedores sin registro.
- Alcance principal: hero, servicios destacados, proceso, reseñas, tres artículos recientes del blog, captación de proveedores y footer.
- Regla de blog: mostrar los tres artículos publicados más recientes por `created_at DESC`, con enlace a `/blogs`.
- Regla de proveedor: registro inicial sin dirección; gestión de ubicación y coordenadas después de verificar el correo.
- Estado: implementación y QA técnico local completados; pendiente revisión final de negocio.
- Restricción operativa: sin `commit`, `push` ni despliegue hasta aprobación expresa.

## Regla Canonica - Proveedor de Servicios
- El `Proveedor de servicios` no publica servicios individuales.
- Su funcionalidad objetivo es:
- registro validado,
- acceso autenticado al CRM,
- mantenimiento de su ficha publica,
- gestion de logo/foto, direccion, tipos de servicio, galeria, video y reputacion.
- Las referencias historicas a `/post/services`, publicacion de servicios o CRUD de servicios de proveedor deben considerarse legacy mientras se completa la transicion funcional a la `ficha de proveedor`.

## Execution Mode (2026-06-04)
- El plan activo de ejecucion del backlog pasa a ser por sprints.
- Documento maestro para implementacion incremental:
- `kconecta_backlog_roadmap.md`
- `roadmap.md` se mantiene como contexto historico, operativo y de estado de produccion.
- Cualquier nueva priorizacion funcional debe alinearse con los sprints definidos en el backlog de 90 dias.

## Status Update (2026-06-07)
- El trabajo de Sprint 1 (`Tickets e Incidencias`) avanzo solo en local y queda pausado antes de cualquier push:
- commit local creado: `f24313d` (`feat(tickets): add sprint 1 support module`)
- validacion local completada para `TicketTest`
- sin despliegue productivo ni autodeploy disparado
- La revision UX/validacion de formularios de propiedades se reanudo con enfoque uno a uno por tipo:
- `Casa o chalet` recibio una primera pasada local de endurecimiento de validaciones en frontend/backend
- el resto de tipos queda pendiente de continuar en futuras sesiones
- Cambio de foco operativo:
- prioridad inmediata pasa a ser la **subida de la app de ficha de proveedor al App Store de Apple**
- backlog por sprints sigue vigente, pero queda temporalmente en pausa mientras se atiende el release movil iOS

## Status Update (2026-05-12)
- Ratings/client-final release deployed to production from `main` (`07c3aae`).
- Backup pre-deploy executed and validated in VPS (`/root/kconecta_backups/*_pre_ratings_release/db_production.sql.gz`).
- Autodeploy did not rotate app container immediately; manual Dokploy redeploy completed.
- Production health checks validated:
- `/` -> `200`
- `/register` -> `200`
- `/post/services` -> `302` guest redirect to `/login` (expected)
- Data parity fix applied in production:
- `user_level.id=6` (`Cliente final`) restored in DB catalog.
- Result:
- online register now shows `Cliente final`, enabling the rating flow entrypoint for final clients.

## Status Update (2026-05-07)
- Local stability pass completed for provider/services discovery map:
- service markers now resolve from `service_address` with fallback to `user_address`.
- provider profile save flow now preserves previous geolocation when address is unchanged.
- public services search CTA normalized to generic `Buscar`.
- detail page refactor isolated into dedicated commit:
- `details.blade.php` split into partials for maintainability (`more_data`, `share_login_modal`).
- All changes remain local at this stage (no host push yet).

## Status Update (2026-05-06)
- Bloque de valoraciones de proveedores implementado en local con flujo completo:
- codigos de trabajo para proveedores
- rating por cliente final verificado (solo estrellas, sin texto)
- resumen de rating por proveedor con promedio, conteo y `my_stars`
- UI lista en:
- detalle publico de servicio (`details_service`)
- backoffice proveedor (`/post/services`, seccion `Codigos de trabajo`)
- dashboard de cliente final (`/home`) con experiencia enfocada en valorar servicios (entrada por codigo + estrellas)
- Registro refinado para `Cliente final`:
- `Nombre` o `Razon social` obligatorios (al menos uno)
- `Apellido`, telefono fijo, WhatsApp y direccion como opcionales para este perfil
- Validacion local completada:
- `ServiceRatingsApiTest` PASS
- `RegistrationTest` PASS

## Status Update (2026-05-05)
- Nuevo plan definido: valoraciones de proveedores con estrellas (1-5), sin texto, habilitadas solo para `Cliente final` con email verificado y codigo de trabajo emitido por proveedor.
- Restricciones cerradas:
- 1 valoracion por cliente/proveedor (editable).
- elegibilidad obligatoria por codigo valido/no usado.
- sin impacto funcional en roles actuales de proveedor/agente/admin.

## Status Update (2026-04-29)
- Calculadora catastral operativa en produccion (`kconecta.com`) con flujo completo:
- validacion de direccion por Google en home
- calculo base por `postal_code` + `m2`
- navegacion a tasacion avanzada con resultado visible
- Backend/API desplegado y verificado:
- `GET /api/cadastral/estimate`
- `POST /api/cadastral/advanced-estimate`
- Migracion `cadastral_prices` aplicada en produccion con registro manual en tabla `migrations` legacy.
- Import de dataset catastral ejecutado en produccion desde CSV (`precios_m2_catalunya_detallado.csv`).
- Resultado: error de conexion eliminado; ahora la UI muestra calculo o mensaje controlado de datos insuficientes.

## Status Update (2026-04-27)
- Provider first-stage business rules (JM) applied in CRM flows:
- provider signup no longer asks for document type/number
- provider address is now optional in registration/profile update for this stage
- legacy provider service publish flow no longer blocks on missing validated address
- Online smoke result reported:
- profile edit OK
- legacy provider services flow OK
- Remaining closure for this stage:
- Gala and JM to run online business validation

## Baseline (2026-04-21)
- Repository conectado y desplegado en Dokploy.
- Produccion operativa con autodeploy sobre `main`.
- Home, login, panel y listados principales verificados.
- Flujo de direccion de propiedades migrado a `Places API (New)` y funcionando en produccion.
- Flujos de alta por tipo de propiedad validados online.
- Edicion de propiedades validada online con reemplazo de multimedia.
- Flujo de `Garaje` validado online en alta y edicion para venta y alquiler.
- Gala valido online el flujo de `Garaje` sin incidencias.
- Flujo de `Terreno` validado localmente en alta para venta y alquiler.
- Layout de edicion de `Terreno` alineado con el fix aplicado antes en `Garaje`.
- Vistas protegidas ante propiedades sin portada.
- Backup productivo previo a cambios creado y validado en host.
- Persistencia de media corregida en Dokploy con volumenes para imagenes y videos.
- Restauracion desde backup validada.
- Media historica y nueva persistieron correctamente tras redeploy.
- Seleccion multiple de imagenes en galeria alineada en formularios web de edicion de propiedades.
- Validacion local completada para `Piso` y `Local o nave`.
- Validacion online completada tras deploy para el fix de seleccion multiple en galeria.
- `Terreno` ya separa `Tipo de terreno` y `Uso` en modelo, formularios, API y detalle publico.
- Produccion ya tiene:
- tabla `terrain_use`
- columna `property.terrain_use_id`
- `Urbanizable` agregado a `type_of_terrain`
- Backup previo al cambio de `Terreno` creado en:
- `/root/kconecta_backups/20260420_2313_pre_terreno`
- Procedimiento real de backup validado contra el contenedor MySQL:
- `kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3`
- Procedimiento real de migracion/cache clear validado contra el contenedor app:
- `kconecta-kconectacrm-5oikfs.1.8j4e7feeo9l3yxw5hap9vhw8k`
- `Terreno` ya soporta `Tipo de calificación` en formularios web, API y detalle publico.
- Produccion ya tiene:
- tabla `terrain_qualification`
- tabla pivot `terrain_qualifications`
- validacion online final confirmada en detalle publico de terreno (con `Tipo de calificación` visible)
- `Terreno` ya soporta visualizacion condicional de campos de superficie por `Tipo de terreno` en formularios web:
- para `Urbano` y `Urbanizable` muestra `Superficie edificable` y `Superficie minima vende/alquila`
- para otros tipos los oculta y limpia en frontend
- Proveedores/servicios (`2026-04-22`):
- formulario de alta de servicios para proveedor ya no duplica datos de usuario
- direccion de servicio se toma desde perfil validado del proveedor
- perfil del proveedor:
- foto/logo se recorta a `350x350` y se guarda en WebP
- `username` queda bloqueado (solo lectura)
- landing de proveedor en `/post/services`:
- slider multi-imagen funcional con controles y dots
- boton de ver publicacion en listado abre en `_blank`
- boton `Ver video` retirado de la cabecera segun decision UX (video queda en bloque inferior)
- detalle publico de servicio:
- fix de carga de video por `service_id`
- mapas de resultados:
- icono legacy reemplazado por icono Kconecta
- Produccion (`2026-04-22`):
- verificacion de email estabilizada con redireccion directa por rol tras confirmacion
- persistencia de media de logos completada con volumen:
- `/var/www/html/public/img/photo_profile`

## Phase 1 - Stabilize Production
- Investigar drift entre referencias en BD y archivos fisicos de media.
- Revisar si en una fase posterior conviene limpiar definitivamente los valores legacy de `type_of_terrain` (`Servicios`, `Industrial`, `Afectado`) tras verificar que ningun flujo dependa de ellos.
- Corregir UX de subida de video:
- mensaje real de limite
- validacion previa
- futura compresion frontend
- desmontar el flujo legacy de alta de servicios y sustituirlo por mantenimiento de ficha de proveedor.
- Ejecutar pasada final de normalizacion UTF-8 en vistas legacy con mojibake.

## Phase 2 - Security Hardening
- Rotar credenciales productivas y secrets de aplicacion.
- Eliminar fallback legacy de password en texto plano.
- Forzar actualizacion de passwords por defecto o importados.

## Phase 3 - Data Governance
- Definir fuente de verdad para seed de datos (`seeders` vs snapshots SQL).
- Evitar sobrescrituras local -> produccion sin aprobacion explicita.
- Formalizar procedimiento de backup y restore drill para DB y volumenes de media.
- Documentar y, si es viable, corregir la incompatibilidad de la tabla legacy `migrations` con el registrador estandar de Laravel.

## Phase 4 - Web/Mobile Parity
- Igualar formularios de propiedades por tipo entre CRM web y app movil.
- Mantener alineados los formularios web de alta y edicion para que no reaparezcan drift como el de galeria multiple.
- Mantener pipeline de imagenes WebP compatible para web y movil.
- Definir estrategia de video compatible para web y movil.
- Revisar si el contrato del API movil cubre todos los campos legacy del CRM.

## Closed In This Round
- `Terreno` quedo alineado en datos, formularios y detalle publico.
- `Admin -> Usuario detalle` estabilizado:
- fix de `500` en `/users/{id}`.
- desglose de servicios de proveedor validado con tags no clicables.
- recorrido online final validado como correcto.
- `Backoffice -> Mis propiedades` refinado visualmente:
- filtros en dos filas segun referencia objetivo
- placeholders y labels alineados
- acciones `Filtrar/Limpiar` y responsive ajustados
- Proveedor (fase 1 reglas JM):
- registro proveedor adaptado en UI para no solicitar documento en alta
- edicion de perfil proveedor sin bloqueo por direccion
- guardado legacy de servicios proveedor sin bloqueo por direccion faltante

## Phase 5 - Operational Reliability
- Mejorar health checks y observabilidad de app + DB.
- Vigilar drift entre migraciones del repo y runtime productivo.
- Documentar incident response y rollback operativo.

- `Detalle publico de propiedades` pulido visual/funcional:
- cards vacias eliminadas por render condicional estricto.
- metadatos superiores con `N/A` para datos faltantes.
- jerarquia de cards ajustada (Fianza/Estado junto a M2 construidos).

## Context update (2026-05-14)
- API v1 proveedores para app movil cerrada end-to-end (etapas 1-5).
- Suite final API ejecutada en Docker: `tests/Feature/Api` en verde.
- Estado operativo actual: en espera de nuevas instrucciones de JM y Gala para continuar roadmap movil.

## Context update (2026-05-18)
- Auditoria de readiness para app nativa completada.
- Gaps tecnicos de API cerrados en entorno local Docker:
- contrato JSON v1 reforzado en auth/ratings/agent-properties.
- endpoint de catalogo `service_type` agregado para app movil.
- endpoint legacy inseguro `delete_more_image` retirado (410).
- fallback legacy de password en texto plano removido del login API.
- suite `tests/Feature/Api` validada en verde.

## Context update (2026-05-21)
- Mobile profile/logo parity completed in backend:
- canonical read field added for provider profile: `provider_logo_url` (+ `provider_logo_path`).
- canonical multipart write enabled: `provider_logo` in `PATCH /api/agent/services/profile`.
- legacy upload aliases supported for compatibility window:
- `logo`, `photo`, `avatar`, `image`, `company_logo`.
- validation enforced for provider logo uploads:
- `jpg|jpeg|png|webp`, max `2048KB`, with `422` contract errors.
- coherence update:
- `GET /api/me` now also exposes `provider_logo_url` and `provider_logo_path`.
- release status:
- deployed via `main` commit `87941f2`; mobile team confirmed logo now renders.

## Sub-plan: Backend Calculador Catastral (Precios M2)
*Plan de implementación asíncrona para la importación y cálculo de precios por metro cuadrado.*

- [x] **1. Migration (`cadastral_prices`)**
  - Campos: `id`, `province`, `municipality`, `neighborhood`, `postal_code` (string 10), `price_m2_eur` (decimal 10,2), `import_batch_id` (trazabilidad e histórico de cargas), `created_at`, `updated_at`.
  - Índices simples: `postal_code`, `municipality`.
  - Índice compuesto: `[postal_code, municipality]`.
  - Restricción única (evita duplicados): `unique(postal_code, municipality, neighborhood)`.
- [x] **2. Model (`CadastralPrice`)**
  - Definir guardeds/fillables.
  - Setup para soporte de consultas agregadas.
- [x] **3. Artisan Command (`cadastral:import {path}`)**
  - Lectura por streaming usando `fgetcsv` para máxima eficiencia de RAM.
  - Validaciones: trim de strings, normalización UTF-8, `postal_code` obligatorio, `price_m2_eur` numérico positivo.
  - Gestión de rechazos: logging de filas inválidas.
  - Persistencia vía `upsert`: insertar si la clave no existe; si el unique key existe, actualizar `price_m2_eur`.
  - Trazabilidad de carga: guardar y emitir el total de filas procesadas/inválidas y el `import_batch_id`.
- [x] **4. Servicio de Consulta (`CadastralCalculationService`)**
  - Entrada: `postal_code`.
  - Salida: estructura con `avg_price_m2`, `min`, `max`, `count` para cálculo aproximado rápido y robusto.
- [ ] **5. Testing (PHPUnit/Pest)**
  - Test: importación inicial correcta.
  - Test: upsert (re-importación de datos no debe duplicar, sino actualizar precio).
  - Test: consulta con `postal_code` existente devuelve valores estadísticos.
  - Test: consulta con `postal_code` inexistente controla respuesta.
## Cadastral Production Closure (2026-04-29)
- Backend calculadora catastral operativo en produccion.
- Tabla `cadastral_prices` creada en produccion.
- Registro manual en tabla legacy `migrations` aplicado para la migracion `2026_04_28_000000_create_cadastral_prices_table`.
- CSV `precios_m2_catalunya_detallado.csv` importado en produccion.
- Flujo online validado: home -> calculo base -> tasacion avanzada con valor estimado.
- Pendiente recomendado: ejecutar backup post-deploy y guardar ruta en `tasks.md`.

## Sub-plan: Valoraciones de Proveedores (Estrellas + Codigo de Trabajo)
- Modelo de usuario:
- agregar nivel `Cliente final` y habilitar su registro local con verificacion email.
- Datos:
- tabla `service_provider_ratings` con `provider_user_id`, `client_user_id`, `stars`, timestamps y `unique(provider_user_id, client_user_id)`.
- tabla `service_work_codes` con `provider_user_id`, `code`, `is_used`, `used_by_user_id`, `used_at`, timestamps.
- Integridad:
- `stars` solo 1..5 enteras.
- cliente y proveedor no pueden ser el mismo usuario.
- Flujo/API:
- `POST /api/service-ratings/work-codes` para generar codigo (proveedor).
- `POST /api/service-ratings` para votar con `{ provider_user_id, work_code, stars }` (cliente final verificado).
- `GET /api/service-ratings/provider/{provider_user_id}` para resumen `{ average_stars, ratings_count, my_stars? }`.
- UI:
- bloque de rating en `details_service` (promedio, votos, estrellas visuales).
- formulario visible solo para cliente final verificado.
- seccion de codigos en backoffice de proveedor.
- Quality gates:
- unit tests de rango, unicidad, auto-voto y ciclo de codigo.
- integration/API tests de elegibilidad, consumo de codigo, upsert de voto y resumen agregado.
- functional UI checks de proveedor (generar codigo) y cliente (votar).




## Status Update (2026-05-18) - Production API Mobile Readiness Closed
- Local hardening/api-contract commits were pushed to `main` and deployed in Dokploy.
- Release commits in production path:
- `e23ebae` (mixed throttle policy + API email verification JSON 403)
- `15d6703` (ratings/work-codes contract unification + CORS config)
- Laravel runtime cache refreshed in production container:
- `php artisan optimize:clear`
- `php artisan config:cache`
- `php artisan route:cache`
- Production verification passed:
- `GET /api/me` -> `401` with v1 JSON contract.
- `GET /api/properties?text=mad` -> `200` with v1 contract and legacy compatibility keys.
- `OPTIONS /api/properties` -> `204` preflight OK.
- CORS now constrained by env var and validated online:
- `CORS_ALLOWED_ORIGINS=https://kconecta.com,https://www.kconecta.com`
- preflight header returns `Access-Control-Allow-Origin: https://kconecta.com`.

## Status Update (2026-05-22) - Service metrics live + migration safety protocol
- Production validated online:
- provider dashboard metrics now move with real usage in service profile/public page.
- `Visitas al perfil`: increments on public service detail visit.
- `Clicks en contacto`: increments on WhatsApp contact click.
- `Tickets de servicio`: sourced from used `service_work_codes`.
- Release deployed from `main`: commit `355e49f`.
- Runtime production issue handled:
- legacy `migrations` table shape blocked Laravel registration (`version` required, no default).
- Recovery path executed: manual migration row reconciliation + missing table creation for `service_contact_clicks`.

## Operational Guardrail (2026-05-22) - Newline-safe migrations in VPS
- Before each migration run:
- refresh `APP_CTN` and `DB_CTN` dynamically from `docker ps`.
- print variables with `printf '%s\n'` to avoid hidden CR/LF.
- use HEREDOC for SQL inserts into legacy `migrations` table.
- avoid very long one-line SQL strings with many escaped quotes.
- After migration run:
- enforce `php artisan migrate --force` => `Nothing to migrate`.
- verify target tables exist and online flow increments KPIs.

## Status Update (2026-05-22) - Mobile dashboard KPI parity completed
- CRM backend and native app are now aligned for provider service KPIs.
- Deployed commit: `d7fa1ed` on `main`.
- API profile endpoint (`GET /api/agent/services/profile`) now exposes provider KPI aggregates and monthly deltas.
- Compatibility aliases included to minimize mobile integration risk.
- Tracking endpoints (`register_visit`, `register_contact_click`) confirmed operational.
- Production validation completed end-to-end:
- migrations present and applied (`2026_05_22_*` in `Ran` state).
- provider dashboard in native app displays live values (visits/clicks/tickets).

## Status Update (2026-05-22) - Store release blockers closed (account + legal)
- Backend compliance for app marketplaces implemented in CRM:
- password recovery API endpoints ready for native app integration.
- in-app initiated account deletion API ready (auth + password confirmation).
- legal public HTML pages online-ready paths defined:
  - `/legal/privacy`
  - `/legal/terms`
  - `/legal/account-deletion`
- Security controls included:
- anti-account-enumeration response for forgot-password.
- token revocation and account anonymization on deletion.
- Validation completed locally in Docker with API test suite fully green.

## Status Update (2026-05-22) - Compliance blocker hardened for store review
- Release-compliance implementation upgraded with security and maintainability controls.
- Added account deletion audit trail persistence (`account_deletion_audits`).
- Tightened rate limit for account deletion operations.
- Removed sensitive hardcoding by parameterizing legal and deletion-domain settings in `config/legal.php`.
- Legal pages refactored to reusable layout and route names with canonical permanent redirect from legacy URL.
- Regression/API validation remained green after hardening (`51` tests, `369` assertions).

## Status Update (2026-05-22) - Store compliance release deployed and production-ready
- Backend and legal web compliance block for App Store / Play Store is now deployed.
- Commit deployed: `c3caab1`.
- VPS migration for `account_deletion_audits` completed with legacy migration-table reconciliation.
- Production migration state is stable (`Nothing to migrate`, migration marked `Ran`).
- Native app team received integration contract for:
- forgot-password flow
- reset-password flow
- in-app account deletion
- legal links exposure

## Status Update (2026-07-12) - Provider import release incident and recovery
- CRM admin provider import is now deployed with preview/confirm flow.
- Production incident sequence after rollout:
- `404` on `/users/providers/import/preview`.
- missing class `App\Services\ProviderServiceTypeService` during import analysis.
- imported providers not visible on public map.
- `500` on `/result_provider/{id}` from map popup `Ver detalle`.
- Root cause summary:
- yes, hubo divergencia, pero en dos capas:
- divergencia de codigo/despliegue entre lo validado en local y la imagen realmente activa en produccion.
- divergencia de esquema legacy en produccion (`migrations.version` obligatorio, sin default), lo que rompia el registro automatico de migraciones aunque algunas se ejecutaran fisicamente.
- Recovery path completed:
- se empujaron y desplegaron las rutas/controladores/servicios faltantes del importador.
- se reconcilio manualmente el estado de migraciones necesarias en produccion.
- la discovery publica de proveedores se desacoplo de `service` y paso a resolver desde `user`, `user_address` y `provider_services`.
- la ficha `/result_provider/{id}` quedo endurecida para convivir con tablas multimedia legacy sin columna `user_id`.
- Outcome:
- importador funcional.
- proveedores visibles en mapa.
- detalle publico funcional para proveedores importados.

## Status Update (2026-05-29) - Final client ratings dashboard API parity
- Mobile parity backend gap closed for final client ratings dashboard hydration.
- New authenticated endpoint available: GET /api/service-ratings/my-dashboard.
- Endpoint returns metrics and recent activity from server-side source (service_provider_ratings):
- atingsCount, providersRatedCount, verageStars, ecentRatings.
- Security/role constraints aligned with existing ratings APIs:
- 401 UNAUTHENTICATED without token.
- 403 ROLE_NOT_ALLOWED for non-final-client roles.
- Regression coverage extended in ServiceRatingsApiTest; suite is green after changes.

## Status Update (2026-07-26) - Public services home local milestone

- Nuevo home público de servicios y profesionales implementado en local.
- Usa el modelo canónico: proveedor, ficha única, `provider_services`, dirección y valoraciones reales.
- Narrativa inmobiliaria eliminada del home sin borrar capacidades internas.
- Búsqueda por servicio/zona, directorio alfabético, proximidad con coordenadas y fallback seguro.
- Responsive, accesibilidad, build y pruebas en verde (`14` tests / `130` assertions).
- Estado: sin commit, push ni despliegue.

### Continuación inmediata

1. Revisión visual/negocio.
2. QA manual de geolocalización y contactos.
3. Ajustes acordados.
4. Regresión y capturas.
5. Decisión de release y autorización antes de push.

### Ejecución orquestada

- DeepSeek: backend/data/tests.
- Mistral: frontend/Blade/CSS/responsive.
- Gemma: regresiones y reglas de negocio.
- Qwen 3.5: cross-review y simplificación.
- Agente principal: planificación, contexto, integración y verificación.

`qwen3.5:9b` está instalado, pero su integración en el orquestador Laravel sigue pendiente. No debe considerarse worker activo hasta configurar y probarlo. Máximo seis rutas relevantes por worker.

## Status Update (2026-08-01) - Public search continuity confirmed

- The public provider discovery flow from the July 16 reference remains present and operational.
- `/result/services` renders the public list/map experience without requiring authentication.
- `/api/services_for_map` supplies provider locations; local verification returned `207` records.
- Leaflet + OpenStreetMap remains the resilient map fallback and does not require a Google API key.
- The refreshed home continues to target the existing public results route rather than creating a parallel search implementation.
- A dedicated browser Google Maps key now exists and is configured only in the local CRM environment.
- Mobile and web keys must remain separate: Android restrictions for the native key, HTTP-referrer restrictions for the CRM browser key.
- Focused regression status: `13` tests, `152` assertions, all passing.
- Browser QA resolves `08029 Barcelona` to valid coordinates and submits address plus service to `/result/services`.
- Production still uses the former Android-restricted key; no production environment change has been made.
- Current local data exposed a pending behavior gap: literal address matching can return zero providers even with valid selected coordinates.

### Next milestone

1. Create a local backup and import a fresh production database dump.
2. Reproduce address/service searches against production-parity provider coordinates.
3. Open results in map mode and replace literal address matching with coordinate-aware discovery.
4. Validate explicit geolocation, Google map rendering and forced Leaflet fallback.
5. Repeat regression tests and browser QA before requesting authorization to push.

## Status Update (2026-08-01) - Geography sanitation validated locally

- A fresh production snapshot is running locally; production remains untouched.
- All 2,895 geolocated providers now have canonical province/state/country values.
- Eight providers without coordinates remain intentionally incomplete and non-geographic.
- The sanitation command is deterministic, transactional and fails closed for unknown geolocated cities.
- The sanitized dump was restored successfully into an isolated verification database.
- Google-selected structured locations now return the existing provider map results instead of being rejected by literal address matching.
- Home search opens map mode and the `08029 Barcelona` QA case returns 215 matching providers for the selected specialty.
- Importer hardened: country suffixes are no longer interpreted as provinces and unresolved geolocated rows fail closed.
- Complete regression passes: 142 tests / 876 assertions.
- Next: prepare a production dry run backed by a new production backup, only after explicit authorization.
- Production must not be replaced with the current local dump; no push or deployment is authorized.

