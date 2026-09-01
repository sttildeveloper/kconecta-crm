# Contrato API movil: perfiles de proveedor

Este documento separa la cuenta personal de la ficha comercial publica. Todas
las rutas autenticadas usan `auth:sanctum` y el envelope JSON habitual:
`success`, `data`, `meta`, `message` y `errors`.

## Perfil personal

- Lectura: `GET /api/me`.
- Actualizacion parcial JSON: `PATCH /api/me`.
- Actualizacion con foto: `POST /api/me`, `multipart/form-data` y
  `_method=PATCH`.
- Campos: `first_name`, `last_name`, `email`, `phone`, `landline_phone`,
  `document_type`, `document_number`, `address`, `password` y `photo`.
- Foto: JPG, JPEG, PNG o WEBP; maximo 2 MB. Se recorta al centro, se redimensiona
  a 350 x 350 y se guarda como WEBP.
- `data.photo_path` contiene la ruta relativa completa
  (`img/photo_profile/...webp`) y `data.photo_url` la URL absoluta.
- Cambiar el correo elimina la verificacion previa. Omitir `password` conserva
  el hash actual. Ninguna respuesta serializa el hash.

Los campos comerciales enviados a `/api/me` se ignoran y no modifican la ficha
publica.

## Ficha comercial

- Lectura: `GET /api/agent/provider-profile`.
- Actualizacion parcial JSON: `PATCH /api/agent/provider-profile`.
- Actualizacion con archivos: `POST /api/agent/provider-profile`,
  `multipart/form-data` y `_method=PATCH`.
- Solo admite usuarios con rol proveedor; devuelve 401 sin sesion y 403 para
  otros roles.

Campos de texto: `title`, `description`, `availability`, `page_url`, `phone`,
`landline_phone`, `address`, `city`, `province`, `postal_code`, `country`,
`latitude` y `longitude`. Los telefonos y la direccion comerciales se guardan
separados de los datos personales. El mapa y el detalle publico no usan
`users.address` como fallback.

### Especialidades

El campo canonico es `specialty_ids`. En JSON se envia como array. En multipart
se envia como texto JSON, por ejemplo `specialty_ids=[23,27]`; `[]` elimina
todas las especialidades. Los alias `service_type_ids` y `service_type` se
mantienen para clientes antiguos.

### Portada y video

- Portada: `cover_image`, JPG/JPEG/PNG/WEBP, maximo 5 MB. Se convierte a WEBP
  y se limita a 1920 px en su lado mayor.
- Video: `video`, MP4/MOV/AVI/MPEG/MPG, maximo 50 MB.
- Un PATCH que omite estos campos conserva el archivo actual.
- La respuesta devuelve `cover_image_path`, `cover_image_url`, `video_path` y
  `video_url`.

### Galeria

- Maximo: `data.gallery_max_images`, actualmente 5. La portada no cuenta.
- Subida canonica multipart: `gallery_images[]`.
- Alias de subida conservado: `more_images[]`.
- Formatos: JPG/JPEG/PNG/WEBP, maximo 5 MB por imagen. Cada archivo se convierte
  a WEBP y se limita a 1920 px en su lado mayor.
- Borrado: `gallery_delete_ids` como array JSON o texto JSON multipart
  (`"[10,11]"`). Alias conservado: `delete_more_images`.
- Orden: `gallery_order`, con todos los IDs finales exactamente una vez. En
  multipart se envia como texto JSON. La ordenacion se realiza en una peticion
  separada de una subida para evitar IDs nuevos desconocidos por el cliente.
- El borrado solo acepta IDs propios, resuelve la ruta desde la base de datos y
  elimina registro y archivo. Un ID ajeno o inexistente produce 422 y no borra
  nada.
- Una subida conserva fotos reales existentes. Las fotos genericas marcadas
  `is_provider_default` se reemplazan al subir la primera foto real.

La representacion canonica de lectura es:

```json
{
  "gallery": [
    {
      "id": 10,
      "path": "img/uploads/example.webp",
      "file": "example.webp",
      "url": "https://kconecta.com/img/uploads/example.webp",
      "position": 0
    }
  ]
}
```

`more_images` se mantiene como alias temporal con los mismos elementos. `file`
es un alias legacy; el codigo nuevo debe usar `path` y `url`.

## Ejemplo multipart completo

```text
POST /api/agent/provider-profile
Authorization: Bearer <token>
Content-Type: multipart/form-data

_method=PATCH
title=Electricidad Norte
phone=600111222
specialty_ids=[23,27]
gallery_delete_ids=[10]
cover_image=<archivo>
gallery_images[]=<archivo>
video=<archivo>
```

Para reordenar posteriormente:

```json
PATCH /api/agent/provider-profile
{ "gallery_order": [14, 12, 13] }
```

Las validaciones devuelven HTTP 422 y mensajes agrupados por campo dentro de
`errors`. En clientes legacy, un error de `more_images[]` aparece tanto en
`errors.gallery_images` como en `errors.more_images`.

## Compatibilidad legacy

Se conservan temporalmente:

- `GET|PATCH /api/agent/services/profile`;
- operaciones legacy de `/api/agent/services` que delegan en la ficha unica;
- `service_type_ids` y `service_type` para especialidades;
- `more_images[]`, `delete_more_images`, `more_images` y `file`.

El codigo movil nuevo debe usar las rutas y nombres canonicos descritos arriba.
