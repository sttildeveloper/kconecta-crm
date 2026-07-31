# Kconecta CRM - Tasks

## Plan activo (2026-07-31) - Refactor del home público
- [x] Alcance funcional y técnico documentado en `HOME_REFACTOR_PLAN.md`.
- [x] Recursos `hero-bg.webp` e `img-review-1.webp` a `img-review-3.webp` migrados a `public/img`.
- [x] Incorporar los tres artículos publicados más recientes en `PageController@index`.
- [x] Refactorizar estructura, layout y navegación del home según el diseño aprobado.
- [x] Integrar hero, reseñas y grilla dinámica de consejos.
- [x] Preservar búsqueda pública sin registro y geolocalización bajo acción explícita.
- [x] Preservar registro de proveedor sin dirección y acceso al perfil solo después de verificar el correo.
- [x] Ampliar pruebas de home, blog, registro y ubicación.
- [x] Ejecutar QA local en escritorio y móvil.
- [x] Presentar capturas locales para revisión JM/Gala.
- [ ] No ejecutar `commit`, `push` ni despliegue sin autorización expresa.

## Regla Canonica - Proveedor de Servicios
- El `Proveedor de servicios` no publica servicios individuales.
- El alcance funcional vigente del proveedor es:
- registro validado,
- acceso al CRM,
- mantenimiento de ficha publica,
- logo/foto,
- direccion,
- tipos de servicio que ofrece,
- galeria,
- video,
- y valoraciones/metricas.
- Las tareas legacy que mencionan `/post/services`, `createService()` o CRUD de servicios de proveedor deben reinterpretarse como deuda de transicion hacia la `ficha de proveedor`.

## Session Update (2026-07-08) - Refactor tipos de servicio del proveedor
- [x] Modelo canonico proveedor ↔ tipos de servicio consolidado localmente.
- [x] Tabla pivote oficial creada:
- [x] `provider_services` (`provider_id`, `service_type_id`, timestamps).
- [x] Catalogo admin preservado en:
- [x] `service_type`.
- [x] Tabla legacy relacional retirada del flujo proveedor:
- [x] `service_types`.
- [x] Backfill local seguro ejecutado antes de retirar la tabla legacy.
- [x] Lectura y guardado de especialidades del proveedor movidos a `provider_services`.
- [x] Vistas y payloads internos alineados a nomenclatura canonica:
- [x] `specialties`
- [x] `specialty_ids`
- [x] Compatibilidad transitoria preservada en respuestas API seleccionadas:
- [x] `service_types`
- [x] `service_type_ids`
- [x] Regresion objetivo validada:
- [x] `ProviderServicesApiTest`
- [x] `PublicDiscoveryApiTest`
- [x] `ServiceTypeManagementTest`
- [x] `ProviderSingleServiceProfileTest`
- [x] `AdminProviderDeletionTest`
- [x] Resultado de la pasada dirigida:
- [x] `38` tests, `341` assertions.

## Mobile API Registration Release (2026-06-28)
- [x] Implementados endpoints nativos de registro para la app móvil bajo `/api/mobile/`:
  - [x] `POST /api/mobile/register-provider` (`user_level_id = 4`)
  - [x] `POST /api/mobile/register-client` (`user_level_id = 6`)
- [x] Creado `App\Services\UserRegistrationService` para reutilizar las reglas de validación y creación del registro web.
- [x] Creado `App\Http\Controllers\Api\RegisterApiController` devuelviendo tokens Sanctum y estructura JSON normalizada.
- [x] Refactorizado `RegisteredUserController` sin alterar el comportamiento web.

## Sprint Execution Mode (2026-06-04)
- [x] Decision tomada:
- [x] implementar el backlog tecnico-funcional siguiendo el plan por sprints.
- [x] Documento maestro de ejecucion:
- [x] `kconecta_backlog_roadmap.md`
- [ ] Siguiente paso operativo:
- [ ] desglosar Sprint 1 en tareas ejecutables del repo actual.
- [ ] validar alcance inicial de `Tickets e Incidencias` frente a la baseline ya desplegada.
- [ ] identificar dependencias tecnicas reutilizables del sistema actual para minimizar riesgo de integracion.

## Session Closure (2026-06-07) - Pause sprint escalation / switch to Apple release
- [x] Sprint 1 `Tickets e Incidencias` ejecutado parcialmente en local.
- [x] Commit local creado sin push:
- [x] `f24313d` - `feat(tickets): add sprint 1 support module`
- [x] Validacion local tickets:
- [x] `docker compose exec app php artisan test --filter=TicketTest` -> PASS (`7` tests, `37` assertions)
- [x] Estado deliberado:
- [x] no hacer `push` todavia para evitar autodeploy a produccion
- [x] Flujo local de migraciones legacy reconciliado para:
- [x] `2026_05_22_160000_create_account_deletion_audits_table`
- [x] `2026_06_04_000000_create_tickets_and_messages_tables`
- [x] Revisada la UX del detalle publico de propiedades y corregido localmente el overflow de cards/textos largos.
- [x] Revisado el bloque de formularios de propiedades con estrategia por tipo.
- [x] `Casa o chalet` completado en primera pasada local:
- [x] resumen de errores en frontend
- [x] bloqueo de guardado si faltan obligatorios clave
- [x] validacion backend especifica para tipo `1`
- [ ] Pendiente cuando se retome esta linea:
- [ ] prueba manual funcional de `Casa o chalet` autenticado en navegador local
- [ ] ajustar hallazgos UX/validacion de esa prueba
- [ ] replicar el mismo enfoque por tipo:
- [ ] `Piso`
- [ ] `Local o nave`
- [ ] `Garaje`
- [ ] `Terreno`
- [ ] `Casa rustica`
- [x] Cambio de prioridad acordado:
- [x] pausa temporal del trabajo de sprints/backend web
- [x] siguiente frente principal: preparar la app de ficha de proveedor para subida al **Apple App Store**

## Nueva prioridad release iOS (2026-06-07) - App de proveedores a Apple App Store
- [ ] Auditar estado actual de la app de proveedores para envio a App Store Connect.
- [ ] Confirmar build actual, bundle identifier, version y build number.
- [ ] Revisar requisitos Apple pendientes:
- [ ] metadatos App Store
- [ ] screenshots
- [ ] privacy nutrition
- [ ] privacy policy URL
- [ ] account deletion UX/link si aplica
- [ ] credenciales/demo flow para review
- [ ] Comprobar integracion real contra backend Kconecta ya desplegado.
- [ ] Ejecutar checklist tecnico de release iOS:
- [ ] login
- [ ] perfil proveedor
- [ ] logo proveedor
- [ ] ficha publica del proveedor
- [ ] metricas/ratings si forman parte del alcance del build
- [ ] Preparar plan de subida sin afectar produccion web hasta validar build final.

