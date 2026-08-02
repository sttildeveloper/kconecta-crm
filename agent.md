# AGENT.md - Kconecta CRM

## Canonical Business Rule - Service Providers
- Regla vigente y prioritaria:
- el `Proveedor de servicios` no publica servicios individuales.
- El proveedor solamente:
- crea su cuenta tras registro validado,
- accede al CRM autenticado,
- completa y mantiene su ficha publica,
- y gestiona su propia informacion:
- logo/foto,
- direccion,
- tipos de servicio que ofrece,
- galeria de imagenes,
- video,
- metricas y valoraciones.
- Toda referencia legacy en este archivo o en el repo a `publicar servicios`, `createService()`, `/post/services` o CRUD de servicios de proveedor debe interpretarse como deuda tecnica/documental pendiente de sustitucion por el modelo de `ficha de proveedor`.

## Goal
Operate and evolve `kconecta-crm` with focus on:
- stable local Docker workflow,
- sync with GitHub,
- deployment in Dokploy (Hostinger),
- security hardening,
- parity between web CRM and future mobile flows.

## Current Repo Context
- Active GitHub repo: `https://github.com/sttildeveloper/kconecta-crm`
- Active remote: `origin`
- Main branch: `main`
- Latest deployed commit: `6a082b3` (`fix(cadastral): harden API errors and guard migration for existing table`)
- Last operational update: `2026-04-21` (post pushes `703ae94`, `105c0b3`, `9cd087e`)
- Context checkpoint updated: `2026-04-27` (provider registration/profile/service flows aligned to JM first-stage rules)
- Context checkpoint updated: `2026-04-29` (cadastral calculator backend + production import validated online)
- Context checkpoint updated: `2026-05-06` (ratings with work-codes implemented locally + client registration rules refined)
- Context checkpoint updated: `2026-05-07` (services map/local search fixes + details view split into partials, local validated)
- Context checkpoint updated: `2026-05-12` (ratings release deployed in production + final client registration visibility restored)
- Context checkpoint updated: `2026-05-21` (canonical provider logo API deployed for mobile profile parity)
- Context checkpoint updated: `2026-06-07` (Sprint 1 tickets local-only + property form validation work paused + Apple App Store provider app release becomes active focus)
- Context checkpoint updated: `2026-07-08` (provider specialties refactor completed locally with canonical pivot model)

## Context checkpoint updated: 2026-07-08 (provider specialties canonical pivot)
- Provider ↔ service-type relation refactor completed locally.
- Canonical data model now is:
- catalog table `service_type`
- pivot table `provider_services`
- `provider_services` fields:
- `provider_id`
- `service_type_id`
- timestamps
- Local legacy relation table `service_types` was removed from active use for provider specialties and dropped from local DB after backfill.
- Canonical naming adopted in code paths touched by this refactor:
- `specialties`
- `specialty_ids`
- Backward-compatible API aliases still remain in selected responses:
- `service_types`
- `service_type_ids`
- Scope migrated to canonical source:
- provider dashboard read/write of specialties
- public/internal provider detail reads
- public discovery/detail payload normalization
- Local validation status:
- feature tests covering provider/public discovery/admin catalog flows passed after refactor (`38` tests, `341` assertions in the targeted suite).
- Important operational note:
- the admin CRUD at `/admin/service-types` continues to manage only the catalog `service_type`; it is no longer the provider relation store.

## Context checkpoint updated: 2026-06-07 (tickets local, property-form QA, Apple focus)
- Sprint execution mode remains active with `kconecta_backlog_roadmap.md` as master implementation plan.
- Sprint 1 `Tickets e Incidencias` progressed locally:
- new local-only commit created: `f24313d` - `feat(tickets): add sprint 1 support module`
- module status:
- DB tables `tickets` and `ticket_messages` reconciled in local legacy migration flow
- `TicketTest` passing locally after hardening (`7` tests / `37` assertions)
- commit intentionally **not pushed** because `push` triggers autodeploy
- Public property detail CSS overflow fix was applied locally for long text cards in:
- `resources/views/page/details.blade.php`
- `resources/views/page/partials/details/more_data.blade.php`
- `public/css/page/details.css`
- Property form validation review started one-by-one by property type.
- `Casa o chalet` (`resources/views/post/forms/form_1.blade.php`) now has local-only first-pass validation hardening:
- frontend summary block for invalid/missing required fields
- additional dynamic required handling for key sale/rent fields
- backend validation guard added in `PostController` for create/update of type `1`
- local technical validation completed:
- Blade cache rebuild OK
- app runtime OK (`php artisan about`)
- no production push performed
- Strategic focus changed at session close:
- next major objective is **prepare and ship the provider profile app to Apple App Store**
- web/property validation work is paused after the first `Casa o chalet` pass and should be resumed later property-type by property-type

## Session Update (2026-05-12)
- Production deploy completed from `main` at commit `07c3aae`.
- Pre-release production backup created and validated (`db_production.sql.gz`) under `/root/kconecta_backups/*_pre_ratings_release`.
- Dokploy manual redeploy executed after autodeploy did not refresh app container.
- Post-deploy health checks validated:
- `GET /` -> `200`
- `GET /register` -> `200`
- `GET /post/services` -> `302` to `/login` (expected for guest user, legacy route pending replacement by provider-profile management)
- Production runtime validated with new details partials present:
- `resources/views/page/partials/details/more_data.blade.php`
- `resources/views/page/partials/details/share_login_modal.blade.php`
- Operational fix applied in production data:
- `user_level.id=6` (`Cliente final`) was missing in DB catalog and was restored via `updateOrInsert`.
- Registration selector now correctly includes `Cliente final` online.

