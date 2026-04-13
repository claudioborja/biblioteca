# Estado de Funcionalidades

Fecha de referencia: 2026-04-12
Fuente: revision de rutas, controladores, vistas y tareas CLI existentes.

## Desarrollado

### 1. Sitio publico y descubrimiento

- Inicio publico con buscador y sugerencias
- Catalogo publico con filtros y detalle de recurso
- Noticias publicas y detalle por slug
- Nuevas adquisiciones publicas
- Pagina Acerca de
- Endpoint de autocompletado (`/api/autocomplete`)

### 2. Autenticacion y cuenta

- Login, logout y registro
- Verificacion de email
- Flujo de recuperar/restablecer contrasena
- Dashboard de cuenta
- Perfil de usuario (ver y actualizar datos)
- Cambio de contrasena desde perfil

### 3. Prestamos, reservaciones y multas

- Vista de prestamos del usuario
- Renovacion de prestamo por usuario
- Panel admin para operaciones de prestamos (renovar, devolver, marcar perdido)
- Reservaciones de usuario (crear/cancelar)
- Buscador tipo publico para crear reservacion desde panel (titulo, ISBN, autor, palabras clave)
- Conversion de reservacion a prestamo en panel admin
- Modulo de multas en cuenta y panel

### 4. Gestion administrativa

- CRUD de recursos (incluye por tipo)
- Exportaciones de recursos
- CRUD de usuarios y cambios de estado/tipo/password
- Gestion de categorias
- Gestion de sedes
- Gestion de noticias
- Revision de sugerencias
- Dashboard administrativo
- Configuracion general, SMTP test y cola de correo
- Auditoria (ruta disponible)

### 5. Modulo docente

- Dashboard docente
- Gestion de grupos
- Actividad de grupo y perfil de estudiante
- Reporte de grupo
- Sugerencias de recursos
- Vista de asignaciones del alumno en cuenta

### 6. Reportes y exportaciones

- Reportes de prestamos, inventario, usuarios, multas y visitas
- Exportaciones CSV y PDF por modulo de reportes
- Exportaciones Excel/PDF puntuales de recursos y usuarios

### 7. Codigos y etiquetas

- Etiquetas
- Codigo de barras por ISBN
- QR por entidad
- Tarjeta de usuario

### 8. Operacion y mantenimiento

- Migraciones SQL versionadas
- Seeds opcionales
- Worker de correo
- Scripts periodicos de sobretiempo, reservaciones, nuevas adquisiciones y asignaciones
- Suite de pruebas (Unit/Integration/Feature)

## Pendiente o Parcial

### 1. Prestamos manuales desde admin

Estado: parcial
Evidencia:
- `LoanController::create()` y `LoanController::store()` responden con mensaje de "disponible proximamente".

Impacto:
- El flujo manual de alta de prestamo desde formulario admin no esta finalizado.

### 2. Flujo de asignaciones docente (crear/detalle)

Estado: parcial
Evidencia:
- `AssignmentController::create()`, `store()` y `show()` muestran mensajes de "disponible proximamente".

Impacto:
- El modulo existe, pero la creacion y detalle de asignaciones aun no esta completo.

### 3. Modelos de dominio no implementados

Estado: tecnico/parcial
Evidencia:
- Existen multiples clases en `app/Models/` con marcador TODO.

Impacto:
- La app opera mayormente desde controladores + SQL directo, pero falta consolidar capa de modelos rica.

## Prioridad sugerida

1. Completar alta manual de prestamos en admin.
2. Completar CRUD/flujo completo de asignaciones docentes.
3. Reducir deuda tecnica de `app/Models/` para mejorar mantenibilidad y pruebas.

## Nota

Este estado refleja implementacion actual observable en el codigo. Para contrastar con alcance esperado, usar tambien `REQUIREMENTS.md`.