## Nueva tarea UX Registro (2026-05-12) - Reducir friccion en alta
- [ ] Objetivo:
- [ ] simplificar formulario de registro para aumentar conversion y reducir intimidacion del usuario.
- [ ] Reglas funcionales solicitadas:
- [ ] mantener obligatorios solo:
- [ ] `email`
- [ ] `password` + `password_confirmation`
- [ ] mantener validacion de identidad minima:
- [ ] `first_name` y/o `company_name` (al menos uno obligatorio).
- [ ] marcar explicitamente como `Opcional` todos los campos no obligatorios visibles en UI.
- [ ] eliminar del formulario de registro los campos de direccion:
- [ ] `address`
- [ ] `address_floor`
- [ ] `address_door`
- [ ] y metadatos asociados (`place_id`, ciudad, provincia, cp, lat, lng, etc.).
- [ ] Persistencia/compatibilidad:
- [ ] todo campo ocultado/eliminado del formulario debe guardarse como `null` en backend para no romper flujos existentes.
- [ ] Alcance tecnico:
- [ ] actualizar vista `resources/views/auth/auth.blade.php` (labels, visibilidad, required).
- [ ] actualizar validaciones en `app/Http/Controllers/Auth/RegisteredUserController.php`.
- [ ] asegurar que la creacion de `user` y `user_address` persista `null` cuando no haya datos.
- [ ] QA esperado:
- [ ] alta `Cliente final` con solo nombre/razon social + email + password.
- [ ] alta `Proveedor` y `Agente` sin bloqueo por campos marcados como opcionales (salvo reglas explicitamente obligatorias vigentes).
- [ ] no errores por campos de direccion ausentes.

## Session Update (2026-05-12) - Deploy ratings/client-final en produccion
- [x] Verificado estado Git local/remoto: `main` alineado con `origin/main` en `07c3aae`.
- [x] Backup pre-release ejecutado en VPS y validado:
- [x] `/root/kconecta_backups/*_pre_ratings_release/db_production.sql.gz`
- [x] Push a `origin/main` completado para los commits del bloque ratings + fixes asociados.
- [x] Redeploy manual en Dokploy ejecutado (autodeploy no refresco contenedor app en primer intento).
- [x] Migraciones en produccion revisadas:
- [x] `php artisan migrate --force` -> `Nothing to migrate`.
- [x] Limpieza de cache aplicada:
- [x] `php artisan optimize:clear`.
- [x] Smoke checks productivos validados:
- [x] `https://kconecta.com/` -> `200`
- [x] `https://kconecta.com/register` -> `200`
- [x] `https://kconecta.com/post/services` -> `302` a `/login` (esperado)
- [x] Verificacion de artefactos del refactor de detalle en produccion:
- [x] presentes `more_data.blade.php` y `share_login_modal.blade.php`.
- [x] Hallazgo operativo en produccion:
- [x] faltaba `user_level.id=6` (`Cliente final`) en catalogo DB.
- [x] Fix aplicado con `updateOrInsert` en produccion.
- [x] Resultado: selector de registro online ahora muestra `Cliente final`.
- [ ] Pendiente inmediato:
- [ ] QA online funcional completo del flujo: registro cliente final -> verificacion email -> valoracion a proveedor con codigo.

## Session Update (2026-05-07) - Mapa servicios + refactor detalles (local)
- [x] Corregido `GET /logout` web para evitar `419` en flujo de cierre de sesion por navegacion directa.
- [x] Corregido endpoint `dataServicesForMap` para resolver ubicacion desde `service_address` o fallback `user_address`.
- [x] Ajustado CTA en `result_all_service` a etiqueta generica `Buscar`.
- [x] Corregida persistencia de direccion de proveedor en perfil:
- [x] ya no se limpian `lat/lng` existentes cuando la direccion no cambia y no se selecciona nuevo place.
- [x] Refactor separado de `details.blade.php` a parciales:
- [x] `resources/views/page/partials/details/more_data.blade.php`
- [x] `resources/views/page/partials/details/share_login_modal.blade.php`
- [x] Commits locales creados:
- [x] `0c95828` - `Fix services map search and preserve provider geolocation`
- [x] `07c3aae` - `Refactor property details view into partials`
- [ ] Pendiente inmediato:
- [ ] smoke QA local post-refactor de detalle publico (`/result/{reference}`) antes de cualquier push.

## Session Update (2026-05-06) - Valoraciones proveedor (implementacion + QA local)
- [x] Esquema de datos implementado:
- [x] tabla `service_provider_ratings` creada en local
- [x] tabla `service_work_codes` creada en local
- [x] migracion de `user_level` para `Cliente final` (id `6`) agregada
- [x] Nota operativa local: por esquema legacy de `migrations`, se requirio registro/ajuste manual para completar consistencia.
- [x] Backend/API implementado:
- [x] generacion de codigo de trabajo (proveedor)
- [x] envio de valoracion por cliente final verificado con `work_code`
- [x] resumen por proveedor (`average_stars`, `ratings_count`, `my_stars`)
- [x] consumo atomico de codigo + update/create de valoracion en transaccion
- [x] Registro actualizado para nuevo perfil `Cliente final`:
- [x] disponible en selector de tipo de usuario
- [x] verificacion por email habilitada para este perfil
- [x] reglas refinadas (peticion de hoy):
- [x] `Nombre` o `Razon social` obligatorios (al menos uno)
- [x] `Apellido` opcional
- [x] `Movil (WhatsApp)` opcional para `Cliente final`
- [x] `Movil (WhatsApp)` obligatorio para `Proveedor` y `Agente`
- [x] `Telefono fijo` opcional
- [x] `Direccion` opcional
- [x] UI implementada:
- [x] `details_service` con bloque de rating (promedio, votos, estrellas)
- [x] formulario de valoracion visible para cliente final verificado
- [x] `/post/services` (vista proveedor) con seccion `Codigos de trabajo` (generar + copiar)
- [x] dashboard de `Cliente final` refinado:
- [x] vista orientada a valoraciones (sin metricas inmobiliarias de propiedad)
- [x] card `Realizar valoracion` con `codigo de trabajo` + `calidad del servicio` (estrellas clicables) + `Guardar`
- [x] input de codigo con icono tipo ticket y layout visual adaptado a referencia UX
- [x] ajuste de rutas para sesion web:
- [x] endpoints web `/service-ratings/*` para evitar `Unauthenticated` en backoffice
- [x] payload UI corregido para leer `data` anidado en respuestas API
- [x] QA local ejecutado:
- [x] `php artisan test --filter=ServiceRatingsApiTest` -> PASS (4 tests, 19 assertions)
- [x] `php artisan test --filter=RegistrationTest` -> PASS (2 tests, 4 assertions)

