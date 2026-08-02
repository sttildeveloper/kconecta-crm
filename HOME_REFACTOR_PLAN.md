# Plan de refactor del home público de Kconecta

Fecha de definición: 2026-07-31
Estado historico: implementado y validado en local el 2026-07-31.
Estado final: desplegado y validado en produccion el 2026-08-02.

Release final:

- busqueda conectada con `/result/services` y `/api/services_for_map`;
- Google Places web operativo con clave restringida por HTTP referrers;
- busqueda por proximidad cuando faltan ciudad/provincia y existen coordenadas;
- saneamiento geografico productivo completado;
- historial del mapa corregido para permitir regresar al home;
- commit funcional `52854d7`;
- 146 pruebas y 1.382 aserciones correctas;
- detalle operativo en `docs/public-provider-search.md`.

## 1. Objetivo de producto

Transformar el home público en la principal puerta de entrada al marketplace de profesionales de Kconecta. Cualquier visitante podrá buscar proveedores, consultar su ficha y contactarlos sin registrarse.

El registro de cliente será opcional y solo se exigirá para dejar una valoración. El registro de proveedor continuará bajo control de verificación por correo electrónico.

## 2. Reglas funcionales

### Visitantes y clientes

- La búsqueda de proveedores, el acceso a fichas y el contacto serán públicos.
- No se solicitará registro para buscar o contactar.
- Para valorar a un proveedor se exigirá una cuenta de cliente final autenticada y con correo verificado.

### Proveedores

- El proveedor podrá crear su cuenta sin dirección ni coordenadas.
- Antes de validar el correo no podrá acceder al panel protegido.
- Tras validar el correo podrá gestionar su ficha, especialidades, contacto, contenido multimedia y ubicación.
- La dirección se capturará mediante una selección válida del proveedor de mapas para guardar dirección normalizada, ciudad, código postal, latitud y longitud.
- La dirección exacta no se mostrará como dato residencial público; el home y las fichas mostrarán ciudad, distrito o zona de trabajo.
- Sin coordenadas, el proveedor podrá completar el resto del perfil, pero no tendrá participación completa en búsquedas por proximidad ni en el mapa.
- El panel deberá comunicar esta situación con un aviso del tipo: `Completa tu ubicación para aparecer en las búsquedas por cercanía`.

## 3. Estructura del nuevo home

El orden de las secciones seguirá la propuesta visual aprobada:

1. Barra informativa y navegación principal.
2. Hero con búsqueda por ubicación y tipo de servicio.
3. Servicios más buscados.
4. Explicación del proceso en tres pasos.
5. Reseñas o testimonios.
6. `Consejos para tu hogar`, alimentado por artículos reales del blog.
7. Captación y registro de proveedores.
8. Footer con navegación, enlaces legales y acceso a las aplicaciones.

## 4. Recursos visuales

Los siguientes recursos ya están migrados a `public/img`:

- `hero-bg.webp`: imagen principal del hero.
- `img-review-1.webp`: imagen de la primera reseña.
- `img-review-2.webp`: imagen de la segunda reseña.
- `img-review-3.webp`: imagen de la tercera reseña.

La implementación deberá incluir dimensiones explícitas, `loading="lazy"` fuera del hero, texto alternativo útil y recorte responsive mediante CSS.

## 5. Datos y comportamiento por sección

### Hero y búsqueda

- Reutilizar el catálogo real `service_type` y la ruta pública `/result/services`.
- Permitir ubicación manual y uso explícito de geolocalización.
- No solicitar permisos de ubicación al cargar la página.
- Mantener fallback seguro cuando el visitante rechace el permiso o la geocodificación falle.
- Usar `hero-bg.webp` como imagen visual del hero.

### Servicios más buscados

- Mostrar tres categorías configuradas a partir del catálogo real.
- Cada tarjeta enlazará a resultados filtrados por su `service_type`.
- Mantener una ruta visible hacia el catálogo completo.

### Cómo funciona

- Contenido estático en tres pasos: buscar, comparar y contactar.
- Sin dependencia adicional de backend.

### Reseñas

- Mostrar tres tarjetas con `img-review-1.webp`, `img-review-2.webp` e `img-review-3.webp`.
- El modelo actual de valoraciones guarda estrellas, pero no comentarios de texto.
- Los nombres, ciudades y testimonios del mockup deberán considerarse contenido editorial hasta que negocio confirme que corresponden a testimonios reales.
- No presentar contenido ficticio bajo la etiqueta `Historias reales` sin aprobación expresa.

### Consejos para tu hogar