## Session Update (2026-05-07)
- Local-only updates completed (no host push):
- commit `0c95828`: services map/search fixes + provider geolocation persistence guard + generic search CTA + GET logout fallback.
- commit `07c3aae`: refactor of `page/details` into partials (`more_data`, `share_login_modal`).
- Result:
- provider/services search map now resolves markers from `service_address` or `user_address`.
- provider profile update no longer drops existing `lat/lng` when address is unchanged and no new Google place is selected.

## Working Rules
- Prefer minimal, testable changes.
- Do not hardcode secrets.
- Keep `.env` out of remote history.
- Record infra, deploy progress, and validation results in `tasks.md`.
- Validate critical flow locally before remote deploy:
- container up
- DB connection
- login
- key form flow affected by the change
- For production updates: use `commit -> push -> autodeploy -> verify`.
- Use manual redeploy only if health checks or critical endpoints fail after push.
- Create annotated release tags on important `main` milestones following `VERSIONING.md`.

## Local Runtime Baseline
- App URL: `http://localhost:8010`
- Containers:
- `kconecta`
- `kconecta-mysql-1`
- DB schema: `kconecta_schema`
- Local office workspace: `C:\MeegDev\kconecta-crm\web`
- Local backup workspace: `C:\MeegDev\kconecta-crm\web\backups`

## Production Runtime Baseline
- Platform: Dokploy on Hostinger VPS
- App URL: `https://kconecta.com/`
- App service pattern: `kconecta-kconectacrm-*`
- DB service pattern: `kconecta-crm-*`
- Current production app container used on `2026-04-20/21`:
- `kconecta-kconectacrm-5oikfs.1.8j4e7feeo9l3yxw5hap9vhw8k`
- Current production app container validated after terrain-qualification deploy on `2026-04-21`:
- `kconecta-kconectacrm-5oikfs.1.r7nuo2pf6d5y46mu7ij1t5nrw`
- Current production MySQL container used on `2026-04-20/21`:
- `kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3`
- DB schema: `kconecta-mysql`
- Deploy mode: automatic redeploy on `push` to `main`
- Release tags published:
- `v0.1.0`
- `v0.1.1`
- Production env includes `GOOGLE_MAPS_API_KEY`
- Google Cloud project `kconectacrm` currently requires these APIs enabled:
- `Maps JavaScript API`
- `Places API`
- `Places API (New)`
- `Geocoding API`

## Access Notes
- Hostinger access validated from this office workstation.
- Dokploy access validated from this office workstation.
- Host-level troubleshooting can be done reliably through Hostinger browser terminal.
- Direct SSH from this workstation to the VPS is not currently reliable; prefer Hostinger terminal when production log inspection is needed.

## Recent Operations (2026-04-01 to 2026-04-15)
- Office workstation autonomy restored for day-to-day operations:
- Hostinger access validated
- Dokploy access validated
- Production data imported into local Docker DB for parity testing
- Local login validated using production credentials
- Deploy workflow and versioning policy documented:
- `commit -> push -> autodeploy -> verify`
- Apache startup warning fixed in production:
- `AH00558` removed by setting global `ServerName`
- Backoffice navigation label changed:
- `Dashboard` -> `Escritorio`
- Property create flow no longer stops at a stub redirect:
- `POST /post/create` now creates the base property and reuses update flow
- Backend validation now requires:
- non-empty property address
- numeric latitude
- numeric longitude
- Shared Google Maps address JS migrated away from legacy autocomplete to a `Places API (New)` compatible flow
- Production Google Cloud config updated and verified so address suggestions render again in property create forms
- Property registration now:
- converts uploaded non-WebP images to `.webp` before persistence
- handles Google address payload safely
- ignores non-numeric placeholder values for integer `_id` fields
- Editing flow now:
- deletes old cover/video files when replaced
- defers deletion of extra images until form submit
- Missing cover image fallback added to edit/detail/listing views to avoid `500`
- Euro symbol rendering fixed in property lists
- Manual and controlled production validations completed for:
- home
- login
- authenticated panel access
- property create route with live address suggestions
- all property types registration:
- `Casa o chalet`
- `Piso`
- `Local o nave`
- `Garaje`
- `Terreno`
- `Casa rustica`
- property editing with multimedia replacement
- Additional validation completed on `2026-04-15`:
- `Garaje` create flow validated online for both sale and rent
- `Garaje` edit flow validated online for both sale and rent
- Gala performed online operator testing on `Garaje` and confirmed the flow works correctly
- `Terreno` create flow validated locally for both sale and rent
- `Terreno` edit layout adjusted to match the fixed `Garaje` description section
- Additional validation completed on `2026-04-18`:
- multiple gallery image selection in property edit forms was enabled for the remaining property types
- `Piso` edit flow validated locally with multiple gallery image selection
- `Local o nave` edit flow validated locally with multiple gallery image selection
- production deploy completed from `main`
- online validation after deploy confirmed the multiple gallery image selection fix works satisfactorily
- Additional implementation and validation completed on `2026-04-20/21`:
- production backup created at `/root/kconecta_backups/20260420_2313_pre_terreno`
- production snapshots captured for `type_of_terrain` and current `Terreno` records
- `Terreno` was refactored to separate `Tipo de terreno` from `Uso`
- new data model introduced:
- table `terrain_use`
- nullable column `property.terrain_use_id`
- `Terreno` web create/edit forms now show:
- `Tipo de teRústico`
- `Uso`: `Servicios`, `Residencial`, `Industrial`, `Agrícola`
- public property detail now shows `Tipo de terreno` and `Uso` cards for `Terreno`
- production `500` on `/post/create_form/9` diagnosed after deploy
- root cause was pending schema change in production, not the Blade form itself
- production schema was updated and cache cleared successfully
- online tests for `Terreno` were reported as successful after schema update
- production `Terreno` records at backup time used only `type_of_terrain_id = Urbano`
- legacy values `Servicios`, `Industrial`, `Afectado` remain in `type_of_terrain` for compatibility, but are no longer exposed by the `Terreno` form
- production migration workflow required manual registration in `migrations` table after each executed migration because the table expects extra fields:
- `version`
- `class`
- `group`
- `namespace`
- `time`
- Production backup created on host at `/root/kconecta_backups/20260415_1656_pre_commit_sync`
- Post-deploy media incident diagnosed in production:
- root cause was ephemeral container storage for uploaded media
- Dokploy was updated with persistent volume mounts for:
- `/var/www/html/public/img/uploads`
- `/var/www/html/public/video/uploads`
- Historical media was restored from `/root/kconecta_backups/20260415_1656_pre_commit_sync`
- Media persistence was validated by:
- redeploy after restore
- new upload after fix
- redeploy after new upload
- Additional production hardening validated on `2026-04-22`:
- email verification flow fixed after adding missing `user.email_verified_at` in production
- post-verification redirect now sends users directly to role-specific destination
- Dokploy persistent volume added for provider profile logos:
- `/var/www/html/public/img/photo_profile`
- backup and restore drill validated for DB + media at:
- `/root/kconecta_backups/20260422_1739_pre_persist_media`