## Session Update (2026-05-05) - Plan Valoraciones con Estrellas (Proveedor)
- [ ] Implementar valoraciones de estrellas (1-5 enteras) para proveedores con elegibilidad por codigo de trabajo.
- [ ] Crear perfil `Cliente final` en `user_level`, habilitado para registro local con verificacion por email.
- [ ] Crear tabla `service_provider_ratings`:
- [ ] campos: `provider_user_id`, `client_user_id`, `stars`, timestamps
- [ ] `unique(provider_user_id, client_user_id)`
- [ ] indices en `provider_user_id` y `client_user_id`
- [ ] regla: `stars` entre 1 y 5
- [ ] regla: no auto-valoracion (`client_user_id != provider_user_id`)
- [ ] Crear tabla `service_work_codes`:
- [ ] campos: `provider_user_id`, `code`, `is_used`, `used_by_user_id`, `used_at`, timestamps
- [ ] indice en `provider_user_id`
- [ ] Endpoints API:
- [ ] `POST /api/service-ratings/work-codes` (proveedor autenticado) -> generar codigo
- [ ] `POST /api/service-ratings` (cliente final autenticado/verificado) -> `{ provider_user_id, work_code, stars }`
- [ ] `GET /api/service-ratings/provider/{provider_user_id}` -> `{ average_stars, ratings_count, my_stars? }`
- [ ] Reglas de flujo:
- [ ] validar codigo existente, del proveedor correcto y no usado
- [ ] marcar codigo como usado por cliente (`used_by_user_id`, `used_at`)
- [ ] crear/actualizar valoracion (1 por cliente/proveedor, editable)
- [ ] UI:
- [ ] `details_service`: mostrar promedio, total de votos y estrellas visuales
- [ ] cliente final verificado: mostrar formulario de estrellas + codigo de trabajo
- [ ] backoffice proveedor: seccion para generar/copiar codigos de trabajo
- [ ] sin comentarios de texto en vistas ni API
- [ ] Errores API claros: no verificado, codigo invalido/usado, stars fuera de rango, rol no permitido.
- [ ] Plan de pruebas:
- [ ] unitarias: rango stars, unicidad, no auto-valoracion, ciclo de codigo
- [ ] integracion/API: bloqueos por no verificado, voto OK con codigo valido, update sin duplicado, resumen correcto
- [ ] funcional UI: detalle con rating agregado, proveedor genera codigo, cliente vota con elegibilidad

## Session Update (2026-04-29) - Cadastral Production Go-Live
- [x] Fix backend desplegado a `main`:
- [x] `fix(cadastral): harden API errors and guard migration for existing table` (`6a082b3`)
- [x] Migracion ejecutada en produccion (`cadastral_prices`).
- [x] Registro manual de migracion aplicado por esquema legacy de tabla `migrations`.
- [x] Cache Laravel limpiada en produccion (`php artisan optimize:clear`).
- [x] CSV catastral subido e importado en produccion (`precios_m2_catalunya_detallado.csv`).
- [x] Validacion funcional online completada:
- [x] calculo base por direccion + m2 en home
- [x] paso a calculadora avanzada con estimacion visible
- [ ] Pendiente operativo recomendado:
- [ ] ejecutar backup post-deploy y registrar ruta final del dump.

## Session Update (2026-04-27)
- [x] Reglas de primera etapa JM aplicadas para proveedor de servicios:
- [x] alta de proveedor sin campos de tipo/numero de documento en UI de registro
- [x] WhatsApp obligatorio mantenido
- [x] telefono fijo opcional mantenido
- [x] direccion opcional permitida para alta/edicion de perfil
- [x] guardado de servicios proveedor sin bloqueo por direccion faltante
- [x] Validacion operativa reportada:
- [x] edicion de perfil online OK
- [x] flujo de servicios online OK
- [ ] Pendiente cierre de negocio: pruebas online finales por Gala y JM

## Session Update (2026-04-27) - Frontend Calculador Catastral V1
- [x] Nueva seccion `Calculador catastral` agregada en home, debajo del HERO.
- [x] Fondo estatico integrado con `public/img/mapa-calculator.webp`.
- [x] UI V1 implementada sin logica de calculo:
- [x] input unico de direccion (`#cadastral-address-input`)
- [x] input de m2 requerido para habilitar calculo
- [x] validacion de direccion por Google integrada en home (place_id + codigo postal)
- [x] boton preparado para fase 2 (`#cadastral-submit-btn`)
- [x] script dedicado conectado en home: `public/js/cadastral_calculator.js`
- [ ] Fase 2 pendiente:
- [ ] tabla de precios por m2
- [ ] endpoint de calculo catastral
- [ ] conexion de boton con flujo real de calculo

## Session Checkpoint (2026-04-21)

