# Saneamiento geografico de proveedores

## Estado validado el 2026-08-01

El dump fresco de produccion se importo en la base local `kconecta_schema`. No se ha modificado produccion ni se ha realizado `push`.

Resultados para usuarios de nivel proveedor (`user_level_id = 4`):

- Proveedores: 2.903.
- Proveedores con coordenadas validas: 2.895.
- Perfiles incompletos sin coordenadas: 8.
- Proveedores geolocalizados con provincia vacia, `NULL` o igual a Espana: 0 despues del saneamiento.
- Ciudades geolocalizadas sin correspondencia: 0.

Los 8 perfiles incompletos se conservan sin inventar una ubicacion. Esta decision respeta el flujo de alta: un proveedor puede registrarse sin direccion, pero no aparece en resultados geograficos hasta completar su perfil.

## Implementacion

El comando `php artisan providers:sanitize-addresses` ejecuta una simulacion sin escribir. La opcion `--apply` actualiza dentro de una transaccion solamente los proveedores con ciudad y coordenadas validas.

La correspondencia determinista de 109 ciudades a provincias vive en `database/data/provider_city_provinces.json`. Si aparece una ciudad geolocalizada no contemplada, el comando falla antes de escribir para evitar asignaciones silenciosas o incorrectas.

Campos normalizados:

- `province`: provincia canonica.
- `state`: misma provincia canonica.
- `country`: `Espana` con ene Unicode.

## Evidencia local

El respaldo saneado se encuentra en el directorio local ignorado `backups/20260801_1653_pre_local_search_test/`:

- `db_local_sanitized.sql.gz`.
- `db_local_sanitized.sql.gz.sha256`.
- SHA-256: `d95e3d8b78e4ba293d5bda30b3c916635a220c4d20a7e8cf7362392072cc0e63`.

El dump se restauro en una base temporal y se comparo con el origen local. Ambas copias contienen 79 tablas, 2.903 proveedores, 2.895 proveedores geolocalizados, 8 incompletos y 0 provincias invalidas entre proveedores geolocalizados.

## Busqueda publica

La seleccion de Google entrega direccion, ciudad, provincia, latitud y longitud. Cuando existen ciudad o provincia, `/api/services_for_map` usa esos campos estructurados y no exige que el texto completo seleccionado aparezca literalmente en cada direccion del proveedor.

La prueba local `08029 Barcelona` con la especialidad usada durante QA paso de 0 a 215 resultados. El home envia `mode=2` para abrir la vista historica de mapa.

## Procedimiento seguro para produccion

No se debe reemplazar toda la base de produccion con este dump local, porque se perderian escrituras realizadas despues de su descarga.

1. Desplegar primero el comando y sus datos de correspondencia.
2. Crear un backup fresco de produccion y verificar su checksum.
3. Ejecutar `php artisan providers:sanitize-addresses` sin `--apply` dentro del contenedor de la aplicacion.
4. Confirmar que no existan ciudades sin resolver y que el total previsto coincida con la auditoria del momento.
5. Ejecutar `php artisan providers:sanitize-addresses --apply` solo con autorizacion expresa.
6. Repetir la simulacion; debe informar 0 direcciones pendientes.
7. Validar conteos, filtros, mapa y fichas publicas.

## Importador protegido

El importador CSV ya no deduce la provincia usando el ultimo fragmento de la direccion. Ahora aplica estas reglas:

1. Usa la correspondencia ciudad-provincia para ciudades catalogadas.
2. Acepta las columnas opcionales `provincia` o `province` para ciudades nuevas.
3. Rechaza `Espana` o `Spain` como valor de provincia.
4. Bloquea una fila con coordenadas si no puede resolver una provincia valida.
5. Conserva los datos geograficos existentes durante una actualizacion cuando el CSV no los reemplaza.

La regresion completa finalizo con 142 pruebas y 876 aserciones, todas correctas. Una simulacion posterior confirma 0 direcciones pendientes y el API publico devuelve 2.895 proveedores geolocalizados.

## Dry-run de produccion del 2026-08-02

Se creo un backup fresco antes de instalar o ejecutar el comando:

- VPS: `/root/kconecta_backups/20260802_1025_pre_provider_sanitize_dryrun/db_production.sql.gz`.
- Copia local: `backups/20260802_1025_pre_provider_sanitize_dryrun/db_production.sql.gz`.
- Tamano: 554.108 bytes.
- SHA-256: `e8a72eec0b5c14360bda89c858c32a652cc4edb651f22df23ce42f9dcaccf397`.
- `gzip -t`: correcto.
- Checksum remoto/local: coincidente.

Como `main` local contenia cuatro commits pendientes de publicacion, no se activo el autodeploy. Para evitar desplegar cambios fuera de alcance, se instalaron temporalmente solo el comando y su catalogo dentro del contenedor productivo activo. Sus hashes coinciden con los archivos locales y la sintaxis PHP fue validada.

Resultado de `php artisan providers:sanitize-addresses` en produccion, sin `--apply`:

- Proveedores: 2.903.
- Direcciones: 2.903.
- Perfiles incompletos no publicables: 8.
- Ciudades sin resolver: 0.
- Direcciones por normalizar: 2.895.

No se modificaron filas, no se reemplazo la base, no se hizo `push` y no se ejecuto `--apply`. La instalacion aislada desaparecera en el siguiente redeploy; el despliegue permanente debe realizarse posteriormente mediante el flujo versionado.

## Aplicacion productiva completada el 2026-08-02

El estado anterior corresponde al primer dry-run y queda conservado como evidencia
historica. Posteriormente se recibio autorizacion expresa y se completo el flujo:

1. Se publico el comando y el catalogo versionados en `main`.
2. Se creo un segundo backup fresco inmediatamente antes del `--apply`.
3. Se ejecuto una simulacion final: 2.895 pendientes y 0 ciudades sin resolver.
4. Se ejecuto `php artisan providers:sanitize-addresses --apply`.
5. Se normalizaron 2.895 direcciones dentro de la transaccion.
6. La simulacion posterior confirmo 0 pendientes y 0 ciudades sin resolver.

Backup previo a la escritura:

- VPS: `/root/kconecta_backups/20260802_1110_pre_provider_sanitize_apply/db_production.sql.gz`.
- Copia local: `backups/20260802_1110_pre_provider_sanitize_apply/db_production.sql.gz`.
- Tamano: 554.266 bytes.
- SHA-256: `27a76f55c1b128b00113d72621d4a8b07dd927bd56957f455ae7df6b08e80d7f`.
- Checksum remoto/local: coincidente.

Estado final productivo:

- Proveedores: 2.903.
- Direcciones: 2.903.
- Perfiles incompletos no publicables: 8.
- Ciudades sin resolver: 0.
- Direcciones por normalizar: 0.

Los ocho perfiles incompletos no son un error de saneamiento. Se conservan sin
ubicacion hasta que el proveedor complete su perfil y permanecen excluidos de la
busqueda geografica.

El importador endurecido y el comando ya forman parte del despliegue permanente.
No es necesario copiar archivos manualmente al contenedor en futuras ejecuciones.
