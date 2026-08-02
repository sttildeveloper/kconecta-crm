# 📋 Kanban de Desarrollo: KConecta CRM Mobile

Este es el centro de orquestación para la nueva App en **React Native** consumiendo el backend **Laravel 12**.

---

## 🏗️ Estado General del Proyecto
- **Backend:** Laravel 12 (Confirmado/Auditando - UI de 12 formularios globalizada)
- **App Móvil:** Pendiente de inicializar (Expo)
- **Infraestructura:** Git LFS Saneado (Media en .gitignore), Backups en VPS realizados (2026-04-16)

---

## 🗂️ TABLERO KANBAN

### 📥 Backlog (Futuro)
- [ ] Implementar Push Notifications (Firebase/Expo Notifications).
- [ ] Configurar CI/CD para despliegues automáticos en TestFlight/Google Play Store.
- [ ] **[Backend]** Centralizar botones de guardado como componente Blade o `@include` (Refactor Architectural).
- [ ] Añadir Modo Offline (Caché local con SQLite/AsynStorage).
- [ ] Integrar Analytics (Google Analytics o Mixpanel).

### 📋 Por Hacer (To Do)
- [ ] **[Backend]** Verificar configuración de CORS para peticiones desde la App Móvil.
- [ ] **[Backend]** Revisar endpoints de `ApiController.php` y asegurar devolución de JSON estandarizado.
- [ ] **[Backend]** Configurar Laravel Sanctum para autenticación por Token de larga duración.
- [ ] **[Mobile]** Inicializar proyecto Expo con Template de TypeScript + NativeWind (Tailwind).
- [ ] **[Mobile]** Crear estructura de carpetas (components, hooks, services, screens).
- [ ] **[Mobile]** Configurar Axios y TanStack Query para consumo de la API.

### ⏳ En Progreso (In Progress)
- [ ] **[Orquestación]** Auditoría inicial del código Laravel actual.
- [ ] **[Infraestructura]** Descarga y configuración de Modelos locales (Ollama).
- [ ] **[Infraestructura]** Instalación de Docker Desktop y configuración de **Plane**.

### ✅ Finalizado (Done)
- [x] **[Setup]** Clonar repositorio de GitHub.
- [x] **[Setup]** Confirmar versión de Laravel y arquitectura de rutas.
- [x] **[UI]** Estandarización global de clases `property-description-*` en 12 formularios (Creación y Update).
- [x] **[Infra]** Respaldo de BD y Media en VPS (Hostinger/Dokploy) y Saneamiento de Git LFS.
- [x] **[Setup]** Crear sistema de orquestura via Kanban Markdown.

---

## 🛠️ Especificación Técnica (Referencia Rápida)

### Endpoints Clave Identificados:
| Método | Endpoint | Descripción |
| :--- | :--- | :--- |
| GET | `/api/properties` | Búsqueda y listado de propiedades. |
| GET | `/api/services` | Búsqueda y listado de servicios. |
| POST | `/api/visitor/save` | Registro de nuevo visitante/lead. |
| POST | `/api/google/user/verify_token` | Auth vía Google (para el futuro). |

### Stack Tecnológico App Móvil:
- **Framework:** Expo (React Native).
- **Estilos:** NativeWind (Tailwind CSS).
- **Navegación:** Expo Router (basado en carpetas).
- **Estado/API:** TanStack Query + Zustand.

---

## Checkpoint web CRM - 2026-08-02

### Finalizado

- [x] Home publico desplegado para buscar proveedores sin registro.
- [x] Google Places web operativo con credencial separada de Android/iOS.
- [x] Resultados por coordenadas en `/result/services` y `/api/services_for_map`.
- [x] Saneamiento productivo de 2.895 direcciones.
- [x] Historial del mapa corregido.
- [x] Commit `52854d7` desplegado y suite 146/1.382 en verde.

### Por hacer

- [ ] QA manual de geolocalizacion permitida/denegada.
- [ ] QA del fallback Leaflet y acciones de contacto.
- [ ] Validar con negocio el radio por defecto de 30 km.
