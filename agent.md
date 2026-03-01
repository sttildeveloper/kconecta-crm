# AGENT.md - Operación del Agente para Kconecta CRM

## Objetivo
Mantener y evolucionar `kconecta-crm` con foco en:
- estabilidad local con Docker,
- sincronización con GitHub,
- despliegue continuo en Dokploy (Hostinger),
- seguridad de credenciales y datos.

## Reglas de Trabajo
- Priorizar cambios reproducibles y verificables.
- No usar credenciales hardcodeadas en código.
- Mantener `.env` fuera del repositorio remoto.
- Documentar cualquier cambio de infraestructura en `tasks.md`.
- Antes de tocar despliegue, validar localmente (`docker compose`, login, rutas críticas).

## Flujo Recomendado
1. Revisar `tasks.md` y elegir tarea prioritaria.
2. Implementar cambio mínimo necesario.
3. Validar con comandos y/o pruebas rápidas.
4. Actualizar `tasks.md` con estado real.
5. Preparar commit claro y atómico.

## Checklist de Deploy Dokploy
- Repo GitHub accesible y actualizado.
- Variables de entorno cargadas en Dokploy.
- DB de destino creada y accesible.
- Migraciones ejecutadas sin error.
- App accesible por dominio con SSL.
- Logs limpios en arranque y login.

## Riesgos Conocidos
- Dumps legacy pueden romper esquema esperado de Laravel (ej. tabla `migrations`).
- Datos importados pueden incluir contraseñas sin hash.
- Cambios de branding pueden dejar referencias residuales si no se audita globalmente.