## Known Recent Incidents
- Incomplete `Piso` draft records were created on `2026-04-07` when the form submitted literal `Seleccione` into integer fields such as `emissions_rating_id` and `power_consumption_rating_id`.
- Those incomplete records were cleaned from production after diagnosis.
- Missing-cover records previously caused `500` in edit/detail views; fallback placeholder fix is already deployed.

## Current Audit Status
- Property create flows by type are considered production-validated.
- Property edit flow is considered production-validated for:
- replace cover image
- add more images
- mark existing more images for deletion
- replace video
- Garage-specific create/edit flow is production-validated for:
- sale
- rent
- operator-side online validation by Gala
- Property edit forms are now aligned to allow multiple gallery image selection in one action.
- `Terreno` create flow now includes isolated support for `terrain_use_id`.
- Property/service views with missing cover image now fall back to placeholder instead of failing.
- Production media persistence is now considered validated for both restored and newly uploaded files.

## Next Operational Focus
- Complete manual local QA cycle with business user:
- provider generates work code in the provider backoffice area (legacy implementation still referenced `/post/services`)
- final client registers and verifies email
- final client submits rating in public `result_service/{id}`
- Promote ratings module from local validated state to production plan when business gives go.
- Update context files and operational notes after major production validations.
- Complete online business validation cycle by Gala and JM on latest provider/profile/service behavior.
- Decide whether to normalize or migrate legacy `type_of_terrain` values in production later, once it is safe to remove compatibility leftovers.
- Replace legacy `createService()` assumptions with provider-profile-only flows.
- Implement planned video upload hardening:
- align frontend/backend messaging with real limits
- validate video size before upload
- compress video in frontend before submit
- Rotate exposed or weak credentials and keys.
- Remove legacy plaintext password fallback in auth flow.
- Define recurring backup and restore drill for production DB and media volumes.

## Recently Closed
- `Terreno`:
- separation of `Tipo de terreno` and `Uso`
- web create/edit support
- API support
- public detail cards
- production schema update
- production validation after fix
- operational notes and backup procedure documented
- `Terreno` (extra round `2026-04-21`):
- support for `Tipo de calificación` in create/edit and API
- public detail now renders `Tipo de calificación` (badge + detail block)
- new schema objects deployed:
- `terrain_qualification`
- `terrain_qualifications`
- new terrain feature options seeded for `id_type=9`
- online validation confirmed with published terrain detail showing qualification values
- `Terreno` (follow-up round `2026-04-21`):
- create/edit forms now conditionally show additional area fields by terrain type
- `Urbano` and `Urbanizable` enable:
- `Superficie edificable` (`plot_meters`)
- `Superficie minima vende/alquila` (`useful_meters`)
- local validation reported successful in create and edit after fixing a Blade variable scope error in `form_5_update`
- Provider/services module (round `2026-04-22`):
- provider registration and profile flow aligned to business rules:
- allowed user types for signup constrained to `Proveedor de servicios` and `Agente inmobiliario`
- CIF/DNI/NIE validation hardened in register flow
- `username` aligned with `Razon social` and locked in profile update
- provider profile photo upload now processed server-side to `350x350` WebP
- services create form for providers simplified:
- removed duplicated user-data block
- service address now resolved from provider profile (`user_address`) instead of form input
- legacy provider landing (`/post/services`) improved:
- gallery slider supports multiple images with prev/next controls and dots
- first render stabilized to avoid post-login visual glitch before full style hydration
- service detail public page fix:
- `result_service` video lookup corrected from `property_id` to `service_id`
- map markers branding update:
- result maps now use `kconecta` icon on both Google Maps and Leaflet in property/service result pages
- Provider/profile/services alignment follow-up (round `2026-04-27`):
- provider registration UI updated for first-stage launch rules:
  - document type + document number removed from provider signup form
  - WhatsApp remains required; landline remains optional
  - provider can register without validated address (saved as `null`)
