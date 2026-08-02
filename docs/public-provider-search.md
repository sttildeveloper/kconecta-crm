# Busqueda publica de proveedores

## Estado productivo validado el 2026-08-02

La busqueda publica permite que cualquier visitante encuentre, consulte y contacte
proveedores sin registrarse. El registro de cliente solo es obligatorio para dejar
una valoracion. No se creo una segunda pagina de resultados: el home continua usando
la interfaz historica `/result/services` y su endpoint de mapa
`/api/services_for_map`.

Estado confirmado:

- Home publico: `https://kconecta.com/`.
- Pagina de resultados: `GET /result/services`.
- Datos publicos del mapa: `GET /api/services_for_map`.
- Busqueda sin autenticacion: operativa.
- Contacto y ficha de proveedor: publicos.
- Valoracion: reservada a cliente final registrado y verificado.
- Commit desplegado: `52854d7` (`fix: restore location-based provider search`).

## Flujo de ubicacion

1. El visitante escribe un codigo postal o una direccion en el home.
2. Google Places muestra sugerencias restringidas a Espana.
3. Al seleccionar una sugerencia se conservan direccion, ciudad, provincia,
   latitud y longitud.
4. El home envia esos parametros a `/result/services` en modo mapa (`mode=2`).
5. Si Google entrega ciudad o provincia, el backend usa esos campos estructurados.
6. Si ciudad y provincia llegan vacias pero existen coordenadas validas, el backend
   busca proveedores dentro del radio configurado.
7. Solo cuando no existen datos estructurados ni coordenadas se usa el texto de
   direccion como fallback legacy.

El radio predeterminado es de 30 km y puede ajustarse mediante
`PROVIDER_SEARCH_RADIUS_KM`. La implementacion compartida vive en
`app/Services/ProviderLocationSearchService.php`, por lo que la lista y el endpoint
del mapa aplican la misma regla.

## Google Maps y Places

La web usa una clave separada de las claves nativas Android/iOS.

- Variable: `GOOGLE_MAPS_API_KEY`.
- Restriccion de aplicacion: `HTTP referrers`.
- Referrers autorizados: local (`localhost` y `127.0.0.1` en puerto 8010),
  `https://kconecta.com/*` y `https://www.kconecta.com/*`.
- APIs habilitadas: Maps JavaScript API, Geocoding API, Places API y Places API
  (New).

La clave es un secreto y nunca debe escribirse en Git, logs o documentacion. La
configuracion persistente se administra en el entorno de la aplicacion de Dokploy;
modificar solo el entorno efimero del contenedor no sobrevive a un redeploy.

Verificacion productiva realizada sin exponer la clave:

- la huella de la clave servida por el home coincide con la clave web esperada;
- Maps JavaScript API responde HTTP `200` sin errores conocidos de autorizacion;
- Places API (New) responde HTTP `200` y devuelve sugerencias para `08029`;
- el mapa conserva Leaflet + OpenStreetMap como fallback si Google falla.

## Resultados por coordenadas

Antes del fix, una URL con `address=08029`, coordenadas validas y ciudad/provincia
vacias terminaba comparando literalmente `08029` contra la direccion de cada
proveedor. Esto llevaba al mapa, pero producia cero o muy pocos resultados.

Desde `52854d7`:

- `PageController::resultAllServices()` filtra por proximidad cuando corresponde;
- `ApiController::dataServicesForMap()` usa exactamente la misma seleccion;
- el filtro de especialidad se conserva y se combina con la ubicacion;
- coordenadas invalidas no activan la busqueda geografica;
- el radio es configurable y no esta duplicado en controladores.

Validacion con datos de produccion:

- consulta `08029` sin especialidad: HTTP `200`, 2.005 proveedores en 30 km;
- consulta estructurada de Barcelona con la especialidad usada en QA: 215
  proveedores;
- proveedor lejano excluido en la prueba automatizada de regresion.

## Historial del navegador

El mapa actualizaba latitud, longitud y zoom mediante `history.pushState()` en cada
movimiento. Esto creaba multiples entradas de la misma pagina y hacia que el boton
Atrás pareciera no funcionar.

Ahora los cambios pasivos del mapa usan `history.replaceState()`. La URL conserva el
estado del mapa sin llenar el historial, por lo que Atrás regresa al home.

