# Sistema de Gestión de Biblioteca (SGB)

Aplicación web en PHP puro para gestión integral de biblioteca escolar: catálogo público, autenticación, préstamos manuales, reservaciones, multas, usuarios, reportes, módulo docente con grupos y asignaciones de lectura, sugerencias de recursos, generación de etiquetas y códigos de barras, y panel de administración completo.

## Stack técnico

| Componente | Detalle |
|---|---|
| PHP | >= 8.2 (probado en 8.4) |
| Base de datos | MariaDB / MySQL |
| Autoload | Composer PSR-4 |
| Exportaciones | PhpSpreadsheet ^5.6 (Excel) · TCPDF (PDF) |
| Imágenes / Barcodes | GD (nativo PHP) |
| Tests | PHPUnit ^11 |

## Arquitectura

MVC ligero sin framework. El kernel en `app/Core/` provee router, request/response, contenedor simple, sesiones, validador y motor de vistas PHP puro.

```
app/
  Controllers/   controladores por módulo
  Core/          Router · Request · Response · View · Session · Database · Validator
  Enums/         enumeraciones de dominio (Role, LoanStatus, FineStatus…)
  Helpers/       utilidades (sanitización, CSRF, ISBN, EcuadorId, redirects seguros)
  Middleware/    Auth · CSRF · Role · SecurityHeaders · RequestLogger · Guest
  Models/        entidades de dominio
  Repositories/  acceso a datos
config/
  routes.php     definición completa de rutas
  app.php        configuración general
  database.php   conexión a BD
  mail.php       configuración SMTP
database/
  migrations/    31 migraciones SQL versionadas
  seeds/         datos semilla
views/           vistas PHP (layouts, partials, admin, teacher, public, auth, account)
public/          document root (index.php, assets, uploads)
bin/             tareas CLI
tests/           Unit / Integration
storage/         logs · cache · barcodes · qrcodes · uploads temporales
```

## Instalación rápida

1. Clonar y entrar al proyecto.
2. Instalar dependencias:

```bash
composer install
```

3. Crear archivo de entorno:

```bash
cp .env.example .env
```

4. Configurar en `.env`:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biblioteca
DB_USERNAME=usuario
DB_PASSWORD=contraseña
APP_URL=http://localhost
```

5. Ejecutar migraciones (con `--seed` para datos de prueba):

```bash
php bin/migrate.php
php bin/migrate.php --seed
```

6. Configurar servidor web apuntando a `public/` como document root (ver `.htaccess` incluido para Apache; usar `try_files` para Nginx).

## Comandos útiles

```bash
# Migraciones
php bin/migrate.php

# Limpiar caché de archivos
php bin/cache_clear.php

# Worker de cola de correo
php bin/mail_worker.php

# Tareas periódicas (cron)
php bin/overdue_check.php          # marcar préstamos vencidos y notificar
php bin/reservation_check.php      # liberar reservas expiradas y notificar
php bin/new_acquisition_check.php  # notificar nuevas adquisiciones
php bin/assignment_check.php       # recordatorios de asignaciones próximas a vencer

# Tests
./vendor/bin/phpunit
```

## Módulos implementados

### Sitio público
- Inicio con buscador y acceso rápido al catálogo
- Catálogo con filtros (categoría, tipo, disponibilidad) y paginación
- Detalle de recurso con portada, ficha MARC y disponibilidad en tiempo real
- Noticias y detalle por slug
- Nuevas adquisiciones
- Página "Acerca de"
- Endpoint `/api/autocomplete` para búsqueda en vivo

### Autenticación y cuenta de usuario
- Login / logout / registro
- Verificación de email y recuperación de contraseña
- Dashboard de cuenta con préstamos activos, reservas y multas pendientes
- Perfil: actualizar datos y cambiar contraseña
- Historial de préstamos y renovación en línea
- Reservaciones: crear y cancelar
- Multas: ver estado y pagos parciales
- Asignaciones de lectura: seguimiento de progreso

### Panel de administración / bibliotecario
| Módulo | Funcionalidades |
|---|---|
| Recursos | CRUD completo con ficha MARC, importación por ISBN (Open Library), soporte físico/digital, activar/desactivar, exportar Excel/PDF |
| Tipos de recurso | CRUD de tipos |
| Préstamos | Alta manual desde formulario (búsqueda en vivo de usuario y recurso), devolución, renovación, marcar perdido |
| Reservaciones | Listado, conversión a préstamo |
| Multas | Registro, pagos parciales/totales, cancelación |
| Usuarios | CRUD, cambio de estado/tipo/contraseña, exportar Excel/PDF |
| Categorías | CRUD |
| Sedes | CRUD |
| Noticias | CRUD con imagen y estado de publicación |
| Sugerencias | Revisar, aprobar o rechazar sugerencias de docentes |
| Etiquetas | Generación de etiquetas EAN-13 y Code-128 por recurso, tarjeta de usuario en PDF |
| Reportes | Préstamos, inventario, usuarios, multas, visitas; exportar CSV/PDF/Excel |
| Configuración | Ajustes generales, parámetros de préstamo, SMTP, cola de correo |

### Panel docente
| Módulo | Funcionalidades |
|---|---|
| Dashboard | Estadísticas propias: grupos, alumnos, asignaciones, vencidos |
| Grupos | CRUD de grupos escolares con año/periodo |
| Detalle de grupo | Listado de alumnos con estado de préstamos y multas |
| Actividad del grupo | Historial de préstamos de todos los alumnos |
| Perfil de alumno | Préstamos y asignaciones del alumno dentro del grupo |
| Reporte de grupo | Tabla imprimible con completitud de asignaciones por alumno |
| Asignaciones | Crear asignaciones de lectura, ver progreso por alumno |
| Sugerencias | Sugerir recursos al administrador, ver estado de aprobación |

## Seguridad

- CSRF en todos los formularios POST
- Middleware de autenticación y roles (admin, librarian, teacher, user)
- Headers de seguridad (CSP, X-Frame-Options, HSTS, etc.) vía middleware
- Contraseñas con `password_hash` / `password_verify`
- Cédula ecuatoriana validada antes de guardar
- Subidas de archivo validadas por MIME real (finfo) con nombres aleatorios
- Redirect seguro (sólo rutas relativas internas)
- Consultas con PDO prepared statements (sin SQL dinámico sin bind)
