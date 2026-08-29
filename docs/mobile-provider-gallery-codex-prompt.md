# Prompt para Codex de la app movil

Actualiza la gestion de la galeria del proveedor para respetar el contrato de
la API del CRM:

- La portada es independiente y no cuenta dentro de la galeria.
- La galeria admite como maximo el valor `gallery_max_images` recibido desde
  `GET /api/agent/provider-profile`; actualmente es 5.
- Calcula el total proyectado como imagenes existentes no eliminadas mas
  imagenes nuevas.
- Permite eliminar una imagen y agregar otra en la misma operacion cuando ya
  existen cinco.
- Impide seleccionar o enviar imagenes si el total proyectado supera el maximo.
- Muestra un mensaje claro: `La galeria admite un maximo de 5 imagenes.`
- Al subir fotos propias, considera que la API sustituye las imagenes genericas
  de muestra, pero conserva las fotos reales no marcadas para eliminar.
- Maneja la respuesta HTTP `422` y muestra el primer mensaje disponible en
  `errors.more_images`.
- Verifica mediante pruebas los flujos de agregar, eliminar, sustituir y guardar
  sin imagenes nuevas.
- No incluyas la portada dentro del contador ni reutilices su estado como parte
  de la galeria.

Usa el valor enviado por la API como fuente de verdad y conserva un fallback
local de 5 solo para versiones del backend que aun no incluyan
`gallery_max_images`.
