# Multimedia generica por tipo de servicio

Fecha de validacion local: 2026-08-29

## Objetivo

Asignar a cada proveedor copias independientes de las imagenes genericas que
corresponden a sus especialidades. Las fuentes WebP se encuentran en:

```text
public/img/service-types/WEBP
```

Cada nombre comienza por el `service_type.id`, por ejemplo:

```text
27-fontaneria.webp
```

## Regla de asignacion

- Las fotos reales del proveedor nunca se reemplazan.
- Si no existe una portada real, la especialidad con menor ID se usa como portada.
- La portada es independiente y no cuenta dentro del limite de la galeria.
- Si la galeria real esta vacia, se copia una imagen por especialidad, incluida
  la especialidad representada en la portada, con un maximo de cinco imagenes.
- Si existen mas de cinco especialidades, se usan las cinco con menor ID.
- Si existe alguna imagen real de galeria, no se agregan genericas a esa galeria.
- Los proveedores sin especialidades no reciben galeria porque no existe una
  correspondencia fiable; una ejecucion completa retira sus galerias genericas
  anteriores. Su portada usa la imagen general de servicios `public/img/hero-bg.webp`.
- Cada copia se almacena en `public/img/uploads/providers/{provider_id}/`.
- La base guarda la ruta relativa `providers/{provider_id}/{archivo}.webp`.
- Las filas genericas usan `provider_user_id`, `is_provider_default = 1`,
  `source_provider_user_id = null` y `service_id = null`.

Las copias independientes permiten que el proveedor sustituya o elimine su
multimedia sin modificar la imagen fuente ni la ficha de otro proveedor.

## Limite compartido de galeria

El limite canonico se define en `config/uploads.php` como
`provider_gallery_max_images = 5` y se aplica sobre el total final de la galeria:

```text
existentes - eliminadas - genericas sustituidas + nuevas <= 5
```

- La edicion web valida el limite antes de modificar datos o archivos y muestra
  el maximo en el formulario.
- La API movil canonica y sus aliases legacy devuelven HTTP 422 si el total
  proyectado supera cinco.
- La respuesta del perfil API publica `gallery_max_images` para que el cliente
  movil pueda reproducir la misma restriccion visual.
- Al subir al menos una foto real, las fotos genericas de muestra se retiran;
  las fotos reales existentes se conservan salvo que el proveedor marque su
  eliminacion.
- El comando de poblacion nunca genera mas de cinco fotos de galeria.

## Comando

Simulacion, sin escrituras:

```bash
php artisan providers:backfill-service-type-media
```

Aplicacion:

```bash
php artisan providers:backfill-service-type-media --apply
```

Aplicacion exclusiva de portadas, sin modificar galerias:

```bash
php artisan providers:backfill-service-type-media --covers-only --apply
```

El comando es idempotente, comprueba hashes de copias preexistentes, conserva
contenido real y revierte las filas y archivos nuevos si ocurre una excepcion
antes de confirmar la transaccion.

## Resultado local de la ejecucion anterior

Este bloque conserva el registro de la poblacion realizada antes de adoptar la
regla actual de una imagen de galeria por especialidad. No describe la simulacion
pendiente indicada al final del documento.

- proveedores: 2.902
- proveedores con especialidades: 2.895
- proveedores sin especialidades y sin cambios: 7
- proveedores sin imagen fuente para alguna especialidad: 0
- portadas reales preservadas: 3
- galerias reales preservadas: 2 proveedores / 7 imagenes
- portadas genericas nuevas: 2.892
- imagenes genericas nuevas de galeria: 97
- copias nuevas: 2.989
- multimedia generica anterior retirada: 17.357 archivos
- espacio de las copias nuevas: 245,56 MiB

Los 7 proveedores sin especialidades conservan sus 42 archivos genericos
anteriores (una portada y cinco imagenes por proveedor) hasta que se les asigne
una especialidad valida.

## Respaldo local previo

```text
backups/20260829_170118_pre_service_type_media_local/db_local.sql.gz
```

- bytes: `710911`
- SHA-256: `69419A58BE1D5343B2AF22326F7A57B7A2B3A8F76CBF3D101E15C6EBC9E0889E`

Las copias genericas anteriores se pueden regenerar desde el comando historico
`providers:backfill-default-media` tras restaurar el respaldo de base de datos.

## Validacion

- segunda simulacion: 0 proveedores con cambios
- rutas genericas nuevas en base de datos: 2.989
- archivos fisicos nuevos: 2.989
- rutas duplicadas: 0
- archivos ausentes: 0
- nombres invalidos: 0
- hashes distintos de la fuente: 0
- archivos huerfanos: 0
- proveedores con especialidades sin portada: 0
- asociaciones simultaneas `provider_user_id + service_id`: 0
- fichas 68, 90 y 101: HTTP 200
- archivos de portada y galeria verificados: HTTP 200
- regresion focal: 17 pruebas / 128 aserciones
- suite completa: 162 pruebas / 1.542 aserciones

## Produccion

La fase exclusiva de portadas se aplico en produccion el 2026-08-29 mediante:

```bash
php artisan providers:backfill-service-type-media --covers-only --apply
```

Respaldo previo verificado:

```text
/root/kconecta_backups/20260829_202944_pre_service_type_covers
```

- DB: `05de0b33dba5b610b2ed467b342ba5b9184c0e2bff4f9c11cc2967863c590e97`
- uploads: `b008f3ca128fd439eccce5b5d09d244706f28f9628d07d7eef5b4f07008fd8c5`
- espacio libre previo: 73 GiB
- proveedores: 4.552
- portadas reales preservadas: 4
- portadas genericas creadas: 4.548
- proveedores sin especialidad con portada general: 8
- galerias creadas o modificadas: 0
- segunda simulacion: 0 cambios pendientes
- proveedores con portada: 4.552 de 4.552
- archivos fisicos genericos: 4.548
- archivos ausentes, hashes incorrectos o huerfanos: 0
- asociaciones simultaneas `provider_user_id + service_id`: 0
- fichas e imagenes 71, 90, 101 y 4628: HTTP 200

La fase de galerias sigue pendiente y requiere una autorizacion separada. La
implementacion del limite se preparo localmente, pero todavia no se ha desplegado
ni se ha ejecutado el poblador de galerias en produccion.

## Validacion de la nueva regla

- regresion focal: 20 pruebas / 134 aserciones
- suite completa: 169 pruebas / 1.591 aserciones
- simulacion local del poblador: 2.902 proveedores, 0 fuentes faltantes y
  2.899 proveedores con cambios pendientes
- la simulacion fue solo lectura; no se aplicaron cambios locales ni de produccion
