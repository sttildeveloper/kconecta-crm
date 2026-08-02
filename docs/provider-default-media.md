# Multimedia genérica inicial para proveedores

Fecha de validación local: 2026-08-02

## Objetivo

Garantizar que todos los proveedores tengan inicialmente portada y galería, sin asociar multimedia a `service_id`. Reformas Buele (`user.id = 67`) se utiliza como fuente temporal aprobada.

## Implementación

Comando versionado:

```bash
php artisan providers:backfill-default-media --source-provider=67
php artisan providers:backfill-default-media --source-provider=67 --apply
```

El comando:

- funciona como simulación si no recibe `--apply`
- solo actúa sobre proveedores sin portada o sin galería
- crea archivos físicos independientes con nombre determinista
- comprueba SHA-256 si encuentra una copia preexistente
- asocia las filas mediante `provider_user_id`
- guarda `service_id = null`
- marca las copias con `is_provider_default = 1`
- registra el origen en `source_provider_user_id`
- es idempotente
- revierte filas y elimina archivos creados si ocurre una excepción controlada

Cuando el proveedor sube su primera galería propia, las imágenes marcadas como genéricas se eliminan. Al reemplazar portada, la misma fila pasa a contenido real y pierde la marca genérica.

## Ejecución local

Simulación previa:

- proveedores: 2.903
- sin portada: 2.900
- sin galería: 2.901
- imágenes fuente por galería: 5
- archivos requeridos: 17.405
- espacio estimado: 2,51 GiB

Resultado aplicado:

- portadas genéricas: 2.900
- imágenes genéricas de galería: 14.505
- proveedores con portada: 2.903 de 2.903
- proveedores con galería: 2.903 de 2.903
- proveedores pendientes: 0
- asociaciones simultáneas `provider_user_id + service_id`: 0
- archivos físicos: 17.405 nombres únicos
- bytes copiados: 2.692.824.546

La segunda ejecución con `--apply` produjo 0 cambios, confirmando idempotencia.

## Backup local

- archivo: `backups/20260802_205530_pre_provider_default_media_local/db_local.sql.gz`
- tamaño: 572.427 bytes
- SHA-256: `3BABEF4101E6207F77F3CCBF0F7A2BFC4B4FDD7393CED8E5B52462AAA7417008`

## Validación

- proveedor sin registro legacy probado: `user.id = 71`
- ficha: `http://localhost:8010/proveedor/71`
- portada y cinco imágenes presentes en HTML
- hashes de las seis copias coinciden con las fuentes
- suite completa: 138 pruebas, 1.292 aserciones

## Producción pendiente

Los 17.405 archivos locales están en una ruta de uploads ignorada por Git; no se enviarán mediante `push`. En producción se debe:

1. Crear backup de DB y de `/var/www/html/public/img/uploads`.
2. Desplegar código y ejecutar migraciones.
3. Ejecutar primero la simulación.
4. Verificar espacio libre y fuente `user.id = 67`.
5. Ejecutar el comando con `--apply` dentro del contenedor con una sesión que tolere una operación larga.
6. Repetir simulación y auditoría de conteos/archivos.
