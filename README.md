# Kconecta CRM

CRM inmobiliario de Kconecta, migrado desde un proyecto legacy de DameloDamelo.

## Stack
- Laravel 12
- PHP 8.2
- MySQL 8
- Docker Compose

## Arranque Local (Docker)
```powershell
cd D:\still\kconecta.com\web
docker compose -p kconecta up -d --build
```

App local:
- `http://localhost:8010`

## Base de Datos
Esquema de Docker:
- `kconecta_schema`

Import SQL manual (si aplica):
```powershell
docker cp D:\still\kconecta.com\assets\damelodamelo_damelo.sql kconecta-mysql-1:/tmp/damelodamelo_damelo.sql
docker exec kconecta-mysql-1 mysql -uroot -psecret -e "DROP DATABASE IF EXISTS kconecta_schema; CREATE DATABASE kconecta_schema CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
docker exec kconecta-mysql-1 sh -lc "mysql -uroot -psecret kconecta_schema < /tmp/damelodamelo_damelo.sql"
```

## Migraciones
```powershell
docker exec kconecta php artisan migrate --force
```

## Estado de Repositorio
Nombre objetivo del repo GitHub:
- `kconecta-crm`

## Próximo objetivo
Sincronizar este repo en Dokploy (Hostinger), con:
- conexión a GitHub,
- variables de entorno seguras,
- base de datos configurada,
- deploy y validación de salud.

## Documentos de control
- Plan y estado de trabajo: [tasks.md](./tasks.md)
- Guía operativa del agente: [agent.md](./agent.md)
