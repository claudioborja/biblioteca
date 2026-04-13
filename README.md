# Sistema de Gestion de Biblioteca

Aplicacion web en PHP puro para gestion integral de biblioteca: catalogo publico, autenticacion, prestamos, reservaciones, multas, usuarios, reportes, noticias y modulo docente.

## Stack tecnico

- PHP >= 8.2
- MariaDB/MySQL
- Composer (autoload + dependencias)
- PHPUnit 11 (tests)
- PhpSpreadsheet (exportaciones)

## Estructura principal

- `public/`: punto de entrada web
- `app/Controllers/`: logica de cada modulo
- `app/Core/`: kernel MVC ligero (Router, Request, Response, View, Session, DB)
- `views/`: vistas publicas, panel y modulos
- `config/routes.php`: rutas del sistema
- `database/migrations/`: migraciones SQL
- `database/seeds/`: datos semilla
- `bin/`: tareas CLI (migraciones, workers, chequeos)
- `tests/`: pruebas unitarias, integracion y feature

## Instalacion rapida

1. Clonar y entrar al proyecto.
2. Instalar dependencias:

```bash
composer install
```

3. Crear variables de entorno:

```bash
cp .env.example .env
```

4. Configurar base de datos en `.env`:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

5. Ejecutar migraciones (opcional con seeds):

```bash
php bin/migrate.php
php bin/migrate.php --seed
```

6. Configurar servidor web apuntando a `public/` como document root.

## Comandos utiles

### Migraciones

```bash
php bin/migrate.php
```

### Limpieza de cache

```bash
php bin/cache_clear.php
```

### Worker de correo

```bash
php bin/mail_worker.php
```

### Tareas periodicas

```bash
php bin/overdue_check.php
php bin/reservation_check.php
php bin/new_acquisition_check.php
php bin/assignment_check.php
```

### Tests

```bash
./vendor/bin/phpunit
```

## Modulos funcionales (alto nivel)

- Publico: inicio, catalogo, detalle, noticias, nuevas adquisiciones, acerca de
- Autenticacion: login, registro, recuperacion y verificacion de email
- Cuenta usuario: dashboard, prestamos, renovacion, reservaciones, multas, perfil, asignaciones
- Panel docente: grupos, actividad, reportes de grupo, asignaciones, sugerencias
- Panel admin/bibliotecario: recursos, prestamos, reservaciones, multas, usuarios, categorias, sedes, noticias, sugerencias, reportes, codigos/etiquetas, configuracion

## Seguridad y arquitectura

- Middleware para autenticacion, roles, CSRF y headers de seguridad
- Sesiones para autenticacion del panel
- Rutas agrupadas por contexto y permisos
- Renderizado server-side con vistas PHP

## Estado del proyecto

Revisar el archivo [ESTADO_FUNCIONALIDADES.md](ESTADO_FUNCIONALIDADES.md) para ver detalle de funcionalidades desarrolladas vs pendientes.
