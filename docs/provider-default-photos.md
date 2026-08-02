# Fotos predeterminadas de proveedores

## Estado productivo validado el 2026-08-02

Todos los usuarios de tipo proveedor de servicios (`user_level_id = 4`) deben tener
una foto de perfil. Cuando el proveedor aun no ha subido una imagen propia, se usa
temporalmente el logo oficial de Kconecta.

Auditoria inicial:

- Proveedores: 2.903.
- Con foto de perfil: 1.
- Sin foto de perfil: 2.902.

Estado final:

- Proveedores con foto: 2.903.
- Proveedores sin foto: 0.
- Fotos predeterminadas Kconecta: 2.902.
- Fotos propias preservadas: 1.

## Estrategia de archivos

No se asigna un unico archivo compartido. La API elimina la foto anterior cuando un
proveedor sube un logo nuevo; compartir un archivo permitiria que la primera
actualizacion rompiera la foto de todos los demas.

Cada proveedor sin foto recibe una copia individual en el volumen persistente
`public/img/photo_profile` con este patron:

```text
provider_{id}_kconecta_default.webp
```

Origen oficial:

```text
public/img/kconecta_icon.webp
```

SHA-256 del logo aplicado:

```text
f52d89aceae91656dc0f20112053e9c063f56486af248850adc5270697c7555a
```

Esto permite que cada proveedor sustituya posteriormente su copia sin afectar al
resto. La foto real existente no fue sobrescrita.

## Comando versionado

Simulacion de solo lectura:

```bash
php artisan providers:backfill-default-photos
```

Aplicacion explicita:

```bash
php artisan providers:backfill-default-photos --apply
```

El comando:

- selecciona solo proveedores con `photo` nulo o vacio;
- usa nombres deterministas e individuales;
- valida que un archivo preexistente tenga el hash oficial;
- actualiza la BD dentro de una transaccion;
- elimina las copias creadas si la transaccion falla;
- puede repetirse sin duplicar ni sobrescribir fotos propias.

Implementacion: `app/Console/Commands/BackfillProviderDefaultPhotos.php`.

## Backups previos

Antes del `--apply` se respaldaron por separado la base y el volumen de fotos:

```text
/root/kconecta_backups/20260802_1449_pre_provider_default_photos/
```

Archivos:

- `db_production.sql.gz`: 572.092 bytes.
- `photo_profile.tar.gz`: 24.146 bytes.
- `SHA256SUMS`.

Checksums:

```text
ab9dacf86e480064b441242413c47f103e6fda213709c8523b117dd4d6c0c487  db_production.sql.gz
668ed688aeff9417e4b2387eb7747586eeb113f26afbcacd80761882ecfb5876  photo_profile.tar.gz
```

Existe una copia local ignorada por Git en
`backups/20260802_1449_pre_provider_default_photos/`. Los checksums remoto y local
coinciden.

## Resultado y validacion

Resultado del apply:

```text
2902 proveedores actualizados y 2902 fotos creadas
```

Comprobaciones posteriores:

- dry-run: 2.903 con foto y 0 sin foto;
- archivos predeterminados: 2.902;
- archivos con hash distinto al logo oficial: 0;
- foto real no predeterminada preservada: 1;
- uso final del volumen: aproximadamente 114 MB;
- ficha `/result_provider/2378`: HTTP `200`;
- imagen `provider_2378_kconecta_default.webp`: HTTP `200`, 38.594 bytes;
- logs recientes del contenedor: sin errores relacionados.

Cobertura automatizada:

- dry-run no escribe en BD ni crea archivos;
- apply solo completa proveedores sin foto;
- fotos existentes permanecen intactas;
- copia y logo oficial tienen el mismo SHA-256.

Suite completa previa al despliegue: 148 pruebas y 1.394 aserciones correctas.

## Rollback

No restaurar el dump completo si produccion recibio escrituras posteriores. Para un
rollback selectivo, poner la aplicacion en mantenimiento, respaldar nuevamente y:

1. limpiar `user.photo` solo cuando coincida con el patron
   `provider_{id}_kconecta_default.webp`;
2. borrar solo archivos `provider_*_kconecta_default.webp`;
3. preservar cualquier foto con otro nombre;
4. ejecutar el dry-run y comprobar que reaparezcan exactamente 2.902 pendientes.

El dump y el tar previos quedan como recuperacion integral de ultimo recurso.