### Done
- [x] Proyecto migrado de DameloDamelo a Kconecta en branding y referencias principales.
- [x] Docker local operativo:
- [x] App: `kconecta`
- [x] DB: `kconecta-mysql-1`
- [x] URL local: `http://localhost:8010`
- [x] Repo GitHub correcto y sincronizado:
- [x] `https://github.com/sttildeveloper/kconecta-crm`
- [x] `main` publicado
- [x] remoto final: `origin`
- [x] Acceso Hostinger y Dokploy validados desde este equipo de oficina.
- [x] Produccion sincronizada hacia local para pruebas:
- [x] dump productivo importado en `kconecta_schema`
- [x] login local validado con las mismas credenciales de produccion
- [x] Politica operativa definida y publicada:
- [x] `commit -> push -> autodeploy -> verify`
- [x] Politica inicial de versionado con tags anotados:
- [x] `v0.1.0`
- [x] `v0.1.1`
- [x] Warning de Apache en produccion corregido:
- [x] `ServerName` global configurado
- [x] `AH00558` eliminado de logs de arranque
- [x] Navegacion del backoffice ajustada:
- [x] `Dashboard` renombrado a `Escritorio`
- [x] Integracion Google Maps migrada para proyectos nuevos:
- [x] autocomplete legacy reemplazado por flujo compatible con `Places API (New)`
- [x] mapa y reverse geocoding siguen operativos
- [x] Google Cloud actualizado en proyecto `kconectacrm`:
- [x] `Maps JavaScript API` habilitada
- [x] `Places API` habilitada
- [x] `Places API (New)` habilitada
- [x] `Geocoding API` habilitada
- [x] Dokploy con `GOOGLE_MAPS_API_KEY` cargada en produccion
- [x] Sugerencias de direccion verificadas manualmente en produccion
- [x] Flujo de creacion de propiedades endurecido:
- [x] `POST /post/create` reutiliza el flujo real de guardado
- [x] backend exige direccion resuelta con `latitude` y `longitude`
- [x] validacion numerica de coordenadas agregada
- [x] mensajes flash de exito/error visibles en backoffice
- [x] Conversion de imagenes a `.webp` aplicada antes de persistir archivos en el servidor.
- [x] Bug de placeholders `Seleccione` corregido:
- [x] frontend con placeholders no enviables
- [x] backend ignora valores no numericos en campos `_id`
- [x] Gestion de multimedia en edicion corregida:
- [x] al reemplazar portada se borra el archivo previo
- [x] al reemplazar video se borra el archivo previo
- [x] borrar imagenes adicionales queda diferido al submit
- [x] Fallback de portada faltante corregido en vistas de listado, detalle y edicion.
- [x] Render del simbolo `EUR` corregido en listados de propiedades.
- [x] Tipos de propiedad validados online en produccion:
- [x] `Casa o chalet`
- [x] `Piso`
- [x] `Local o nave`
- [x] `Garaje`
- [x] `Terreno`
- [x] `Casa rustica`
- [x] Edicion online validada al menos para `Piso`.
- [x] Registros incompletos de prueba (`Piso`) diagnosticados y eliminados de produccion.
- [x] `Garaje` validado online tanto en alta como en edicion para venta y alquiler.
- [x] Gala probo online el flujo de `Garaje` y funciono correctamente.
- [x] `Terreno` validado localmente tanto en alta como en edicion para venta y alquiler.
- [x] Fix aplicado en edicion de `Terreno` para el layout de titulo, sitio web y descripcion.
- [x] Respaldo productivo validado en host:
- [x] `/root/kconecta_backups/20260415_1656_pre_commit_sync/db_production.sql`
- [x] `/root/kconecta_backups/20260415_1656_pre_commit_sync/media_production/img_uploads`
- [x] `/root/kconecta_backups/20260415_1656_pre_commit_sync/media_production/video_uploads`
- [x] Incidente de perdida de media post-deploy diagnosticado:
- [x] causa raiz confirmada en almacenamiento efimero del contenedor
- [x] volumen persistente Dokploy configurado para `/var/www/html/public/img/uploads`
- [x] volumen persistente Dokploy configurado para `/var/www/html/public/video/uploads`
- [x] media historica restaurada desde backup en los volumenes persistentes
- [x] redeploy de verificacion completado sin perdida de imagenes
- [x] nueva subida validada online y persistente tras redeploy
- [x] Seleccion multiple de imagenes en galeria habilitada en formularios de edicion pendientes:
- [x] `Casa o chalet`
- [x] `Piso`
- [x] `Local o nave`
- [x] `Terreno`
- [x] `Casa rustica`
- [x] Validacion local completada para edicion de `Piso` con seleccion multiple en galeria.
- [x] Validacion local completada para edicion de `Local o nave` con seleccion multiple en galeria.
- [x] Deploy a produccion del fix de galeria multiple completado desde `main`.
- [x] Validacion online posterior al deploy reportada como satisfactoria.
- [x] Mini auditoria de formularios de propiedades completada para detectar naming cruzado y residuos legacy.
- [x] Limpieza controlada de naming/residuos publicada en:
- [x] `ee63e5c` - `Clean property form naming and legacy residue`
- [x] Cambio de `Terreno` implementado y publicado en:
- [x] `eadae0a` - `Add terrain use support and normalize land forms`
- [x] Nuevo modelo de datos para `Terreno` implementado:
- [x] tabla `terrain_use`
- [x] columna nullable `property.terrain_use_id`
- [x] Catalogo web/API de `Terreno` separado en:
- [x] `Tipo de teRústico`
- [x] `Uso`: `Servicios`, `Residencial`, `Industrial`, `Agrícola`
- [x] Formulario web de alta de `Terreno` validado online tras aplicar esquema en produccion.
- [x] Detalle publico de `Terreno` muestra arriba los recuadros:
- [x] `Tipo de terreno`
- [x] `Uso`
- [x] Backup productivo previo al cambio de `Terreno` creado y validado:
- [x] `/root/kconecta_backups/20260420_2313_pre_terreno/db_production.sql.gz`
- [x] `/root/kconecta_backups/20260420_2313_pre_terreno/type_of_terrain.tsv`
- [x] `/root/kconecta_backups/20260420_2313_pre_terreno/terrain_properties.tsv`
- [x] Contenedor MySQL productivo utilizado para backup del cambio de `Terreno`:
- [x] `kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3`
- [x] Contenedor app productivo utilizado para migraciones/cache clear del cambio de `Terreno`:
- [x] `kconecta-kconectacrm-5oikfs.1.8j4e7feeo9l3yxw5hap9vhw8k`
- [x] Incidencia `500` en `/post/create_form/9` diagnosticada y resuelta:
- [x] causa raiz confirmada en migraciones productivas no aplicadas
- [x] esquema productivo actualizado manualmente
- [x] cache productiva limpiada
- [x] pruebas online satisfactorias posteriores a la correccion
- [x] Particularidad operativa documentada:
- [x] la tabla legacy `migrations` no acepta el registro estandar de Laravel sin poblar campos extra (`version`, `class`, `group`, `namespace`, `time`)
- [x] durante futuras migraciones productivas puede ser necesario registrar manualmente cada migracion ya ejecutada con `php artisan tinker --execute ... updateOrInsert(...)`
- [x] Bloque `Terreno` (calificaciones) implementado y publicado en:
- [x] `703ae94` - `Terreno: calificaciones, usos y detalle público`
- [x] Bloque `Terreno` (superficies condicionales por tipo) implementado y publicado en:
- [x] `9cd087e` - `Terreno: mostrar campos de superficie segun tipo`
- [x] Regla aplicada en formularios de alta/edicion de `Terreno`:
- [x] si `Tipo de terreno` = `Urbano` o `Urbanizable`, mostrar:
- [x] `Superficie edificable` (`plot_meters`)
- [x] `Superficie minima vende/alquila` (`useful_meters`)
- [x] si cambia a otro tipo, ocultar y limpiar ambos campos en frontend
- [x] Validacion local reportada:
- [x] alta de terreno OK
- [x] edicion de terreno OK (corregido undefined variable en `form_5_update`)
- [x] Produccion actualizada con nuevas tablas:
- [x] `terrain_qualification`
- [x] `terrain_qualifications`
- [x] Verificacion productiva posterior al deploy:
- [x] `Schema::hasTable('terrain_qualification') = true`
- [x] `Schema::hasTable('terrain_qualifications') = true`
- [x] catalogo `terrain_qualification` con `8` registros
- [x] Validacion online reportada:
- [x] detalle publico de terreno muestra `Tipo de calificación` correctamente

### In Progress
- [ ] Retirar o reconvertir `createService()` y sus flujos asociados al modelo canonico de ficha de proveedor.
- [ ] Limpieza final de textos mojibake/encoding en vistas legacy.
- [ ] Retirar aliases API `service_types` y `service_type_ids` cuando los consumidores activos ya usen `specialties` y `specialty_ids`.