- profile update flow for providers no longer blocks save when address is missing
- service publish/edit flow no longer blocks with "complete address in profile" message
- current status reported by operator:
  - profile edit online OK
  - legacy provider services flow online OK
  - Gala and JM will execute additional online validation

## Known Risks
- Context update `2026-04-23`:
- backoffice `Mis propiedades` (`/post/my_posts`) refinado en UI:
- bloque de filtros reorganizado en dos filas segun referencia de diseno
- labels/placeholders normalizados (`Titulo o referencia`, `Categoria`, `dd / mm / aaaa`)
- acciones `Filtrar` y `Limpiar` alineadas visualmente y comportamiento responsive corregido
- Context update `2026-04-23`:
- online recorrido final validado como correcto end-to-end.
- modulo admin de usuarios:
- corregido `500` en `GET /users/{id}` por conteo en columna incorrecta.
- detalle de proveedor muestra correctamente informacion visible heredada de registros legacy; objetivo funcional nuevo: ficha unica de proveedor sin publicaciones de servicio individuales.
- persistencia de media confirmada en Dokploy para `img/uploads`, `video/uploads` y `img/photo_profile`.
- Existing fallback login logic still accepts plaintext and rehashes on login.
- Google Maps address UX depends on keeping both Dokploy env and Google Cloud API enablement aligned.
- Video upload UX is still inconsistent:
- forms still say `50MB`
- frontend compression is not implemented yet
- backend/server limits can still surprise users with large uploads
- Some property records can reference image files that are missing from the current workspace or deployment media set.
- Existing production backup set is available before cleanup under `/root/kconecta_backups/20260415_1656_pre_commit_sync`.
- Backup set specific to the `Terreno` schema change is available at `/root/kconecta_backups/20260420_2313_pre_terreno`.
- Production migrations are operationally riskier than standard Laravel because the legacy `migrations` table shape forces manual registration if `php artisan migrate` only partially completes.
- Future Dokploy service changes must preserve the configured media volume mounts or the same class of incident can return.
- Legacy dumps may override expected Laravel schema if imported without review.
- Production data can drift from local if sync is repeated without controls.
- Some backoffice/service views still show legacy mojibake text (`...`) and require final UTF-8 cleanup pass.
- API compatibility aliases for provider specialties (`service_types`, `service_type_ids`) still exist in selected payloads and should only be removed when all consumers are confirmed migrated to `specialties` / `specialty_ids`.

- Context update `2026-04-23`:
- `details.blade.php` estabilizado para evitar cards vacias en detalle publico (garaje/nave y casos sin datos).
- bloque superior de metadatos ahora incluye fallback `N/A` en cards clave cuando no hay valor.
- `Fianza` y `Estado de conservacion` movidos al grid superior junto a `M2 construidos`.
- eliminada duplicidad visual de `Estado de conservacion` y `Fianza` en cards inferiores.

- Context update `2026-05-14`:
- API v1 proveedores completada por etapas con trazabilidad y commits locales por etapa.
- Pruebas finales de API en verde (`tests/Feature/Api`).
- Sin push realizado por riesgo de autodeploy; siguiente accion bloqueada hasta instrucciones de JM/Gala.

- Context update `2026-05-18`:
- Auditoria API para app movil de proveedores ejecutada y gaps cerrados en local Docker.
- Nuevo endpoint de catalogo para mobile: `GET /api/agent/service-types`.
- Contrato JSON v1 unificado en auth, ratings y modulo `agent/properties`.
- Endpoint legacy `GET /api/delete_more_image` retirado con `410`.
- Fallback legacy de password en texto plano eliminado del login API.
- Validacion final: `tests/Feature/Api` en verde.



## Context checkpoint updated: 2026-05-18 (production hardening + CORS closure)
- Local API debt-closure commits were pushed to `origin/main` and deployed to Dokploy:
- `e23ebae`
- `15d6703`
- Production container cache was refreshed (`optimize:clear`, `config:cache`, `route:cache`).
- Production API behavior validated after deploy:
- `GET /api/me` returns `401` with v1 contract payload.
- `GET /api/properties` returns `200` with v1 contract + legacy compatibility fields.
- `OPTIONS /api/properties` returns `204` with valid preflight response.
- CORS environment binding confirmed in runtime:
- `CORS_ALLOWED_ORIGINS=https://kconecta.com,https://www.kconecta.com`
- `config('cors.allowed_origins')` resolved to both domains.
- preflight confirms `Access-Control-Allow-Origin: https://kconecta.com`.

## Context checkpoint updated: 2026-05-21 (provider logo canonical fields for mobile)
- Deployed commit on `main`: `87941f2`.
- Provider profile API now exposes canonical logo fields:
- `GET /api/agent/services/profile` includes `provider_logo_url` and `provider_logo_path`.
- Provider profile update endpoint added:
- `PATCH /api/agent/services/profile` accepts multipart `provider_logo`.
- Legacy compatibility for upload aliases enabled:
- `logo`, `photo`, `avatar`, `image`, `company_logo`.
- Validation contract for provider logo uploads:
- `mimes: jpg,jpeg,png,webp` and `max:2048` with `422` error payload.
- `/api/me` now mirrors provider logo fields for cross-client consistency.
- Mobile verification status:
- team confirmed provider logo renders correctly after deploy.

