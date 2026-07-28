# SisColog Web

Aplicacion MVC en PHP y MySQL para apoyo clinico en salud mental con analisis asistido por IA.

## Requisitos

- PHP 8.1 o superior
- MySQL 8 o superior
- Servidor web apuntando a `www/public`

## Instalacion

1. Copiar `.env.example` a `.env`.
2. Configurar credenciales de base de datos.
3. Crear la base de datos en MySQL.
4. Ejecutar migraciones:

```bash
php cli/migrate.php
```

5. Ejecutar seeders:

```bash
php cli/seed.php
```

## Arquitectura

- `app/Controllers`: recibe solicitudes HTTP y coordina respuestas.
- `app/Models`: entidades del dominio.
- `app/Repositories`: acceso a datos.
- `app/Services`: reglas de negocio y casos de uso.
- `app/Middleware`: filtros de seguridad y autenticacion.
- `app/Views`: vistas PHP.
- `core`: router, base de datos, request, response y utilidades framework.
- `database/migrations`: cambios versionados de esquema.
- `database/seeders`: datos iniciales.
- `public`: punto de entrada web.

## Comandos

```bash
php cli/migrate.php
php cli/seed.php
```