### Session Update (2026-04-22)
- [x] Registro de proveedores alineado:
- [x] tipos permitidos en registro limitados a `Proveedor de servicios` y `Agente inmobiliario`
- [x] validaciones CIF/DNI/NIE reforzadas
- [x] `username` alineado con `Razon social`
- [x] Perfil de proveedor:
- [x] foto/logo procesada a WebP 350x350 en backend
- [x] `username` bloqueado (solo lectura)
- [x] feedback de carga/guardado en interfaz de perfil
- [x] Servicios proveedor:
- [x] formulario de alta sin bloque duplicado de datos de usuario
- [x] direccion de servicio resuelta desde perfil del proveedor
- [x] slider en `/post/services` funcional con controles + dots
- [x] bug visual post-login mitigado en primer render del slider
- [x] CTA de ver publicacion en listados ajustado a `_blank`
- [x] Detalle publico de servicio:
- [x] fix de carga de video por `service_id` en `result_service`
- [x] Mapas de resultados:
- [x] icono de marcador migrado a branding Kconecta (`kconecta_icon.webp`)
- [x] UX proveedor:
- [x] boton `Ver video` retirado de cabecera de `/post/services` (video queda en bloque inferior)
- [x] Verificacion por email en produccion:
- [x] columna `user.email_verified_at` creada en produccion para evitar `500` en `/verify-email/...`
- [x] redireccion post-verificacion ajustada por rol (proveedor -> `/post/services`)
- [x] Persistencia de logos de perfil:
- [x] volumen Dokploy agregado para `/var/www/html/public/img/photo_profile`
- [x] restore de logo desde backup validado
- [x] smoke deploy validado sin perdida de media en rutas persistentes
- [x] Backup pre-persistencia creado en host:
- [x] `/root/kconecta_backups/20260422_1739_pre_persist_media`

### Session Update (2026-04-23)
- [x] Admin -> detalle de usuario proveedor:
- [x] fix de `500` en `GET /users/{id}` por conteo en columna incorrecta.
- [x] conteos de servicios validados online:
- [x] `Publicaciones de servicio`
- [x] `Tipos de servicio`
- [x] tags no clicables de tipos de servicio agregados bajo metricas.
- [x] recorrido online final validado como correcto por negocio.
- [x] Backoffice -> `Mis propiedades` (`/post/my_posts`) refinado:
- [x] barra de filtros reorganizada en dos filas segun referencia visual aprobada
- [x] placeholders/labels alineados (`Titulo o referencia`, `Categoria`, `dd / mm / aaaa`)
- [x] botonera `Filtrar`/`Limpiar` ajustada y responsive estabilizado

### Next
- [ ] Recoger feedback final de Gala/JM tras pruebas online de esta etapa y cerrar acta de validacion.
- [ ] Respaldar BD local.
- [ ] Comparar media faltante de referencias con respaldo productivo antes de limpiar archivos no trackeados.
- [ ] Decidir si se limpiaran mas adelante de `type_of_terrain` los valores legacy `Servicios`, `Industrial` y `Afectado` una vez confirmada la no dependencia historica.
- [ ] Implementar plan de video:
- [ ] cambiar mensaje de `50MB` a limite real alineado
- [ ] validar tamano antes de subir
- [ ] comprimir video en frontend antes del submit
- [ ] Eliminar la dependencia funcional de `createService()` en favor del mantenimiento de ficha de proveedor.
- [ ] igualar formularios web y movil por tipo de propiedad.

### Closed
- [x] Bloque de trabajo de `Terreno` dado por cerrado tras validacion online y documentacion operativa.
- [x] API v1 proveedores (`/api/agent/services`) implementada historicamente; queda marcada como legacy frente al modelo canonico de ficha de proveedor.
- [x] Soporte `multipart` en create/update para `cover_image`, `more_images` y `video` en API v1 proveedores.
- [x] Contrato JSON unificado para API v1 proveedores (`success`, `data`, `meta`, `message`, `errors`).
- [x] Pruebas feature base agregadas para auth, permisos por rol, ownership y CRUD proveedor.
- [x] Cobertura de login/token mobile completada: `POST /api/login` retorna Bearer token utilizable para `GET /api/me` (`AuthApiTest`, 2026-05-14).
- [x] Regresion minima API validada (2026-05-14): `ServiceRatingsApiTest` PASS + smoke `200` en `/api/services` y `/api/services_for_map`.
- [x] Etapa 2 iniciada (2026-05-14): cobertura extendida en `ProviderServicesApiTest` para matriz de acceso (`403/404`) y validaciones `422` con contrato de errores.
- [x] Etapa 2 completada (2026-05-14): limites de media aplicados en API v1 proveedores (`5MB` imagen, `50MB` video), politica `403/404` documentada y tests de tamano `422` en verde.
- [x] Etapa 3 completada (2026-05-14): contrato JSON v1 validado en respuestas de exito y error del CRUD proveedor.
- [x] Etapa 4 completada (2026-05-14): lifecycle de media multipart validado en update (replace cover/video + delete selectivo de `more_images`).
- [x] Etapa 5 completada (2026-05-14): plan/documentacion y checklist QA final consolidados para release readiness.

### Security Backlog
- [ ] Rotar secretos actuales (`APP_KEY`, API keys, credenciales DB).
- [ ] Forzar actualizacion de passwords por defecto.
- [ ] Eliminar fallback de login legacy que acepta password en texto plano.
- [ ] Verificar que no se suban secretos reales al repo.
- [ ] Mover credenciales sensibles fuera de notas operativas y comandos historicos.

### Notes
- Mantener este archivo como fuente de verdad para estado y proximos pasos.
- No reimportar dumps legacy en produccion sin validacion de esquema.
- No subir dumps, backups ni secretos al repo.
- Restore local de dumps productivos: usar copia binaria al contenedor MySQL + `mysql --default-character-set=utf8mb4`; no usar `Get-Content ... | mysql`.
- `todo.md` sigue como archivo local sin trackear; no mezclarlo en commits funcionales.
- `.codex_tmp` sigue local y sin trackear; no mezclarlo en commits.
- Para inspeccion rapida de produccion, preferir Hostinger browser terminal si el SSH directo desde este PC vuelve a fallar.
- `origin/main` quedo alineado con `eadae0a` tras publicar el cambio de `Terreno`.
- `origin/main` quedo actualizado con `703ae94` para el bloque de `Tipo de calificación` en `Terreno`.
- `origin/main` quedo actualizado con `9cd087e` para superficies condicionales de `Terreno`.
- El backup mas reciente util para el cambio de `Terreno` esta en `/root/kconecta_backups/20260420_2313_pre_terreno`.
- Para backups productivos del CRM, usar directamente el contenedor MySQL:
- `kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3`
- Comando validado para dump:
- `docker exec kconecta-crm-b8ejyl.1.uhlwrkdsmasxw6hmpnkio19y3 sh -lc 'mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > "$BKP_DIR/db_production.sql"`
- Para migraciones productivas del CRM, usar directamente el contenedor app:
- `kconecta-kconectacrm-5oikfs.1.8j4e7feeo9l3yxw5hap9vhw8k`
- Referencias `sadtgnab`, `6ckhqztv` y `cyj5uxrv` tienen media en BD local pero sus archivos no existen en `public/img/uploads`.
- La persistencia productiva de media ya no esta pendiente: quedo resuelta en Dokploy y validada con redeploy mas subida nueva.

