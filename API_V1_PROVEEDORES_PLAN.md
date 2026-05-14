### Estado
- Etapa 1 (Autenticacion y acceso): **CERRADA** el 2026-05-14.
- Evidencia automatizada:
- Tests\Feature\Api\AuthApiTest: login valido retorna Bearer token usable contra endpoint protegido (/api/me).
- Tests\Feature\Api\ProviderServicesApiTest: cobertura de 401 sin token y 403 para rol no proveedor, ademas de ownership en acceso a servicios.
- Etapa 2 (validaciones y matriz 403/404): **CERRADA** el 2026-05-14.
- Etapa 3 (contrato JSON v1 uniforme): **CERRADA** el 2026-05-14.
- Etapa 4 (media multipart create/update): **CERRADA** el 2026-05-14.
- Etapa 5 (documentacion + trazabilidad QA): **CERRADA** el 2026-05-14.

### Checklist QA de Cierre (2026-05-14)
- [x] Auth login/token usable validado (AuthApiTest).
- [x] 401 sin token validado para rutas protegidas.
- [x] 403 por rol no proveedor validado en CRUD.
- [x] Ownership estricto y 404 por recurso inexistente/ajeno validado.
- [x] 422 por validacion de campos, tipo y tamano de media validado.
- [x] Contrato JSON v1 (success, data, meta, message, errors) validado en respuestas de exito y error del controlador.
- [x] Reemplazo de cover_image y video, alta y borrado selectivo de more_images validado.
- [x] Regresion minima endpoints legacy: /api/services, /api/services_for_map, y ratings en verde.

### Resumen
Definir y entregar una API v1 enfocada en proveedores con autenticacion `Sanctum Bearer`, alcance `CRUD + listados`, y subida de media por `multipart` en los mismos endpoints de creacion/edicion.
Objetivo: que la app nativa pueda operar el ciclo completo de servicios sin depender de rutas web.

### Cambios Clave de Implementacion
1. **Nuevo bloque API de servicios de proveedor (`/api/agent/services`)**
- `GET /api/agent/services`: listado paginado de servicios del proveedor autenticado.
- `POST /api/agent/services`: alta de servicio (campos funcionales + media multipart).
- `GET /api/agent/services/{id}`: detalle de servicio propio.
- `PUT/PATCH /api/agent/services/{id}`: edicion de servicio propio (incluye reemplazo/alta de media).
- `DELETE /api/agent/services/{id}`: baja logica/fisica segun comportamiento actual del sistema.

2. **Autorizacion y reglas de acceso**
- Requiere `auth:sanctum` en todo el bloque.
- Solo `Proveedor de servicios` puede crear/editar/eliminar sus servicios.
- Acceso por ownership estricto: un proveedor no puede operar servicios de otro.
- Respuestas de error estandarizadas (`401`, `403`, `404`, `422`).

3. **Contrato de datos v1 (estable para mobile)**
- Unificar payload/response JSON de servicios con estructura consistente (`success`, `data`, `meta`, `message`, `errors`).
- Mantener compatibilidad con catalogos y descubrimiento existentes (`/api/services`, `/api/services_for_map`) sin breaking changes.
- Definir validaciones minimas de v1 para alta/edicion (campos obligatorios, tipos, limites de media).

4. **Media multipart en create/update**
- Soportar subida de imagen/logo/video en `POST/PUT/PATCH`.
- Reusar validaciones backend existentes de tamano/formato.
- Incluir en respuesta URLs canonicas para consumo directo de la app.

5. **Documentacion y trazabilidad**
- Publicar especificacion funcional (tabla de endpoints, request/response, codigos de error).
- Agregar ejemplos de requests mobile-ready (login + create/update service).
- Registrar checklist de QA en `tasks.md` para cierre de entrega.