## Context checkpoint updated: 2026-05-22 (service metrics online + safe migration runbook)
- Feature status: **online validated** for provider dashboard service metrics.
- Public flow validated:
- guest visit to `result_service/{id}` increments `Visitas al perfil`.
- guest click on provider WhatsApp increments `Clicks en contacto`.
- provider dashboard now shows:
- `Visitas al perfil`
- `Clicks en contacto`
- `Tickets de servicio` (from used `service_work_codes`).
- Code release:
- commit pushed/deployed: `355e49f`.
- New migrations in that release:
- `2026_05_22_090000_create_service_profile_visits_table`
- `2026_05_22_090100_create_service_contact_clicks_table`
- Production incident during migration:
- legacy `migrations` table requires extra fields (`version`, `class`, `namespace`, `group`, `time`), so Laravel insert failed after physical table creation.
- App container name rotated after deploy; stale `APP_CTN` caused false "No such container".
- `service_contact_clicks` table was missing and had to be created/reconciled manually in production.

## Safe Migration Runbook (Hostinger VPS, newline-safe)
- Always execute from single-line commands or HEREDOC blocks to avoid shell line-break corruption.
- Always re-resolve running container names immediately before migration commands.
- Prefer `printf '%s\n' "$VAR"` to detect hidden newline/CRLF in env vars.

### 1) Resolve runtime containers (fresh)
- `APP_CTN=$(docker ps --format '{{.Names}}' | grep '^kconecta-kconectacrm-5oikfs\.1\.')`
- `DB_CTN=$(docker ps --format '{{.Names}}' | grep '^kconecta-crm-b8ejyl\.1\.')`
- `printf '%s\n' "$APP_CTN"`
- `printf '%s\n' "$DB_CTN"`

### 2) Backup before any migration
- `BKP_DIR="/root/kconecta_backups/$(date +%Y%m%d_%H%M)_pre_migrate"`
- `mkdir -p "$BKP_DIR"`
- `docker exec "$DB_CTN" sh -lc 'mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > "$BKP_DIR/db_production.sql"`
- `gzip "$BKP_DIR/db_production.sql"`

### 3) Run migrate with maintenance window
- `docker exec "$APP_CTN" php artisan down`
- `docker exec "$APP_CTN" php artisan migrate --force`
- `docker exec "$APP_CTN" php artisan optimize:clear`
- `docker exec "$APP_CTN" php artisan config:cache`
- `docker exec "$APP_CTN" php artisan route:cache`
- `docker exec "$APP_CTN" php artisan up`

### 4) If `version doesn't have a default value` appears
- Cause: legacy `migrations` schema drift.
- Action: insert missing migration rows manually with all legacy columns using **HEREDOC** (not long escaped one-liners), then re-run `php artisan migrate --force`.

### 5) Post-migration checks
- Verify expected tables exist.
- Verify `php artisan migrate --force` returns `Nothing to migrate`.
- Verify app dashboard and target flow online.

## Context checkpoint updated: 2026-05-22 (mobile KPI parity closed)
- Release commit published: `d7fa1ed`.
- `GET /api/agent/services/profile` now returns provider KPI metrics used by native dashboard:
- `profile_visits`, `profile_visits_change_pct`
- `contact_clicks`, `contact_clicks_change_pct`
- `service_tickets`, `service_tickets_change_pct`
- compatibility aliases:
- `visits_count`, `contact_clicks_count`, `service_tickets_count`
- Tracking endpoints confirmed live:
- `POST /api/service_stats/register_visit`
- `POST /api/service_stats/register_contact_click`
- Local verification:
- Docker tests `ProviderServicesApiTest` PASS (22 tests, 195 assertions).
- Production verification:
- tables present: `service_profile_visits`, `service_contact_clicks`, `service_work_codes`.
- migration state stable: `Nothing to migrate` and `2026_05_22_*` marked `Ran`.
- Native app verification:
- provider dashboard now reflects real counters online (`1` visit, `1` click, `1` ticket in validation sample).

## Context checkpoint updated: 2026-05-22 (release compliance endpoints + legal pages)
- Implemented new public API auth endpoints for mobile release compliance:
- `POST /api/forgot-password` (generic response).
- `POST /api/reset-password` (Laravel password broker).
- Implemented account deletion endpoints:
- `DELETE /api/me`
- `POST /api/account/delete` (compatibility alias).
- Account deletion implementation includes:
- password confirmation.
- sanitization/anonymization of direct user PII.
- token revocation.
- account deactivation (`is_active=0` if available).
- cleanup of `user_address` rows when table exists.
- Implemented legal public HTML pages:
- `/legal/privacy`
- `/legal/terms`
- `/legal/account-deletion`
- Updated legacy route behavior:
- `/policy_and_privacy` redirects to `/legal/privacy`.
- Validation:
- new tests `AccountComplianceApiTest` and `LegalPagesTest` passing.
- full API suite in Docker: 48 tests / 358 assertions PASS.