- Context update `2026-04-23`:
- [x] Corregir cards vacias en `resources/views/page/details.blade.php` (garaje/nave).
- [x] Reubicar `Fianza` y `Estado de conservacion` al bloque superior de cards pequenas.
- [x] Aplicar fallback `N/A` en cards superiores cuando falten datos.

- Context update `2026-05-14`:
- [x] API v1 proveedores cerrada por etapas (1-5) con validacion final `tests/Feature/Api` en verde.
- [x] Commits por etapa aplicados localmente, sin push (evitar autodeploy).
- [ ] Esperar instrucciones de JM y Gala para siguiente bloque de desarrollo de app movil.

- Context update `2026-05-18`:
- [x] Gaps de auditoria mobile cerrados en backend local Docker:
- [x] catalogo API para `service_type` agregado: `GET /api/agent/service-types`.
- [x] contrato JSON v1 reforzado en auth (`/api/login`, `/api/me`, `/api/logout`) y ratings (`/api/service-ratings*`).
- [x] endpoints API de propiedades `agent/*` alineados a contrato JSON v1 (`success`, `data`, `meta`, `message`, `errors`).
- [x] endpoint legacy `GET /api/delete_more_image` retirado con respuesta `410` y ruta moderna documentada.
- [x] fallback de password en texto plano eliminado del login API.
- [x] validacion final API en Docker: `tests/Feature/Api` PASS (20 tests, 177 assertions).

- Context update `2026-05-18` - Cero deuda (Paso 1):
- [x] Endpoints publicos de discovery (`/api/properties`, `/api/services`, `/api/properties_for_map`, `/api/services_for_map`) alineados a contrato JSON v1.
- [x] Compatibilidad legacy preservada (`status`, `province`) para no romper consumidores web existentes.
- [x] Cobertura nueva `PublicDiscoveryApiTest` agregada y en verde.

- Context update `2026-05-18` - Cero deuda (Paso 2):
- [x] Contrato `401` unificado para rutas API via manejador de excepciones (`bootstrap/app.php`).
- [x] Ruta web legacy que colisionaba con `/api/delete_more_image` eliminada (`routes/web.php`).
- [x] Cobertura `PropertyApiContractTest` agregada para contrato en `agent/properties` y deprecacion `410` legacy endpoint.

- Context update `2026-05-18` - Cero deuda (Paso 3):
- [x] Hardening auth API: `POST /api/login` con rate-limit (`throttle:10,1`).
- [x] Logout API endurecido: revocacion robusta de token personal y cierre de sesion web.
- [x] Endpoints utilitarios legacy (`visitor/*`, `property_stats/register`, `verify_token_google`, `send/message/*`) alineados a contrato JSON v1 con compatibilidad legacy.
- [x] Cobertura `AuthApiTest` y nueva `LegacyUtilityApiContractTest` en verde.
- [x] Suite API Docker validada: `tests/Feature/Api` PASS (30 tests, 267 assertions).

- Context update `2026-05-18` - Cero deuda (Paso 4):
- [x] Limpieza de mojibake/UTF-8 aplicada en vistas públicas clave de detalle/compartir.
- [x] Limpieza de mojibake aplicada en archivos de contexto (`tasks.md`, `roadmap.md`, `agent.md`).
- [x] Verificación de residuos `Ã/Â` en archivos críticos sin hallazgos.
- [x] Regresión API en Docker validada: `tests/Feature/Api` PASS (30 tests, 267 assertions).

- Context update `2026-05-18` - Cero deuda (Paso 5):
- [x] Deuda de testing catastral cerrada:
- [x] `CadastralImportCommandTest` agrega cobertura de importación inicial + upsert.
- [x] `CadastralEstimateApiTest` agrega cobertura de estimación con postal code existente + `404` en inexistente.
- [x] Regresión final API validada en Docker: `tests/Feature/Api` PASS (32 tests, 273 assertions).



- Context update `2026-05-18` - Cierre productivo API mobile readiness:
- [x] Commits locales publicados a `origin/main`:
- [x] `e23ebae` (`throttle` mixto + verificacion email API en JSON 403).
- [x] `15d6703` (cierre H-02 ratings/work-codes + configuracion CORS).
- [x] Redeploy productivo ejecutado en Dokploy con codigo actualizado.
- [x] Cache de Laravel regenerada en contenedor productivo:
- [x] `php artisan optimize:clear`
- [x] `php artisan config:cache`
- [x] `php artisan route:cache`
- [x] Validacion productiva API:
- [x] `GET /api/me` -> `401` contrato v1 (`success/data/meta/message/errors`).
- [x] `GET /api/properties?text=mad` -> `200` contrato v1 + compat legacy (`status`, `province`).
- [x] `OPTIONS /api/properties` -> `204` con CORS operativo.
- [x] CORS productivo endurecido:
- [x] `CORS_ALLOWED_ORIGINS=https://kconecta.com,https://www.kconecta.com`.
- [x] `Access-Control-Allow-Origin` validado en preflight para `https://kconecta.com`.

- Context update `2026-05-21` - Provider logo canonico para app movil:
- [x] API perfil proveedor ampliada con lectura canonica:
- [x] `GET /api/agent/services/profile` ahora incluye `provider_logo_url` y `provider_logo_path`.
- [x] Regla de no regresion aplicada: `provider_logo_url` no usa `cover_image_url` ni galeria.
- [x] Nuevo update de perfil proveedor para logo:
- [x] `PATCH /api/agent/services/profile` acepta `provider_logo` (`jpg,jpeg,png,webp`, max `2048KB`).
- [x] Compatibilidad legacy temporal habilitada para upload:
- [x] fallback de campos `logo|photo|avatar|image|company_logo` cuando no llega `provider_logo`.
- [x] Respuesta de `PATCH` devuelve perfil actualizado con `provider_logo_url`.
- [x] Coherencia entre clientes:
- [x] `GET /api/me` incluye `provider_logo_url` y `provider_logo_path`.
- [x] Cobertura feature agregada/actualizada:
- [x] `ProviderServicesApiTest` y `AuthApiTest` en verde.
- [x] Publicado en `origin/main` commit `87941f2` (deploy habilitado para revision mobile).