## Estado geografico de proveedores

El saneamiento productivo se ejecuto despues de un backup fresco y verificado:

- Proveedores: 2.903.
- Direcciones: 2.903.
- Direcciones normalizadas: 2.895.
- Ciudades sin resolver: 0.
- Direcciones pendientes: 0.
- Perfiles incompletos no publicables: 8.

Los ocho perfiles incompletos son compatibles con la regla de negocio: el proveedor
puede registrarse sin direccion, pero no aparece en busquedas geograficas hasta
completar una ubicacion con coordenadas. No se inventan coordenadas ni provincias.

Backup previo al `--apply`:

- VPS: `/root/kconecta_backups/20260802_1110_pre_provider_sanitize_apply/db_production.sql.gz`.
- Copia local ignorada por Git:
  `backups/20260802_1110_pre_provider_sanitize_apply/db_production.sql.gz`.
- SHA-256:
  `27a76f55c1b128b00113d72621d4a8b07dd927bd56957f455ae7df6b08e80d7f`.

No se reemplazo produccion con el dump local. Se ejecuto el comando transaccional
versionado y despues una nueva simulacion confirmo cero cambios pendientes.

## Pruebas y despliegue

- Suite completa: 146 pruebas, 1.382 aserciones, todas correctas.
- Autodeploy de Dokploy: completado.
- Contenedor nuevo: operativo y sin errores recientes.
- Git: `main` sincronizado con `origin/main` al cerrar la validacion.

Regresiones cubiertas:

- coordenadas sin ciudad/provincia encuentran proveedores cercanos;
- proveedores lejanos quedan excluidos;
- lista y mapa son consistentes;
- el mapa no agrega entradas de historial.

## Checklist de regresion

1. Probar sugerencias con direccion completa y codigo postal.
2. Probar seleccion de servicio vacia y con especialidad.
3. Confirmar que la URL lleva direccion y coordenadas.
4. Confirmar consistencia entre contador, lista y marcadores.
5. Probar `Usar mi ubicacion`, rechazo de permisos y fallback manual.
6. Probar Google Maps y forzar el fallback Leaflet.
7. Abrir resultados desde el home y comprobar el boton Atrás.
8. Ejecutar `PublicDiscoveryApiTest` y `HomePageTest`.
9. No registrar claves, dumps ni backups en Git.

## Incidente posterior al refactor de medios (2026-08-02)

Tras el autodeploy del commit `21c1d25`, `/api/services_for_map` respondia `500`
porque el codigo ya consultaba `cover_image.provider_user_id`, pero las tres
migraciones del refactor de medios seguian pendientes en produccion.

- Backup previo: `/root/kconecta_backups/20260802_2055_pre_provider_media_migrations/db_production.sql.gz`.
- SHA-256: `7ea1e9c0f8594a2fd72c82840fbdd3dbd46752247b22b2f5868847ded80b671c`.
- Migraciones `2026_08_02_170000`, `170100` y `170200` aplicadas y registradas
  manualmente en los lotes `16`, `17` y `18` por el esquema legacy de `migrations`.
- Cache de Laravel limpiada y endpoint validado con HTTP `200`.
- Auditoria: existen 1.522 proveedores geolocalizados con ciudad `Barcelona`.

Tambien se detecto que el home enviaba `sti[]=` al buscar sin especialidad. Ese
valor vacio se interpretaba como el tipo `0` y producia cero resultados. El backend
ahora ignora valores vacios, no numericos, cero y duplicados tanto en la pagina como
en la API. La regresion reproduce la URL del incidente y valida ambos flujos.

## Densidad y zoom del mapa

Las busquedas con direccion y coordenadas conservan el centro solicitado y aplican
un zoom minimo `13`; ya no se alejan automaticamente para encajar todos los
proveedores de la ciudad. Google Maps agrupa los marcadores cercanos en burbujas
KCONECTA con contador. Al pulsar una agrupacion, el mapa se acerca progresivamente;
los proveedores individuales mantienen el marcador de la marca y su ficha.

La agrupacion utiliza `@googlemaps/markerclusterer` con version fijada y conserva un
fallback a marcadores individuales si el recurso externo no esta disponible. El
fallback Leaflet mantiene igualmente el centro y zoom de la busqueda.