- Consultar exclusivamente artículos con `status = 1`.
- Ordenar por `created_at DESC` y, como desempate estable, `id DESC`.
- Limitar el home a los tres artículos publicados más recientes.
- Cada tarjeta mostrará imagen destacada, título, resumen y enlace a `/blogs/{slug}`.
- Si hay menos de tres artículos, mostrar solo los disponibles.
- Si falta imagen destacada, utilizar el fallback público ya usado por el blog.
- El botón `Ver todos los consejos` enlazará a `/blogs`.
- El listado público del blog deberá conservar el mismo orden cronológico.

Consulta prevista:

```php
BlogPost::query()
    ->where('status', 1)
    ->orderByDesc('created_at')
    ->orderByDesc('id')
    ->limit(3)
    ->get();
```

### Registro de proveedores

- El CTA principal dirigirá a `/quiero-ser-proveedor`.
- El formulario integrado o enlazado reutilizará `route('register')` y forzará el rol de proveedor.
- No se añadirán dirección, latitud o longitud como requisitos del alta inicial.
- Se preservarán la verificación de correo, protección antispam y validación de identidad duplicada existentes.
- El acceso a la gestión de la ficha permanecerá protegido por `provider_or_agent_verified`.

## 6. Plan técnico

### Fase 1: datos del home

- Incorporar `BlogPost` en `PageController@index`.
- Preparar `homeArticles` con los tres últimos artículos publicados.
- Mantener `serviceTypes`, `featuredServices` y proveedores reales como fuentes de datos existentes.
- Definir fallbacks para imágenes, zonas y contenido vacío.

### Fase 2: estructura Blade

- Refactorizar `resources/views/page/index.blade.php` según el orden aprobado.
- Extraer parciales reutilizables para servicio, reseña y artículo cuando reduzcan complejidad.
- Actualizar `resources/views/layouts/home.blade.php` para navegación, anchors y footer.
- Mantener rutas públicas y protegidas actuales.

### Fase 3: presentación responsive

- Actualizar `public/css/page/home.css` con enfoque desktop y mobile.
- Integrar las cuatro imágenes WebP ya migradas.
- Mantener contraste, foco visible, navegación por teclado y jerarquía semántica.
- Evitar saltos de layout reservando el espacio de imágenes.

### Fase 4: interacción

- Adaptar `public/js/home.js` para navegación móvil y búsqueda.
- Mantener la geolocalización exclusivamente tras una acción explícita.
- Conservar el fallback alfabético y la búsqueda manual ante errores.

### Fase 5: pruebas y revisión local

- Ampliar `HomePageTest` para verificar:
  - render de las nuevas secciones;
  - uso de `hero-bg.webp`;
  - solo tres artículos publicados;
  - orden por `created_at` descendente;
  - exclusión de borradores;
  - enlaces a detalle y `/blogs`;
  - registro de proveedor sin dirección;
  - protección del panel antes de verificar el correo.
- Ejecutar pruebas focales de home, blog, registro y descubrimiento público.
- Validar sintaxis JavaScript, compilación de vistas y respuesta HTTP `200`.
- Revisar manualmente escritorio y móvil en `http://localhost:8010/`.
- Generar capturas locales para comparación con el diseño aprobado.

## 7. Criterios de aceptación

- Un visitante puede buscar, abrir una ficha y contactar sin iniciar sesión.
- El hero usa la nueva imagen y funciona en escritorio y móvil.
- Los tres servicios destacados llevan a resultados reales.
- Las tres tarjetas de reseñas utilizan las imágenes entregadas.
- El home muestra como máximo los tres artículos publicados más recientes y nunca muestra borradores.
- `Ver todos los consejos` abre el listado público del blog.
- Un proveedor puede registrarse sin dirección.
- Un proveedor no verificado no puede gestionar su ficha.
- Tras verificar su correo puede completar una ubicación y guardar coordenadas.
- Rechazar geolocalización no bloquea la navegación ni la búsqueda manual.
- No se introduce ninguna regresión en rutas de búsqueda, blog, registro o panel.

## 8. Fuera de alcance de este refactor

- Crear un módulo editorial de testimonios.
- Añadir comentarios de texto al sistema de valoraciones.
- Hacer obligatoria la dirección durante el registro inicial.
- Mostrar públicamente la dirección residencial exacta del proveedor.
- Modificar el modelo canónico de ficha única de proveedor.
- Ejecutar `commit`, `push` o despliegue sin autorización expresa.

## 9. Orden de ejecución

- [x] Preparar query y contrato de `homeArticles`.
- [x] Refactorizar Blade y layout.
- [x] Integrar estilos y recursos visuales.
- [x] Adaptar interacciones del home.
- [x] Ampliar pruebas automatizadas.
- [x] Levantar entorno local y ejecutar QA.
- [x] Presentar el resultado local para revisión de JM/Gala.
- [x] Aplicar ajustes de revisión técnica orquestada.
- [x] Autorizacion recibida; commit, push, autodeploy y verificacion completados.
