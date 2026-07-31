# Punto de recuperación para agentes locales

## Checkpoint activo: 2026-07-26

El objetivo activo es continuar la revisión y acabado del nuevo home público de servicios y profesionales de Kconecta.

### Estado

- Implementación completa en local.
- URL: `http://localhost:8010/`.
- Sin commit, push ni despliegue.
- Docker local activo.
- Validación focal: `14` tests / `130` assertions.
- Build, Pint, Blade cache y JavaScript correctos.
- Capturas finales desktop/móvil disponibles en `screenshots/`.

### Trabajo realizado

- Home inmobiliario sustituido por búsqueda de profesionales.
- Datos reales desde `service_type`, `user`, `user_address`, `provider_services` y `service_provider_ratings`.
- Directorio alfabético inicial sin distancias.
- Geolocalización solo tras clic explícito.
- Proximidad únicamente con coordenadas válidas.
- Fallback manual y alfabético.
- Responsive y accesibilidad básica.
- Sin referencias visibles a inmuebles, enfermería o sanidad.

### Próxima acción

1. Revisar el home con JM/Gala.
2. Probar ubicación autorizada, rechazada y manual.
3. Verificar selector, contactos y fichas con datos reales.
4. Aplicar ajustes acordados.
5. Repetir pruebas, build y capturas.
6. No hacer commit/push sin autorización expresa.

## Orquestación requerida

- DeepSeek: backend, queries y tests.
- Mistral: Blade, CSS, responsive y accesibilidad.
- Gemma: nulos, regresiones y reglas canónicas.
- Qwen 3.5: segunda revisión y simplificación.
- Agente principal: contexto reducido, integración y validación.

`qwen3.5:9b` está instalado en Ollama, pero todavía no está configurado como worker en el orquestador Laravel. Integrarlo o decidir su sustitución tras una prueba comparativa.

Mantener un máximo de seis rutas por worker y evitar que varios modelos repitan la misma inspección.
