# Kconecta CRM - Tasks

## Contexto
- Proyecto migrado de DameloDamelo a Kconecta.
- Stack principal: Laravel + MySQL + Docker Compose.
- Objetivo inmediato: publicar/sincronizar repositorio en GitHub como `kconecta-crm`.
- Objetivo siguiente: desplegar y sincronizar en Dokploy (Hostinger).

## Estado Actual
- [x] Docker funcional en local (`kconecta`, `kconecta-mysql-1`).
- [x] Branding principal migrado a Kconecta.
- [x] Import SQL base en contenedor MySQL.
- [x] Fix de columna `user.password` a `VARCHAR(255)`.
- [x] Login validado tras fix de esquema.

## Pendiente - GitHub
- [ ] Crear repo `kconecta-crm` en la cuenta GitHub.
- [ ] Vincular remoto `origin` al nuevo repo.
- [ ] Subir rama principal y validar visibilidad.
- [ ] Definir estrategia de ramas (`main`, `develop`, feature branches).

## Pendiente - Dokploy (Hostinger)
- [ ] Preparar server y proyecto en Dokploy.
- [ ] Configurar despliegue desde repo GitHub.
- [ ] Cargar variables de entorno de producción (sin secretos hardcodeados).
- [ ] Configurar base de datos de producción y migraciones.
- [ ] Configurar dominio, SSL y health checks.
- [ ] Validar deploy inicial y rollback básico.

## Seguridad Prioritaria
- [ ] Rotar secretos (`APP_KEY`, API keys, credenciales DB).
- [ ] Eliminar contraseñas por defecto y forzar cambio en usuarios administrativos.
- [ ] Desactivar fallback de login con password en texto plano (legacy).
- [ ] Revisar `.gitignore` para evitar subir secretos reales.

## Nota Operativa
- No sobrescribir cambios del usuario sin confirmación explícita.
- Registrar en este archivo cada avance relevante de infraestructura/deploy.