## Context checkpoint updated: 2026-05-22 (compliance hardening complete, pending release commit)
- Orchestrated local-agent review completed and remediations applied.
- API compliance endpoints retained and hardened:
- `POST /api/forgot-password`
- `POST /api/reset-password`
- `DELETE /api/me`
- `POST /api/account/delete`
- Security improvements:
- stricter rate limit for account deletion endpoints (`5/min`).
- account deletion audit trail table added: `account_deletion_audits`.
- delete flow now records audit row when table exists.
- configurable anonymized email domain for deleted accounts via `config/legal.php`.
- Legal web improvements:
- legal pages now share a common layout and CSS.
- legal content fields are configuration-driven (`config/legal.php`).
- named canonical legal routes + permanent redirect from legacy privacy URL.
- Validation status:
- Docker API suite PASS (`51` tests, `369` assertions).
- This hardening pass is complete and ready for user-triggered commit/push.

## Context checkpoint updated: 2026-05-22 (compliance release deployed + migration reconciled)
- Compliance release hardening was published to `main` in commit `c3caab1`.
- Production migration status validated on VPS:
- migration `2026_05_22_160000_create_account_deletion_audits_table` executed physically and then registered in legacy `migrations` schema (manual reconciliation required due to `version` field constraint).
- Final production confirmation:
- `php artisan migrate --force` => `Nothing to migrate`.
- `2026_05_22_160000_create_account_deletion_audits_table` => `Ran`.
- Compliance capabilities now online:
- `POST /api/forgot-password`
- `POST /api/reset-password`
- `DELETE /api/me`
- `POST /api/account/delete`
- public legal HTML URLs:
  - `/legal/privacy`
  - `/legal/terms`
  - `/legal/account-deletion`
- Native app handoff prepared:
- implementation prompt/contract delivered for iOS/Android integration of forgot/reset/delete/legal links.

## Context checkpoint updated: 2026-07-12 (provider import + public map/detail incident resolved)
- Se implemento y desplego la importacion masiva de proveedores desde CRM admin, con preview, deteccion de duplicados y confirmacion explicita.
- Incidente observado en produccion tras el despliegue inicial:
- `POST /users/providers/import/preview` devolvia `404`.
- despues, la importacion fallo por clase faltante `App\Services\ProviderServiceTypeService`.
- una vez importados, los proveedores no aparecian en el mapa publico.
- cuando empezaron a aparecer, `GET /result_provider/{id}` devolvia `500` desde el boton `Ver detalle`.
- Causa raiz:
- hubo divergencia de codigo/despliegue: produccion estaba corriendo una imagen sin todas las rutas, acciones y servicios ya presentes en local/main para el flujo nuevo.
- hubo tambien divergencia de esquema legacy: la tabla `migrations` en produccion no seguia el shape estandar de Laravel y exigia `version`, por lo que algunas migraciones quedaban ejecutadas fisicamente pero no registradas automaticamente.
- adicionalmente, la ficha publica del proveedor asumía columnas `user_id` en `cover_image`, `video` y `more_images`, pero esa transicion de schema no estaba garantizada en produccion.
- Resolucion aplicada:
- push de rutas y acciones faltantes del importador a `main`.
- push de `ProviderServiceTypeService`, `ProviderService` y migracion `provider_services`.
- reconciliacion manual en produccion de migraciones ejecutadas fisicamente, debido al schema legacy de `migrations`.
- cambio en mapa/busqueda publica para usar proveedores + `user_address` + `provider_services`, sin depender de `service` para la visibilidad publica.
- hardening de `/result_provider/{id}` con `Schema::hasColumn(...)` para tolerar tablas multimedia legacy sin `user_id`.
- Validacion final en produccion:
- importador operativo.
- proveedores visibles en mapa.
- `Ver detalle` operativo sobre proveedores importados.
- SSH directo al VPS validado desde la workstation del usuario con clave dedicada en `$HOME/.ssh/kconecta_prod_access`.

## Context checkpoint updated: 2026-05-29 (final-client ratings dashboard API for mobile)
- Added authenticated endpoint GET /api/service-ratings/my-dashboard for user_level_id = 6 (final client).
- Access control enforced with existing API contract:
- 401 + UNAUTHENTICATED when no auth user.
- 403 + ROLE_NOT_ALLOWED when role is not final client.
- Response payload added for mobile dashboard hydration:
- atingsCount, providersRatedCount, verageStars, ecentRatings (latest 10 by updated_at desc).
- provider_name fallback chain implemented: irst_name + last_name -> user_name -> email -> Proveedor #ID.
- Feature tests expanded and passing for dashboard endpoint scenarios.

## Context checkpoint updated: 2026-07-26 (new public services home, local pause)

- Nuevo home público implementado localmente a partir del diseño aprobado por José María.
- Disponible en `http://localhost:8010/`.
- Validado en escritorio y móvil.
- Sin `commit`, `push` ni despliegue.

## Context checkpoint updated: 2026-08-01 (public provider search and map audit)

### Historical reference

- The public results screenshot reported for 2026-07-16 matches the search generation present after the 2026-07-14 commits (`e171dc1`, followed by `5c03ff7`).
- Earlier supporting fixes remain traceable in `0c95828`, `4d63631`, `22fe94a` and `656371f`.
- The historical home autocomplete-like suggestions came from `GET /api/services`, using provider addresses grouped by municipality/province.
- The historical results page also loaded Google Places through `public/js/autocomplet.js`; selecting a place populated city, province, latitude and longitude and submitted the filter form.

### Current public contract

- `GET /result/services` is public and returns the list/map result page.
- `GET /api/services_for_map` is public and returns map provider entities with coordinates.
- `GET /api/services` remains the internal location/service suggestion endpoint.
- Current local verification returned HTTP `200` for the page and API, with `207` map locations.
- The result page contains service filters, province/city exploration, list/map toggles and provider detail/contact actions.
- The map first uses Google Maps when authorized and falls back to Leaflet + OpenStreetMap when Google is unavailable or rejects the browser request.
- Therefore, public results and the fallback map are not blocked by Google key provisioning.

