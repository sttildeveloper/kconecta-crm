# Prompt para Codex de la app movil

## Edicion completa del perfil del proveedor

Implementa la edicion del proveedor separando el perfil personal de la ficha
comercial. No mezcles campos ni envies el mismo payload a ambos endpoints.

### Perfil personal y cuenta

- Carga los datos desde `GET /api/me`.
- Guarda cambios de texto mediante `PATCH /api/me` con actualizaciones
  parciales; no es necesario reenviar campos que no cambiaron.
- Campos admitidos: `first_name`, `last_name`, `email`, `phone`,
  `landline_phone`, `document_type`, `document_number`, `address` y
  `password`.
- La contrasena es opcional, nunca debe precargarse ni mostrarse desde la
  respuesta y solo se envia cuando el usuario introduce una nueva.
- Para actualizar la foto usa el campo canonico `photo`. Debido al tratamiento
  de multipart en PHP 8.2, envia `POST /api/me` con `multipart/form-data` y el
  campo `_method=PATCH`; para cambios sin archivo se puede usar `PATCH` JSON.
- Formatos de foto admitidos: JPG, JPEG, PNG y WEBP, con maximo 2 MB. El backend
  la convierte a WEBP cuadrado de 350 x 350.
- Tras guardar, actualiza el estado local con `data.user`, `data.photo_path`,
  `data.photo_url` y `data.email_verified` de la respuesta.
- Si cambia el correo, el backend devuelve `email_verified=false`; refleja ese
  estado y conduce al usuario al flujo de verificacion cuando corresponda.
- Maneja `401` cerrando o renovando la sesion segun el flujo actual y maneja
  `422` mostrando los mensajes por campo recibidos en `errors`.
- Este formulario no debe enviar `provider_title`, `provider_description`,
  `provider_availability`, `provider_page_url`, especialidades, portada,
  galeria ni video.

### Ficha comercial publica

- Carga y guarda la ficha comercial mediante `GET` y
  `PATCH /api/agent/provider-profile`.
- Cuando haya archivos, envia `POST /api/agent/provider-profile` como multipart
  con `_method=PATCH`.
- Este endpoint gestiona titulo y descripcion comercial, disponibilidad, web,
  telefonos comerciales, ubicacion publica, especialidades, portada, galeria y
  video.
- La direccion que se publica y se usa para busqueda/mapa debe actualizarse en
  este endpoint con sus campos estructurados y coordenadas; no la sustituyas
  enviando solamente el `address` personal de `/api/me`.
- Conserva compatibilidad con los aliases actuales solo si la app ya los usa,
  pero utiliza los dos endpoints canonicos anteriores para codigo nuevo.

### Pruebas moviles requeridas

- Lectura y actualizacion parcial del perfil personal.
- Validaciones por campo y correo duplicado.
- Cambio de correo y estado de verificacion.
- Cambio opcional de contrasena sin exponerla posteriormente.
- Subida y reemplazo de foto mediante multipart y `_method=PATCH`.
- Edicion comercial sin modificar datos personales.
- Edicion personal sin modificar especialidades, portada, galeria o video.

## Galeria del proveedor

Actualiza la gestion de la galeria del proveedor para respetar el contrato de
la API del CRM:

- La portada es independiente y no cuenta dentro de la galeria.
- La galeria admite como maximo el valor `gallery_max_images` recibido desde
  `GET /api/agent/provider-profile`; actualmente es 5.
- Lee la galeria desde `data.gallery`. Cada elemento contiene `id`, `path`,
  `url` y `position`.
- Para nuevas imagenes usa el campo canonico multipart `gallery_images[]`.
- Para eliminar envia `gallery_delete_ids` y para ordenar envia
  `gallery_order`. En multipart, ambos deben ser strings JSON, por ejemplo
  `gallery_delete_ids=[10,11]`.
- `gallery_order` debe contener todos los IDs finales y se envia en una
  peticion separada de la subida de nuevas imagenes.
- Calcula el total proyectado como imagenes existentes no eliminadas mas
  imagenes nuevas.
- Permite eliminar una imagen y agregar otra en la misma operacion cuando ya
  existen cinco.
- Impide seleccionar o enviar imagenes si el total proyectado supera el maximo.
- Muestra un mensaje claro: `La galeria admite un maximo de 5 imagenes.`
- Al subir fotos propias, considera que la API sustituye las imagenes genericas
  de muestra, pero conserva las fotos reales no marcadas para eliminar.
- Maneja la respuesta HTTP `422` y muestra el primer mensaje disponible en
  `errors.gallery_images`; usa `errors.more_images` como fallback legacy.
- Verifica mediante pruebas los flujos de agregar, eliminar, sustituir y guardar
  sin imagenes nuevas.
- No incluyas la portada dentro del contador ni reutilices su estado como parte
  de la galeria.

Usa el valor enviado por la API como fuente de verdad y conserva un fallback
local de 5 solo para versiones del backend que aun no incluyan
`gallery_max_images`.

El contrato completo, incluidos aliases y ejemplos, esta documentado en
`docs/mobile-provider-profile-api-contract.md`.