## Session Update (2026-05-22) - Service metrics online fixed and validated
- [x] Deployed backend/frontend tracking for provider service KPIs (commit `355e49f`).
- [x] Added migrations:
- [x] `2026_05_22_090000_create_service_profile_visits_table`
- [x] `2026_05_22_090100_create_service_contact_clicks_table`
- [x] Added/validated tracking endpoints for:
- [x] profile visit register
- [x] contact click register
- [x] Dashboard provider metrics now read from persistent tables:
- [x] `service_profile_visits`
- [x] `service_contact_clicks`
- [x] `service_work_codes` (used tickets)
- [x] Online validation confirmed with real click/visit:
- [x] `Visitas al perfil` increments.
- [x] `Clicks en contacto` increments.
- [x] `Tickets de servicio` remains consistent.

## Session Incident Note (2026-05-22) - Legacy migrations table drift
- [x] Root cause identified: production `migrations` table is non-standard and requires:
- [x] `version`
- [x] `class`
- [x] `namespace`
- [x] `group`
- [x] `time`
- [x] Symptom: `SQLSTATE[HY000]: 1364 Field 'version' doesn't have a default value` after physical migration execution.
- [x] Mitigation used:
- [x] manual insert into `migrations` with all required legacy fields.
- [x] re-run `php artisan migrate --force` until `Nothing to migrate`.
- [x] Additional reconciliation performed:
- [x] manually created missing table `service_contact_clicks` in production.

## Safe Migration SOP (VPS) - Avoid line-break/quote failures
- [x] Step 1: refresh dynamic container names every time before commands:
- [x] `APP_CTN=$(docker ps --format '{{.Names}}' | grep '^kconecta-kconectacrm-5oikfs\.1\.')`
- [x] `DB_CTN=$(docker ps --format '{{.Names}}' | grep '^kconecta-crm-b8ejyl\.1\.')`
- [x] Step 2: verify no hidden newlines in vars:
- [x] `printf '%s\n' "$APP_CTN"`
- [x] `printf '%s\n' "$DB_CTN"`
- [x] Step 3: backup before migrate:
- [x] `mysqldump` from `DB_CTN` into `/root/kconecta_backups/<timestamp>_pre_migrate`.
- [x] Step 4: migrate in maintenance mode + cache rebuild.
- [x] Step 5: if legacy error appears, use HEREDOC SQL (not escaped mega one-liners).
- [x] Step 6: final checks:
- [x] `php artisan migrate --force` -> `Nothing to migrate`
- [x] confirm tables exist
- [x] validate online KPI increment flow

## Nueva tarea Release Compliance (2026-05-22) - Google Play + App Store
- [ ] Objetivo:
- [ ] completar auditoria integral de requisitos de publicacion para marketplaces movil (Google Play y App Store) y cerrar brechas de cumplimiento antes de submit.

### Brechas detectadas (prioridad alta)
- [ ] Publicar URL HTML de `Politica de Privacidad` (publica, accesible, no PDF, no geobloqueada).
- [ ] Publicar URL HTML de `Eliminar cuenta y datos` (flujo visible y funcional fuera de la app).
- [ ] Agregar ruta in-app para iniciar eliminacion de cuenta (iOS/Android).
- [ ] Alinear politica con retencion/eliminacion real de datos (incluye excepciones legales/fraude).

### Google Play - checklist operativo
- [ ] Play Console -> App content -> Data safety:
- [ ] declarar recoleccion/uso/comparticion de datos de forma exacta.
- [ ] responder bloque de eliminacion de cuenta/datos.
- [ ] Play Console -> Privacy Policy URL:
- [ ] URL activa HTML (no PDF), editable solo por el propietario, referencia a app o developer.
- [ ] App:
- [ ] mostrar acceso a politica de privacidad dentro de la app.
- [ ] incluir opcion visible para eliminar cuenta en app (o deep-link al flujo web permitido por politica).
- [ ] Verificar declaraciones de permisos sensibles y coherencia con funcionalidad publicada.

### App Store - checklist operativo
- [ ] App Store Connect -> App Privacy:
- [ ] `Privacy Policy URL` obligatorio y publico.
- [ ] completar nutricion de privacidad (data collected/linked/tracking) coherente con app real + SDKs.
- [ ] Guideline 5.1.1:
- [ ] link de politica dentro de la app y en metadata de App Store Connect.
- [ ] politica debe explicar coleccion, uso, terceros, retencion y eliminacion de datos.
- [ ] Guideline 5.1.1(v):
- [ ] si hay cuenta, debe existir eliminacion de cuenta dentro de la app.
- [ ] Submission readiness:
- [ ] demo account o demo mode completo para App Review si aplica login.
- [ ] notas de review y credenciales de prueba actualizadas.

### Entregables minimos (HTML)
- [ ] `/privacy-policy` (HTML publico).
- [ ] `/account-deletion` (HTML publico con flujo de solicitud).
- [ ] contenido legal minimo:
- [ ] responsable del tratamiento/contacto.
- [ ] datos recolectados y finalidades.
- [ ] terceros/SDKs.
- [ ] retencion de datos.
- [ ] derechos del usuario (acceso, rectificacion, eliminacion).
- [ ] proceso y plazo de eliminacion de cuenta/datos.

### Validacion pre-submit
- [ ] QA funcional:
- [ ] crear cuenta -> solicitar eliminacion en app -> confirmar solicitud -> estado final.
- [ ] solicitar eliminacion desde web publica (sin reinstalar app).
- [ ] QA tecnico:
- [ ] verificar respuestas API de eliminacion y borrado/anonymizacion de datos asociados.
- [ ] verificar links en metadata Play/App Store y dentro de la app.
- [ ] Evidencia:
- [ ] capturas + video corto del flujo de eliminacion.
- [ ] checklist firmada por producto/legal/tecnico.

## Session Closure (2026-05-22) - Mobile metrics integration validated online
- [x] Backend API for provider KPI metrics exposed in mobile profile endpoint.
- [x] Endpoint confirmed for mobile dashboard:
- [x] `GET /api/agent/services/profile` now returns KPI fields:
- [x] `profile_visits`, `profile_visits_change_pct`
- [x] `contact_clicks`, `contact_clicks_change_pct`
- [x] `service_tickets`, `service_tickets_change_pct`
- [x] plus compatibility aliases:
- [x] `visits_count`, `contact_clicks_count`, `service_tickets_count`
- [x] Tracking endpoints confirmed and operational:
- [x] `POST /api/service_stats/register_visit`
- [x] `POST /api/service_stats/register_contact_click`
- [x] Local Docker validation completed:
- [x] `ProviderServicesApiTest` PASS (`22` tests, `195` assertions).
- [x] Production VPS migration state validated:
- [x] tables exist: `service_profile_visits`, `service_contact_clicks`, `service_work_codes`.
- [x] `php artisan migrate --force` => `Nothing to migrate`.
- [x] migration status shows `2026_05_22_*` as `Ran`.
- [x] Mobile app online validation completed:
- [x] dashboard now shows real values for `visitas`, `clicks`, `tickets`.