### Google key finding

- Runtime browser verification showed that the former CRM credential returned `PERMISSION_DENIED` because it is restricted as an Android client application.
- Production and the local CRM used that same former credential; the mobile app also references it as `EXPO_PUBLIC_GOOGLE_PLACES_API_KEY` while native Android Maps uses a different dedicated key.
- A dedicated CRM browser key was created instead of loosening either mobile key.
- Required APIs: `Maps JavaScript API`, `Places API`, `Places API (New)` and `Geocoding API`.
- Allowed local referrer: `http://localhost:8010/*`.
- Allowed production referrers: `https://kconecta.com/*` and `https://www.kconecta.com/*`.
- It is configured locally through `GOOGLE_MAPS_API_KEY`; never commit the credential.
- Production has not been changed and still uses the former credential.

### Validation checkpoint

- Docker services active: `kconecta-crm-app` and `kconecta-crm-mysql`.
- `PublicDiscoveryApiTest` and `HomePageTest`: PASS, `13` tests / `152` assertions.
- Route inspection confirms `PageController@resultAllServices` and `ApiController@dataServicesForMap`.
- Browser QA on `http://localhost:8010` returned `08029 Barcelona` from Places API (New).
- Place selection resolved `41.3888317`, `2.1425692`, city/province `Barcelona`, and submitted the selected service to `/result/services`.
- The current home still submits `mode=1`; map-first parity requires `mode=2`.
- Current local data returned zero providers for the selected postal code/service because the backend applies literal address matching before using coordinates.
- Current refactor/search work remains local. No push or deployment has been authorized.

### Resume sequence

1. Preserve the current local database and download a fresh production dump.
2. Import the dump locally following the documented safe database workflow.
3. Reproduce postcode/address and service searches against production-parity data.
4. Change the home to map-first mode and implement coordinate-aware provider discovery.
5. Validate `Usar mi ubicación`, including permission denial and reverse-geocode failure.
6. Verify filters, provider counts, markers, details, Google Maps and Leaflet fallback.
7. Repeat tests and browser QA; do not push until explicitly authorized.

## Context checkpoint updated: 2026-08-01 (provider geography sanitized locally)

- Fresh production dump imported into local `kconecta_schema`; production was not modified.
- Provider audit: 2,903 total, 2,895 with valid coordinates, 8 incomplete without coordinates.
- Initial geographic defect: 2,869 of 2,895 geolocated providers had province empty, `NULL` or set to the country.
- `providers:sanitize-addresses` and `database/data/provider_city_provinces.json` now provide deterministic dry-run/apply sanitation for 109 known cities.
- Local apply result: 2,895 normalized, 0 unresolved cities, 0 invalid provinces, 8 incomplete profiles preserved.
- Sanitized dump SHA-256: `d95e3d8b78e4ba293d5bda30b3c916635a220c4d20a7e8cf7362392072cc0e63`.
- Restore verification: 79 tables and exact provider/geolocation counts matched source and restored database.
- `/api/services_for_map` now gives precedence to Google city/province over literal selected-address text.
- Home hidden result mode changed from list (`1`) to map (`2`).
- QA search `08029 Barcelona` plus specialty returns 215 providers; previously returned 0.
- Tests green: sanitation 3/17; public discovery + home 15/161.
- Full evidence and production runbook: `docs/provider-address-sanitization.md`.
- Importer hardening completed as the final implementation step: known cities use the canonical mapping, optional `provincia`/`province` is supported, country labels are rejected and unresolved geolocated rows are blocked.
- Complete regression status: 142 tests / 876 assertions, all passing.
- Never replace production wholesale with the local dump. Create a fresh production backup and run a production dry run first.
- No push, deployment or production data mutation is authorized.
- Production dry-run completed on 2026-08-02 after a fresh verified backup.
- Valid backup path: `/root/kconecta_backups/20260802_1025_pre_provider_sanitize_dryrun`.
- Backup SHA-256: `e8a72eec0b5c14360bda89c858c32a652cc4edb651f22df23ce42f9dcaccf397`.
- The command and mapping were copied in isolation to the active app container because pushing `main` would also release four queued commits.
- Production dry-run result: 2,903 providers, 2,895 pending normalizations, 8 incomplete profiles and 0 unresolved cities.
- No `--apply`, push, autodeploy or database write was performed.
- La siguiente sesión debe continuar desde este estado y no reconstruir el home.

### Alcance

- `GET /` continúa usando `PageController@index`.
- El home ahora prepara catálogo `service_type`, proveedores reales (`user_level_id = 4`), direcciones `user_address`, especialidades `provider_services` y valoraciones `service_provider_ratings`.
- Se usan teléfono, correo, zona publicada y ficha real.
- El home excluye la presentación de enfermería, sanidad, cuidados e inmobiliaria.
- No se eliminaron módulos, datos ni rutas internas.
- Layout: `resources/views/layouts/home.blade.php`.
- Vista: `resources/views/page/index.blade.php`.
- Parciales: `resources/views/page/partials/home/`.
- CSS/JS: `public/css/page/home.css`, `public/js/home.js`.
- Hero: `public/img/home-services-hero-v2.webp` (`92 KB`).
- Capturas: `screenshots/home-redesign-desktop-final.png` y `screenshots/home-redesign-mobile-final.png`.

### Ubicación

- Directorio inicial alfabético sin distancias.
- `getCurrentPosition` solo se llama desde el clic en `Usar mi ubicación`.
- Con coordenadas se calculan distancias Haversine y se ordena por proximidad.
- Ubicación manual mediante Google Geocoder cuando está disponible.
- Rechazo/error conserva el fallback alfabético y la búsqueda manual.

### Validación

- Pint, `node --check`, Blade cache y Vite build: PASS.
- Tests: `14` / `130` assertions, PASS.
- Suites: `HomePageTest`, `ExampleTest`, `PublicDiscoveryApiTest`, `ProviderSingleServiceProfileTest`.
- Home y hero: HTTP `200`.
- HTML auditado sin inmuebles ni enfermería.

### Runtime al pausar

- Docker Desktop iniciado.
- Contenedores: `kconecta-crm-app`, `kconecta-crm-mysql`.
- Node `20.18.3`; Vite recomienda `20.19+` o `22.12+`.

### Orquestación obligatoria para continuar

- DeepSeek (`deepseek-coder-v2:16b`): backend, queries y tests.
- Mistral (`mistral-nemo:latest`): Blade, CSS, responsive y accesibilidad.
- Gemma (`gemma3:4b`): auditoría, edge cases y regresiones.
- Qwen (`qwen3.5:9b`): revisión transversal y simplificación.
- Agente principal: reducir contexto, repartir subtareas, integrar y validar.
- Qwen está registrado como cuarto worker en `config/orchestrator.php` y en el entorno local.
- Qwen actúa como `worker-reviewer` después de la integración principal.
- Mantener `max_paths_per_worker = 6`; evitar inspecciones duplicadas.

### Próxima sesión

1. Revisar `http://localhost:8010/` con JM/Gala.
2. Probar ubicación autorizada, rechazada y manual.
3. Verificar contactos, categorías y fichas sobre datos locales.
4. Aplicar ajustes acordados.
5. Repetir pruebas, build y capturas.
6. Solo con autorización expresa preparar commit; el push a `main` activa autodeploy.

## Context checkpoint updated: 2026-07-31 (new home refactor plan)

- Plan de producto e implementación documentado en `HOME_REFACTOR_PLAN.md`.
- Nuevo diseño aprobado como siguiente iteración del home público.
- Recursos disponibles en `public/img`: `hero-bg.webp` e `img-review-1.webp` a `img-review-3.webp`.
- Blog del home: solo `status = 1`, máximo tres registros, orden `created_at DESC`, `id DESC` y botón a `/blogs`.
- El alta de proveedor continúa sin dirección ni coordenadas.
- El correo debe verificarse antes de acceder a la gestión del perfil.
- La ubicación se completa posteriormente para búsquedas por cercanía y mapa.
- No presentar testimonios ficticios como historias reales sin validación de negocio.
- Estado: implementación y QA local completados; pendiente revisión final de negocio.
- Sin `commit`, `push` ni despliegue hasta autorización expresa.

## Context checkpoint updated: 2026-07-31 (home refactor implemented locally)

- Nuevo home disponible en `http://localhost:8010/`.
- Integrados `hero-bg.webp` e `img-review-1.webp` a `img-review-3.webp`.
- Home alineado con la nueva composición: hero, tres servicios, proceso, reseñas, blog, captación de proveedores y footer.
- `PageController@index` entrega los tres artículos `status = 1` más recientes por `created_at DESC`, `id DESC`.
- `/blogs` usa el mismo orden cronológico.
- Se preservó la búsqueda pública y la geolocalización solo tras acción explícita.
- Se preservó el registro de proveedor sin dirección y el bloqueo del panel hasta verificar el correo.
- Qwen 3.5 quedó integrado como `worker-reviewer` junto a DeepSeek, Mistral y Gemma.
- Validación focal: `18` tests y `152` assertions en verde.
- Blade cache, sintaxis PHP, JavaScript y HTTP `200`: OK.
- Capturas locales: `screenshots/home-refactor-20260731-desktop.png` y `screenshots/home-refactor-20260731-mobile.png`.
- Testimonios: contenido editorial local pendiente de validación por negocio antes de producción.
- Sin `commit`, `push` ni despliegue.

## ACTIVE CONTEXT - 2026-08-02 (supersedes prior local-only checkpoints)

- El home y la busqueda publica estan desplegados en produccion.
- Cualquier nota anterior que indique `sin push`, `produccion intacta`, clave solo
  local o `--apply` pendiente es historica y no describe el estado actual.
- Commit funcional desplegado: `52854d7`.
- Google Places web: clave dedicada persistida en Dokploy, referrers web y APIs
  requeridas verificados; sugerencias para `08029` responden HTTP `200`.
- Discovery: ciudad/provincia estructurada tiene prioridad; si faltan y existen
  coordenadas, lista y mapa buscan en 30 km mediante
  `ProviderLocationSearchService`.
- Historial: el mapa usa `replaceState`; el boton Atrás regresa al home.
- Produccion saneada: 2.903 proveedores, 2.895 geolocalizados normalizados, 8
  incompletos no publicables, 0 ciudades sin resolver y 0 pendientes.
- Backup previo al apply y checksum documentados en
  `docs/provider-address-sanitization.md`.
- Suite final: 146 pruebas / 1.382 aserciones.
- Pendiente: QA manual de permisos de ubicacion, fallback Leaflet, contactos y radio.
- Fuente tecnica canonica: `docs/public-provider-search.md`.