## Commit/Release Log (2026-05-22)
- [x] Published to `origin/main`: `d7fa1ed`
- [x] Commit scope:
- [x] mobile KPI API exposure in `ProviderServiceApiController@profile`
- [x] context/runbook updates for safe VPS migration workflow

## Session Update (2026-05-22) - Release blocker compliance (stores) implemented
- [x] API Password Recovery implemented:
- [x] `POST /api/forgot-password` (generic anti-enumeration response).
- [x] `POST /api/reset-password` (token + password reset via Laravel broker).
- [x] API Account Deletion implemented:
- [x] `DELETE /api/me` (auth required + password confirmation).
- [x] compatibility endpoint: `POST /api/account/delete`.
- [x] account deletion behavior:
- [x] revoke Sanctum tokens.
- [x] anonymize direct PII fields in `user`.
- [x] disable account (`is_active=0` when column exists).
- [x] remove profile address rows from `user_address` when present.
- [x] Legal HTML pages published (public, 200 OK):
- [x] `/legal/privacy`
- [x] `/legal/terms`
- [x] `/legal/account-deletion`
- [x] legacy route `/policy_and_privacy` now redirects to `/legal/privacy`.
- [x] Automated tests added and passing:
- [x] `AccountComplianceApiTest` (forgot/reset/delete)
- [x] `LegalPagesTest` (public legal pages)
- [x] full API suite in Docker: `48` tests PASS (`358` assertions).

## Session Update (2026-05-22) - Compliance hardening pass (orchestrated)
- [x] Orchestrated multi-agent audit executed and findings remediated.
- [x] Security hardening:
- [x] account delete endpoints now rate-limited (`throttle:5,1`).
- [x] account deletion audit trail added (`account_deletion_audits` table + insert on delete flow).
- [x] anonymized deleted email domain moved to config (`config/legal.php`, `DELETED_USER_EMAIL_DOMAIN`).
- [x] Legal architecture hardening:
- [x] legal pages refactored to shared layout + shared nav + shared CSS (`public/css/legal.css`).
- [x] legal content now parameterized from `config/legal.php` (brand/responsible/contact/jurisdiction/SLA/retention).
- [x] canonical legal route naming added:
- [x] `legal.privacy`, `legal.terms`, `legal.account-deletion`.
- [x] legacy route hardened to permanent redirect: `/policy_and_privacy` -> `/legal/privacy` (301).
- [x] QA hardening:
- [x] extra tests for unauthenticated delete, alias endpoint parity, and forgot-password rate limit.
- [x] extra legal tests for required sections + canonical redirect assertion.
- [x] Docker validation:
- [x] `AccountComplianceApiTest` PASS.
- [x] `LegalPagesTest` PASS.
- [x] full API suite PASS (`51` tests, `369` assertions).

## Session Closure (2026-05-22) - Compliance deploy + migration + mobile handoff
- [x] Compliance hardening commit published: `c3caab1`.
- [x] Production deploy executed.
- [x] VPS migration handled (legacy schema):
- [x] `2026_05_22_160000_create_account_deletion_audits_table` reconciled and marked as `Ran`.
- [x] Final migration check passed:
- [x] `php artisan migrate --force` => `Nothing to migrate`.
- [x] Compliance endpoints online:
- [x] `POST /api/forgot-password`
- [x] `POST /api/reset-password`
- [x] `DELETE /api/me`
- [x] `POST /api/account/delete`
- [x] Legal public pages online:
- [x] `/legal/privacy`
- [x] `/legal/terms`
- [x] `/legal/account-deletion`
- [x] Native app Codex prompt delivered to implement store-compliance UX flows end-to-end.

## Session Update (2026-05-29) - Final client ratings dashboard API
- [x] Added authenticated endpoint GET /api/service-ratings/my-dashboard in outes/api.php (uth:sanctum).
- [x] Implemented ApiController@myServiceRatingsDashboard with:
- [x] access control (401 UNAUTHENTICATED, 403 ROLE_NOT_ALLOWED).
- [x] dashboard payload: atingsCount, providersRatedCount, verageStars, ecentRatings.
- [x] recent activity ordered by updated_at descending (limit 10).
- [x] provider name fallback chain for robust UI labels.
- [x] Added/updated feature tests for endpoint auth, role gate, structure, order, and metric correctness.
- [x] Test run completed: docker compose exec app php artisan test --filter=ServiceRatingsApiTest -> PASS (15 tests, 56 assertions).

## Session Closure (2026-07-26) - New public services home completed locally

### Implementación y QA

- [x] Revisado el contexto y preservado el worktree existente.
- [x] Sustituido el home inmobiliario por el home aprobado de profesionales.
- [x] Creados layout, vista, parciales, CSS, JavaScript y hero WebP.
- [x] Reutilizados datos reales de `service_type`, `user`, `user_address`, `provider_services` y `service_provider_ratings`.
- [x] Reutilizadas rutas reales de búsqueda, login, alta, legales y ficha pública.
- [x] Eliminadas del home las referencias a inmuebles y enfermería sin borrar módulos internos.
- [x] Directorio inicial alfabético, sin distancias.
- [x] Geolocalización solo tras clic explícito.
- [x] Distancia Haversine únicamente con coordenadas válidas.
- [x] Geocodificación manual y fallback alfabético.
- [x] Responsive y accesibilidad básica completados.
- [x] Pint, JavaScript, Blade cache y build PASS.
- [x] Tests PASS: `14` tests / `130` assertions.
- [x] Capturas finales desktop/móvil generadas.

### Pausa

- [x] Disponible en `http://localhost:8010/`.
- [x] Sin commit, push ni deploy.
- [ ] Revisión conjunta con JM/Gala.
- [ ] QA manual de ubicación y contactos.
- [ ] Ajustes acordados.
- [ ] Repetir pruebas, build y capturas.
- [ ] Pedir autorización antes de commit/push.

### Orquestación

- [x] `qwen3.5:9b` instalado en Ollama.
- [x] Disponibles DeepSeek, Mistral, Gemma, Qwen 3.5 y Qwen 2.5 Coder.
- [ ] Integrar Qwen 3.5 como worker o decidir sustitución tras benchmark.
- [ ] DeepSeek: backend/tests.
- [ ] Mistral: frontend/responsive/accesibilidad.
- [ ] Gemma: auditoría/regresiones.
- [ ] Qwen: segunda revisión/simplificación.
- [ ] Agente principal: contexto, merge y validación.
- [ ] Máximo seis rutas por worker; evitar trabajo duplicado.

