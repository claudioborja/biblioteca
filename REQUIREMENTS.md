# Sistema de Gestión de Biblioteca — Documento de Requerimientos

**Versión:** 1.4  
**Fecha:** 2026-04-11  
**Estado:** Borrador

---

## Tabla de Contenidos

1. [Descripción General](#1-descripción-general)
2. [Actores y Roles](#2-actores-y-roles)
3. [Casos de Uso](#3-casos-de-uso)
4. [Requerimientos Funcionales](#4-requerimientos-funcionales)
   - 4.0 [Página Pública y Catálogo Abierto](#40-página-pública-y-catálogo-abierto)
   - 4.1 [Autenticación y Acceso](#41-autenticación-y-acceso)
   - 4.2 [Catálogo de Libros](#42-catálogo-de-libros)
   - 4.3 [Gestión de Préstamos](#43-gestión-de-préstamos)
   - 4.4 [Reservas](#44-reservas)
   - 4.5 [Multas y Pagos](#45-multas-y-pagos)
   - 4.6 [Gestión de Usuarios](#46-gestión-de-usuarios)
   - 4.7 [Búsqueda y Descubrimiento](#47-búsqueda-y-descubrimiento)
   - 4.8 [Códigos de Barras y Etiquetas](#48-códigos-de-barras-y-etiquetas)
   - 4.9 [Notificaciones y Email](#49-notificaciones-y-email)
   - 4.10 [Reportes y Estadísticas](#410-reportes-y-estadísticas)
   - 4.11 [Gestión de Archivos e Imágenes](#411-gestión-de-archivos-e-imágenes)
   - 4.12 [Administración del Sistema](#412-administración-del-sistema)
   - 4.13 [Módulo Docente](#413-módulo-docente)
   - 4.14 [Noticias y Comunicados](#414-noticias-y-comunicados)
5. [Requerimientos No Funcionales](#5-requerimientos-no-funcionales)
   - 5.1 [Rendimiento](#51-rendimiento)
   - 5.2 [Seguridad](#52-seguridad)
   - 5.3 [Compatibilidad de Despliegue](#53-compatibilidad-de-despliegue)
   - 5.4 [Accesibilidad](#54-accesibilidad)
   - 5.5 [Mantenibilidad](#55-mantenibilidad)
6. [Restricciones Técnicas](#6-restricciones-técnicas)
7. [Reglas de Negocio](#7-reglas-de-negocio)
8. [Modelo de Datos](#8-modelo-de-datos)
9. [Integraciones Externas](#9-integraciones-externas)
10. [Estructura del Proyecto](#10-estructura-del-proyecto)
11. [Criterios de Aceptación](#11-criterios-de-aceptación)

---

## 1. Descripción General

### 1.1 Propósito

Sistema de Gestión de Biblioteca (SGB) es una aplicación web para administrar el ciclo completo de una biblioteca: catálogo bibliográfico, préstamos, reservas, multas, usuarios y reportes estadísticos.

### 1.2 Alcance

El sistema cubre las operaciones diarias de una biblioteca pequeña o mediana, con soporte para **múltiples sedes físicas** (sucursales, facultades, campus, ciudades):
- Página pública tipo catálogo accesible sin sesión
- Gestión de múltiples sedes con dirección, horario y contacto propios
- Registro y búsqueda de libros físicos y digitales con soporte ISBN
- Control de préstamos (máximo 72 horas, configurable) y devoluciones
- Sistema de reservas con lista de espera
- Cálculo y cobro de multas por mora
- Administración de usuarios y carnet
- Generación de etiquetas con código de barras y QR
- Reportes exportables en PDF, CSV y Excel (préstamos, visitas, inventario)
- Notificaciones automáticas por correo electrónico
- Blog/noticias de la biblioteca con sección pública
- Seguimiento de visitas diarias al sistema

### 1.3 Objetivos

| ID  | Objetivo |
|-----|----------|
| O-1 | Digitalizar el registro de libros físicos y digitales |
| O-2 | Automatizar el control de préstamos (72h) y devoluciones |
| O-3 | Reducir pérdidas por mora y deterioro mediante alertas automáticas |
| O-4 | Proveer métricas de uso para decisiones de adquisición |
| O-5 | Operar en hosting compartido sin dependencias complejas |
| O-6 | Ofrecer catálogo público accesible sin necesidad de registro |
| O-7 | Publicar noticias y nuevas adquisiciones para la comunidad |
| O-8 | Soportar múltiples sedes físicas con inventario y operaciones independientes |

---

## 2. Actores y Roles

### 2.1 Roles del Sistema

| Rol              | Descripción |
|------------------|-------------|
| **Admin**        | Control total: configuración, usuarios, respaldos, reportes globales. Incluye todas las funciones del rol Docente. |
| **Bibliotecario**| Gestión operativa: libros, préstamos, devoluciones, multas, reservas. |
| **Docente**      | Extiende al Usuario: puede gestionar grupos de alumnos, asignar lecturas, ver estadísticas de actividad de sus alumnos y sugerir libros para adquisición. |
| **Usuario**      | Usuario registrado: buscar, reservar, ver su historial. |
| **Invitado**     | Acceso público: buscar catálogo sin autenticación. |

### 2.2 Mapa de Permisos

| Permiso                                        | Admin | Bibliotecario | Docente | Usuario | Invitado |
|------------------------------------------------|:-----:|:-------------:|:-------:|:-------:|:--------:|
| **— Acceso público —**                         |       |               |         |         |          |
| Ver catálogo público                           | ✓     | ✓             | ✓       | ✓       | ✓        |
| Buscar libros                                  | ✓     | ✓             | ✓       | ✓       | ✓        |
| Ver disponibilidad                             | ✓     | ✓             | ✓       | ✓       | ✓        |
| Ver noticias públicas                          | ✓     | ✓             | ✓       | ✓       | ✓        |
| Ver nuevas adquisiciones públicas              | ✓     | ✓             | ✓       | ✓       | ✓        |
| Acceder a libros digitales                     | ✓     | ✓             | ✓       | ✓       | ✓        |
| **— Usuario autenticado —**                    |       |               |         |         |          |
| Reservar libro                                 | ✓     | ✓             | ✓       | ✓       | ✗        |
| Solicitar préstamo (presencial)                | ✓     | ✓             | ✓       | ✓       | ✗        |
| Ver propio historial                           | ✓     | ✓             | ✓       | ✓       | ✗        |
| Ver nuevas adquisiciones privadas              | ✓     | ✓             | ✓       | ✓       | ✗        |
| Renovar propio préstamo                        | ✓     | ✓             | ✓       | ✓       | ✗        |
| **— Docente —**                                |       |               |         |         |          |
| Sugerir libros para adquisición                | ✓     | ✗             | ✓       | ✗       | ✗        |
| Gestionar grupos de alumnos                    | ✓     | ✗             | ✓       | ✗       | ✗        |
| Asignar lecturas a alumnos                     | ✓     | ✗             | ✓       | ✗       | ✗        |
| Ver estadísticas de actividad de alumnos       | ✓     | ✗             | ✓       | ✗       | ✗        |
| Ver historial de préstamos de alumnos          | ✓     | ✗             | ✓       | ✗       | ✗        |
| Verificar si alumno leyó/accedió un libro      | ✓     | ✗             | ✓       | ✗       | ✗        |
| Ver estado de asignaciones de su grupo         | ✓     | ✗             | ✓       | ✗       | ✗        |
| Exportar reporte de su grupo                   | ✓     | ✗             | ✓       | ✗       | ✗        |
| Ver multas activas de sus alumnos (solo ver)   | ✓     | ✗             | ✓       | ✗       | ✗        |
| **— Bibliotecario —**                          |       |               |         |         |          |
| Crear/editar libros                            | ✓     | ✓             | ✗       | ✗       | ✗        |
| Dar de baja un libro                           | ✓     | ✓             | ✗       | ✗       | ✗        |
| Registrar préstamo                             | ✓     | ✓             | ✗       | ✗       | ✗        |
| Registrar devolución                           | ✓     | ✓             | ✗       | ✗       | ✗        |
| Emitir multa                                   | ✓     | ✓             | ✗       | ✗       | ✗        |
| Cobrar/condonar multa                          | ✓     | ✓             | ✗       | ✗       | ✗        |
| Gestionar usuarios                             | ✓     | ✓             | ✗       | ✗       | ✗        |
| Aprobar/rechazar sugerencias de libros         | ✓     | ✓             | ✗       | ✗       | ✗        |
| Imprimir etiquetas                             | ✓     | ✓             | ✗       | ✗       | ✗        |
| Ver todos los reportes                         | ✓     | ✓             | ✗       | ✗       | ✗        |
| Exportar reportes                              | ✓     | ✓             | ✗       | ✗       | ✗        |
| Publicar noticias/comunicados                  | ✓     | ✓             | ✗       | ✗       | ✗        |
| **— Admin —**                                  |       |               |         |         |          |
| Gestionar usuarios/roles                       | ✓     | ✗             | ✗       | ✗       | ✗        |
| Configuración del sistema                      | ✓     | ✗             | ✗       | ✗       | ✗        |
| Acceder a logs de auditoría                    | ✓     | ✗             | ✗       | ✗       | ✗        |
| Ver estadísticas globales de todos los grupos  | ✓     | ✗             | ✗       | ✗       | ✗        |
| Gestionar sedes (crear, editar, desactivar)    | ✓     | ✗             | ✗       | ✗       | ✗        |

---

## 3. Casos de Uso

### 3.1 Diagrama de Actores

| Caso de Uso                         | Invitado | Usuario | Docente | Bibliotecario | Admin |
|-------------------------------------|:--------:|:-------:|:-------:|:-------------:|:-----:|
| UC-01 Buscar Material               | ✓        | ✓       | ✓       | ✓             | ✓     |
| UC-02 Solicitar Préstamo            |          | ✓       | ✓       | ✓             | ✓     |
| UC-03 Renovar Préstamo              |          | ✓       | ✓       | ✓             | ✓     |
| UC-04 Devolver Material             |          |         |         | ✓             | ✓     |
| UC-05 Administrar Multas            |          |         |         | ✓             | ✓     |
| UC-06 Generar Informes              |          |         |         | ✓             | ✓     |
| UC-07 Administrar Usuarios          |          |         |         |               | ✓     |
| UC-08 Reservar Material             |          | ✓       | ✓       | ✓             | ✓     |
| UC-09 Integrar Recursos             |          |         |         | ✓             | ✓     |
| UC-10 Acceso Remoto                 | ✓        | ✓       | ✓       | ✓             | ✓     |
| UC-11 Gestionar Grupo de Alumnos    |          |         | ✓       |               | ✓     |
| UC-12 Asignar Lectura               |          |         | ✓       |               | ✓     |
| UC-13 Ver Actividad de Alumnos      |          |         | ✓       |               | ✓     |
| UC-14 Sugerir Libro para Adquisición|          |         | ✓       |               | ✓     |

---

### UC-01 — Buscar Material

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-01 |
| **Nombre**       | Buscar Material |
| **Actor principal** | Invitado, Usuario, Bibliotecario, Admin |
| **Precondiciones** | El catálogo tiene al menos un libro registrado y activo. |
| **RF relacionados** | RF-SRCH-01, RF-SRCH-02, RF-SRCH-03, RF-SRCH-04 |

**Flujo Principal:**
1. El usuario accede al catálogo público o al buscador desde cualquier página.
2. Ingresa un término de búsqueda (título, autor, ISBN o categoría) en el campo de búsqueda.
3. El sistema ejecuta búsqueda FULLTEXT en MariaDB; si no hay resultados, aplica LIKE como fallback.
4. Se muestra la lista de resultados paginada con: portada, título, autor, categoría, disponibilidad y tipo (físico/digital).
5. El usuario puede aplicar filtros (categoría, idioma, tipo, disponibilidad) para refinar resultados.
6. El usuario selecciona un resultado y accede a la ficha completa del libro.

**Flujos Alternativos:**
- **A1 — Sin resultados:** El sistema muestra sugerencias SOUNDEX y libros de categorías relacionadas.
- **A2 — Búsqueda por código de barras:** Si el usuario tiene un lector USB, al escanear el ISBN se redirige directamente a la ficha del libro.
- **A3 — Autocompletado:** Al escribir 2+ caracteres, aparecen sugerencias en tiempo real (debounce 200 ms).

**Postcondiciones:**
- El término buscado queda registrado en `search_log` de forma anónima.
- Si hubo resultados, se incrementa el contador de búsquedas del término.

---

### UC-02 — Solicitar Préstamo

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-02 |
| **Nombre**       | Solicitar Préstamo |
| **Actor principal** | Bibliotecario, Admin |
| **Actor secundario** | Usuario (beneficiario del préstamo) |
| **Precondiciones** | El usuario tiene sesión activa o está presente físicamente. El libro existe, es físico y tiene copias disponibles. |
| **RF relacionados** | RF-LOAN-01, RF-LOAN-06 |

**Flujo Principal:**
1. El Bibliotecario accede al panel de gestión de préstamos.
2. Busca al usuario (por nombre, número de carnet o documento) y selecciona su perfil.
3. Busca el libro (por título, ISBN o escaneo de código de barras) y lo selecciona.
4. El sistema valida automáticamente:
   - El libro tiene copias disponibles.
   - El usuario no supera el límite de 3 préstamos simultáneos.
   - El usuario no tiene préstamos vencidos sin devolver.
   - El usuario no tiene multas pendientes (si `block_loans_with_fines = true`).
5. El sistema calcula `due_at = NOW() + loan_hours` (default: 72 horas).
6. El Bibliotecario confirma el préstamo.
7. El sistema registra el préstamo, decrementa `available_copies` y genera el comprobante PDF.
8. Se envía correo de confirmación al usuario con la fecha y hora exacta de vencimiento.

**Flujos Alternativos:**
- **A1 — Libro sin copias disponibles:** El sistema informa la situación y ofrece registrar una reserva (UC-08).
- **A2 — Usuario con multas (modo advertencia):** Se muestra aviso pero permite continuar.
- **A3 — Usuario con multas (modo bloqueo):** El préstamo es rechazado; se indica el monto adeudado.
- **A4 — Usuario con préstamos vencidos:** El préstamo es rechazado hasta que se registre la devolución.

**Postcondiciones:**
- Queda registrado el préstamo con `status = active`.
- `available_copies` del libro decrementado en 1.
- Correo de confirmación encolado para envío.
- Acción registrada en `audit_logs`.

---

### UC-03 — Renovar Préstamo

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-03 |
| **Nombre**       | Renovar Préstamo |
| **Actor principal** | Usuario |
| **Actor secundario** | Bibliotecario, Admin (pueden renovar en nombre del usuario) |
| **Precondiciones** | El préstamo existe con `status = active` o venció hace menos de `renewal_grace_hours`. El usuario tiene sesión activa. |
| **RF relacionados** | RF-LOAN-03 |

**Flujo Principal:**
1. El usuario accede a "Mis préstamos activos" en su panel.
2. Visualiza la lista de préstamos con tiempo restante y opción "Renovar" en los elegibles.
3. Selecciona el préstamo a renovar y confirma la acción.
4. El sistema valida:
   - No hay reservas activas del libro en cola.
   - No se ha superado el límite de `max_renewals` (default: 2).
   - El préstamo está dentro del período de gracia si ya venció.
5. El sistema actualiza `due_at = NOW() + loan_hours` e incrementa `renewals_count`.
6. Se muestra confirmación con la nueva fecha y hora de vencimiento.

**Flujos Alternativos:**
- **A1 — Libro con reservas:** El sistema rechaza la renovación e informa que otro usuario está esperando.
- **A2 — Límite de renovaciones alcanzado:** El sistema rechaza e indica que debe devolver el material.
- **A3 — Fuera del período de gracia:** El sistema rechaza e indica que hay mora acumulada.

**Postcondiciones:**
- `due_at` actualizado al nuevo vencimiento.
- `renewals_count` incrementado en 1.
- Correo de confirmación de renovación encolado.

---

### UC-04 — Devolver Material

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-04 |
| **Nombre**       | Devolver Material |
| **Actor principal** | Bibliotecario, Admin |
| **Actor secundario** | Usuario (entrega el material) |
| **Precondiciones** | Existe un préstamo activo (`status = active` u `overdue`) para el libro y usuario. |
| **RF relacionados** | RF-LOAN-02 |

**Flujo Principal:**
1. El Bibliotecario busca el préstamo activo (por N° préstamo, ISBN escaneado o nombre del usuario).
2. El sistema muestra los datos del préstamo: libro, usuario, fecha de vencimiento, estado.
3. El Bibliotecario confirma la devolución.
4. El sistema compara `NOW()` con `due_at`:
   - **Sin mora:** registra devolución, incrementa `available_copies`, actualiza `status = returned`.
   - **Con mora:** calcula `CEIL((NOW() - due_at) en horas) × fine_per_hour`, genera multa automáticamente, luego registra la devolución.
5. Si hay usuarios en lista de reservas del libro, el sistema notifica al primero en cola.

**Flujos Alternativos:**
- **A1 — Material dañado:** El Bibliotecario marca el motivo "daño" al generar la multa manualmente.
- **A2 — Material perdido:** Se marca `status = lost`, se genera multa por pérdida equivalente a `replacement_cost`, se descuenta una copia de `total_copies`.
- **A3 — Devolución parcial (múltiples copias):** No aplica; cada préstamo es de un ejemplar.

**Postcondiciones:**
- Préstamo marcado como `returned` con `returned_at = NOW()`.
- `available_copies` incrementado en 1 (excepto si se declaró pérdida).
- Multa generada si hubo mora o daño/pérdida.
- Primer usuario en lista de reservas notificado por correo (si existe).
- Acción registrada en `audit_logs`.

---

### UC-05 — Administrar Multas

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-05 |
| **Nombre**       | Administrar Multas |
| **Actor principal** | Bibliotecario, Admin |
| **Actor secundario** | Usuario (paga la multa) |
| **Precondiciones** | Existe al menos una multa registrada en el sistema. |
| **RF relacionados** | RF-FINE-01, RF-FINE-02, RF-FINE-03, RF-FINE-04 |

**Flujo Principal — Registrar Pago:**
1. El Bibliotecario accede a "Gestión de multas" y busca al usuario.
2. El sistema muestra el listado de multas pendientes con montos detallados.
3. El Bibliotecario selecciona la multa y registra el pago (total o parcial).
4. El sistema actualiza `amount_paid`; si `amount_paid = amount`, cambia `status = paid`.

**Flujo Alternativo — Condonar Multa:**
1. El Bibliotecario o Admin selecciona la multa y elige "Condonar".
2. El sistema solicita justificación escrita (campo obligatorio, mínimo 10 caracteres).
3. Se confirma la condonación; el sistema actualiza `status = waived` y registra en `audit_logs` el usuario, motivo y timestamp.

**Flujo Alternativo — Generación Manual:**
1. El Bibliotecario registra manualmente una multa por daño o infracción de normas.
2. Selecciona el préstamo de referencia, el motivo y el monto.
3. El sistema la asocia al usuario y la registra como pendiente.

**Postcondiciones:**
- El estado de la multa actualizado según la acción.
- Si el usuario paga todas sus multas, se desbloquea para nuevos préstamos.
- Condonaciones registradas en `audit_logs` con motivo.

---

### UC-06 — Generar Informes

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-06 |
| **Nombre**       | Generar Informes |
| **Actor principal** | Admin, Bibliotecario |
| **Precondiciones** | El usuario tiene sesión activa con rol Admin o Bibliotecario. |
| **RF relacionados** | RF-REP-01 al RF-REP-08 |

**Flujo Principal:**
1. El usuario accede a la sección `/informes` del panel de administración.
2. Selecciona la categoría de informe: Circulación, Inventario, Usuarios, Finanzas o Tráfico.
3. Configura los parámetros: período (hoy / semana / mes / año / rango personalizado), filtros adicionales.
4. El sistema consulta la base de datos (con cache para evitar consultas repetitivas) y genera el reporte.
5. Se muestra la vista con tablas de datos y gráficos SVG.
6. El usuario puede exportar en el formato deseado: CSV, Excel o PDF.

**Flujos Alternativos:**
- **A1 — Sin datos en el período:** Se muestra mensaje informativo y se sugiere ampliar el rango.
- **A2 — Exportar CSV:** Se descarga el archivo con BOM UTF-8 para compatibilidad con Excel en español.
- **A3 — Imprimir:** Se activa el layout de impresión (`@media print`) que oculta menús y muestra solo contenido tabular.

**Postcondiciones:**
- El reporte generado queda en cache por 5 minutos para consultas repetidas.
- Los PDFs exportados se eliminan del servidor automáticamente tras 1 hora.

---

### UC-07 — Administrar Usuarios

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-07 |
| **Nombre**       | Administrar Usuarios |
| **Actor principal** | Admin |
| **Precondiciones** | El usuario tiene sesión activa con rol Admin. |
| **RF relacionados** | RF-AUTH-05, RF-MEM-01 al RF-MEM-05, RF-ADMIN-02 |

**Flujo Principal — Registrar Usuario:**
1. El Admin accede a "Gestión de usuarios / Nuevo usuario".
2. Completa el formulario con datos obligatorios: nombre, correo, documento, teléfono, tipo.
3. El sistema valida unicidad de correo y documento.
4. Se genera automáticamente el número de carnet (`BIB-YYYY-NNNNN`).
5. El sistema crea la cuenta, asigna contraseña temporal y envía correo de bienvenida.
6. El usuario debe cambiar la contraseña en el primer acceso.

**Flujo Principal — Gestionar Roles y Estado:**
1. El Admin busca al usuario por nombre, correo o carnet.
2. Puede cambiar: rol (Admin / Bibliotecario / Usuario), estado (Activo / Suspendido / Bloqueado).
3. Puede forzar cambio de contraseña en el próximo acceso.
4. Todas las acciones quedan registradas en `audit_logs`.

**Flujos Alternativos:**
- **A1 — Correo duplicado:** El sistema rechaza el registro con mensaje de error específico.
- **A2 — Baja de usuario con préstamos activos:** El sistema bloquea la baja e informa los préstamos pendientes.
- **A3 — Eliminar último Admin:** El sistema rechaza la operación para garantizar acceso mínimo al sistema.

**Postcondiciones:**
- Usuario creado/modificado con los datos proporcionados.
- Correo de bienvenida encolado para nuevos usuarios.
- Acción registrada en `audit_logs`.

---

### UC-08 — Reservar Material

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-08 |
| **Nombre**       | Reservar Material |
| **Actor principal** | Usuario |
| **Actor secundario** | Bibliotecario, Admin (pueden reservar en nombre del usuario) |
| **Precondiciones** | El usuario tiene sesión activa. El libro existe, es físico. El usuario no tiene el mismo libro prestado. |
| **RF relacionados** | RF-RES-01, RF-RES-02, RF-RES-03, RF-RES-04 |

**Flujo Principal:**
1. El usuario encuentra un libro sin copias disponibles en el catálogo.
2. Hace clic en "Reservar" en la ficha del libro.
3. El sistema valida:
   - El usuario no tiene ya una reserva activa del mismo libro.
   - El usuario no supera el límite de 5 reservas activas simultáneas.
4. Se crea la reserva y se asigna posición en la cola de espera (FIFO).
5. El sistema muestra confirmación con la posición en cola y estimación de disponibilidad.

**Flujo de Notificación (automático vía cron):**
1. Al registrarse una devolución, el sistema detecta reservas activas del libro.
2. Notifica por correo al primero en cola con un plazo de `reservation_hold_hours` (default: 48h) para retirar.
3. Si no retira en el plazo, la reserva expira, se notifica al siguiente en cola.

**Flujos Alternativos:**
- **A1 — Libro disponible:** El sistema advierte que el libro tiene copias disponibles y ofrece ir directamente al préstamo.
- **A2 — Lista de espera larga:** Se informa la posición y una estimación aproximada de espera.
- **A3 — Cancelar reserva:** El usuario puede cancelar desde "Mis reservas"; la cola se reorganiza automáticamente.

**Postcondiciones:**
- Reserva creada con `status = waiting` y posición en cola asignada.
- Al notificar disponibilidad: `status = notified`, `notified_at` y `expires_at` registrados.
- Al convertir a préstamo: reserva eliminada y préstamo creado en operación atómica.

---

### UC-09 — Integrar Recursos Digitales

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-09 |
| **Nombre**       | Integrar Recursos Digitales |
| **Actor principal** | Bibliotecario, Admin |
| **Precondiciones** | El usuario tiene sesión con rol Bibliotecario o Admin. El recurso digital es de acceso abierto o la biblioteca posee la licencia correspondiente. |
| **RF relacionados** | RF-CAT-01, RF-CAT-09 |

**Flujo Principal — Recurso externo (URL):**
1. El Bibliotecario accede a "Nuevo libro" y selecciona tipo `digital`.
2. Completa los campos: título, autor, categoría, descripción, costo de reposición, URL de acceso externo.
3. El sistema valida que la URL tenga formato válido (no verifica disponibilidad en tiempo real).
4. Guarda el registro; el libro aparece en el catálogo con etiqueta "Digital" y acceso directo al enlace.

**Flujo Principal — Archivo PDF propio:**
1. El Bibliotecario selecciona tipo `digital` y elige "Subir archivo PDF".
2. Sube el archivo (PDF, máximo 50 MB); el sistema lo almacena fuera de `public_html`.
3. El acceso se sirve a través del controlador PHP con validación de ruta segura.
4. El contador `digital_access_count` se incrementa en cada acceso.

**Flujos Alternativos:**
- **A1 — URL ya registrada:** El sistema advierte si la URL está duplicada en otro registro.
- **A2 — Archivo demasiado grande:** Se muestra error de validación con el límite permitido.

**Postcondiciones:**
- Libro digital visible en catálogo público con enlace de acceso abierto.
- No se generan copias, préstamos ni reservas para este recurso.
- Accesos registrados en estadísticas (RF-REP-07).

---

### UC-10 — Acceso Remoto

| Campo            | Detalle |
|------------------|---------|
| **ID**           | UC-10 |
| **Nombre**       | Acceso Remoto |
| **Actor principal** | Invitado, Usuario, Bibliotecario, Admin |
| **Precondiciones** | El servidor está accesible por Internet con HTTPS habilitado. |
| **RF relacionados** | RF-AUTH-01, RF-AUTH-02, RF-PUB-01, RF-PUB-02 |

**Flujo Principal — Acceso público:**
1. El usuario navega a la URL del sistema desde cualquier dispositivo o ubicación.
2. El sistema sirve la página pública (catálogo, noticias, búsqueda) sin requerir autenticación.
3. El catálogo de libros y los recursos digitales son accesibles de forma abierta.

**Flujo Principal — Acceso autenticado:**
1. El usuario accede al formulario de login desde cualquier ubicación.
2. Ingresa credenciales; el sistema valida con protección contra fuerza bruta.
3. Opcionalmente activa "Recordarme" para persistir la sesión 30 días mediante cookie segura.
4. Una vez autenticado, puede realizar todas las operaciones permitidas por su rol desde cualquier dispositivo.

**Consideraciones de Seguridad:**
- HTTPS obligatorio en producción (HSTS `max-age=31536000`).
- Cookies de sesión con flags `HttpOnly; Secure; SameSite=Strict`.
- Token "Recordarme" rotado en cada uso (nunca reutilizado).
- Protección CSRF en todos los formularios mutantes.
- Las operaciones administrativas críticas (cambio de contraseña, condonar multa) requieren reautenticación si la sesión supera las 8 horas de inactividad.

**Postcondiciones:**
- La visita queda registrada en `visits_log` con IP hasheada (nunca en texto claro).
- La sesión se mantiene activa mientras el usuario opera dentro del timeout configurado.

---

---

### UC-11 — Gestionar Grupo de Alumnos

| Campo               | Detalle |
|---------------------|---------|
| **ID**              | UC-11 |
| **Nombre**          | Gestionar Grupo de Alumnos |
| **Actor principal** | Docente, Admin |
| **Precondiciones**  | El Docente tiene sesión activa. Los alumnos están registrados como Usuarios en el sistema. |
| **RF relacionados** | RF-DOC-01, RF-DOC-02 |

**Flujo Principal — Crear grupo:**
1. El Docente accede a "Mis grupos" en su panel.
2. Crea un nuevo grupo indicando nombre, materia/asignatura y ciclo escolar.
3. Busca y agrega alumnos al grupo por nombre, número de carnet o documento.
4. Guarda el grupo; los alumnos no son notificados automáticamente (solo el docente los gestiona).

**Flujo Principal — Editar grupo:**
1. El Docente selecciona un grupo existente.
2. Puede agregar o remover alumnos, renombrar el grupo o archivar el grupo al finalizar el ciclo.
3. Los grupos archivados conservan el historial pero no aparecen en el panel activo.

**Flujos Alternativos:**
- **A1 — Alumno no encontrado:** Se muestra mensaje indicando que el usuario no existe; se puede invitar al Bibliotecario a registrarlo.
- **A2 — Alumno ya en otro grupo activo:** El sistema permite al mismo alumno estar en múltiples grupos simultáneos.

**Postcondiciones:**
- Grupo creado/actualizado con los usuarios asignados.
- Los alumnos del grupo quedan bajo la visibilidad estadística del Docente.

---

### UC-12 — Asignar Lectura

| Campo               | Detalle |
|---------------------|---------|
| **ID**              | UC-12 |
| **Nombre**          | Asignar Lectura |
| **Actor principal** | Docente, Admin |
| **Precondiciones**  | El Docente tiene al menos un grupo activo. El libro existe y está activo en el catálogo. |
| **RF relacionados** | RF-DOC-03, RF-DOC-04 |

**Flujo Principal:**
1. El Docente accede a "Asignaciones de lectura" y crea una nueva.
2. Busca y selecciona el libro (físico o digital).
3. Selecciona el grupo o alumnos individuales destinatarios.
4. Configura los parámetros de la asignación:
   - Fecha límite de cumplimiento.
   - Tipo de verificación esperada: préstamo registrado / acceso a digital / ambas.
   - Nota o instrucciones adicionales para el alumno.
5. Guarda la asignación; el sistema notifica a cada alumno por correo con los detalles.

**Flujos Alternativos:**
- **A1 — Libro sin copias disponibles (físico):** El sistema advierte y permite continuar; los alumnos verán la asignación con estado "pendiente de disponibilidad".
- **A2 — Asignación sin fecha límite:** Permitido; la asignación queda abierta indefinidamente.
- **A3 — Eliminar asignación:** El Docente puede eliminar la asignación; se notifica a los alumnos.

**Postcondiciones:**
- Asignación registrada y vinculada al grupo/alumnos.
- Correo de notificación encolado para cada alumno asignado.
- La asignación aparece en el panel del alumno con estado "Pendiente".

---

### UC-13 — Ver Actividad de Alumnos

| Campo               | Detalle |
|---------------------|---------|
| **ID**              | UC-13 |
| **Nombre**          | Ver Actividad de Alumnos |
| **Actor principal** | Docente, Admin |
| **Precondiciones**  | El Docente tiene al menos un grupo activo con alumnos. |
| **RF relacionados** | RF-DOC-05, RF-DOC-06, RF-DOC-07 |

**Flujo Principal — Vista de grupo:**
1. El Docente selecciona un grupo desde su panel.
2. El sistema muestra el dashboard del grupo con:
   - Resumen de asignaciones activas: cumplidas / pendientes / sin actividad.
   - Lista de alumnos con indicadores de actividad (verde/amarillo/rojo).
   - Préstamos activos del grupo en este momento.
   - Alumnos con multas pendientes (sin monto, solo indicador).
3. El Docente puede hacer clic en un alumno para ver su perfil de actividad detallado.

**Flujo Principal — Perfil de actividad de un alumno:**
1. El sistema muestra para el alumno seleccionado:
   - Historial de préstamos (todos los libros que ha tomado prestados).
   - Libros digitales a los que ha accedido.
   - Asignaciones pendientes y cumplidas.
   - Fecha del último acceso al sistema.
   - Total de préstamos en el ciclo actual.
2. El Docente puede exportar el perfil de actividad del alumno a PDF o CSV.

**Flujos Alternativos:**
- **A1 — Alumno sin actividad:** Se indica claramente "Sin actividad registrada en el período".
- **A2 — Verificar cumplimiento de asignación específica:** El Docente filtra por asignación y ve qué alumnos la han cumplido (tomaron prestado o accedieron al libro).

**Postcondiciones:**
- Solo lectura; el Docente no puede modificar datos del alumno.
- Las consultas del Docente no quedan registradas en `audit_logs` (son operaciones de lectura autorizadas).

---

### UC-14 — Sugerir Libro para Adquisición

| Campo               | Detalle |
|---------------------|---------|
| **ID**              | UC-14 |
| **Nombre**          | Sugerir Libro para Adquisición |
| **Actor principal** | Docente, Admin |
| **Actor secundario**| Bibliotecario (revisa y aprueba/rechaza) |
| **Precondiciones**  | El Docente tiene sesión activa. |
| **RF relacionados** | RF-DOC-08 |

**Flujo Principal:**
1. El Docente accede a "Sugerir libro" desde su panel o desde la ficha de búsqueda sin resultados.
2. Completa el formulario de sugerencia:
   - Título (obligatorio).
   - Autor (opcional).
   - ISBN (opcional; si se ingresa, el sistema lo valida y auto-completa datos con Open Library).
   - Motivo/justificación (campo de texto libre, obligatorio).
   - Materia o asignatura relacionada.
   - Urgencia: Normal / Alta.
3. Envía la sugerencia; el sistema notifica al Bibliotecario y al Admin.

**Flujo de Revisión (Actor: Bibliotecario/Admin):**
1. El Bibliotecario accede a "Sugerencias pendientes" en el panel de administración.
2. Revisa cada sugerencia con los datos del solicitante y la justificación.
3. Puede: **Aprobar** (convierte en tarea de adquisición), **Rechazar** (con motivo obligatorio) o **Marcar como duplicado** (si el libro ya existe en el catálogo).
4. El sistema notifica al Docente con el resultado de la revisión.

**Flujos Alternativos:**
- **A1 — Libro ya existe en catálogo:** El sistema detecta coincidencia por ISBN o título similar y advierte antes de enviar.
- **A2 — Sugerencia duplicada:** Si el mismo libro fue sugerido antes, el sistema informa y permite agregar la sugerencia como "co-solicitante" de la existente.

**Postcondiciones:**
- Sugerencia registrada con estado `pending`.
- Notificación enviada al Bibliotecario/Admin.
- Al aprobar: la sugerencia queda como `approved` y el Docente recibe confirmación.
- Al rechazar: la sugerencia queda como `rejected` con el motivo; el Docente es notificado.

---

## 4. Requerimientos Funcionales

### 4.0 Página Pública y Catálogo Abierto

#### RF-PUB-01 — Página de Inicio (Home)
- La URL raíz del sistema muestra una página pública sin requerir sesión.
- Secciones visibles para cualquier visitante:
  - Banner o encabezado con `library_name`, `library_logo` y `library_slogan` (configurables en RF-ADMIN-01).
  - Buscador de libros en posición destacada.
  - Vitrina de **nuevas adquisiciones** (últimas incorporaciones al catálogo).
  - Sección de **libros más prestados** (top semanal/mensual).
  - Últimas **noticias o comunicados** de la biblioteca (máximo 3 en portada).
  - Acceso directo a catálogo completo, noticias y formulario de contacto.
  - Si hay múltiples sedes: listado de sedes con nombre, dirección y horario (RF-ADMIN-06).
- Pie de página con: `library_name`, `library_address`, `library_phone`, `library_email`, `library_schedule` (datos de la sede principal; configurables en RF-ADMIN-01).
- Favicon configurable via `library_favicon` (RF-ADMIN-01).
- Meta tags `<title>` y `<meta description>` usan `library_name` y `library_slogan`.
- La página es completamente funcional sin JavaScript (JS enriquece la experiencia pero no es requisito).

#### RF-PUB-02 — Catálogo Público
- Listado paginado de todos los libros activos, accesible sin sesión.
- Muestra: portada, título, autor, categoría, disponibilidad (disponible / no disponible), tipo (físico / digital).
- Los libros digitales muestran enlace de acceso abierto directamente.
- Filtros disponibles para invitados: categoría, idioma, tipo (físico/digital), disponibilidad.
- Ficha de cada libro accesible públicamente con todos los metadatos.
- La ficha incluye un botón "Reservar" que redirige al login si el visitante no tiene sesión.

#### RF-PUB-03 — Nuevas Adquisiciones (Sección Pública)
- Página pública `/nuevas-adquisiciones` con los libros incorporados en los últimos 30 días (configurable).
- Ordenados de más reciente a más antiguo.
- Muestra portada, título, autor, fecha de ingreso.
- Invitados pueden verla sin sesión.

#### RF-PUB-04 — Nuevas Adquisiciones (Sección Privada)
- Sección `/mi-zona/nuevas-adquisiciones` accesible solo para usuarios autenticados.
- Contenido extendido: precio de adquisición, ubicación física, cantidad de copias, notas internas.
- Permite al usuario marcar libros de interés para recibir notificación cuando estén disponibles.

#### RF-PUB-05 — Registro de Visitas
- El sistema registra automáticamente cada visita única a la plataforma (por sesión o cookie anónima de 24h).
- Se distingue entre: visitantes anónimos, usuarios autenticados.
- Los datos de visitas alimentan el reporte de visitas diarias (RF-REP-07).
- No se almacenan datos personales de visitantes anónimos (solo fecha, hora, IP hasheada, user-agent categorizado).

#### RF-PUB-06 — Estadísticas Públicas
- Bloque informativo en la página pública mostrando:
  - Total de libros en el catálogo.
  - Total de usuarios registrados.
  - Total de préstamos realizados (histórico).
- Datos actualizados con cache de 1 hora.

---

### 4.1 Autenticación y Acceso

#### RF-AUTH-01 — Inicio de Sesión
- El sistema debe permitir login con correo electrónico y contraseña.
- Las contraseñas deben almacenarse con ARGON2ID.
- Debe existir protección contra fuerza bruta: bloqueo temporal tras 5 intentos fallidos en 15 minutos.
- La sesión debe renovar el ID al autenticarse (`session_regenerate_id(true)`).

#### RF-AUTH-02 — Recordar Sesión
- Opción "Recordarme" con token seguro (SHA-256, 30 días).
- El token se almacena en cookie `HttpOnly; Secure; SameSite=Strict`.
- Al usar el token se debe regenerar (rotación en cada uso).

#### RF-AUTH-03 — Recuperación de Contraseña
- Flujo de recuperación por correo con token de un solo uso.
- El token expira en 60 minutos.
- Al usarse, el token se invalida inmediatamente.

#### RF-AUTH-04 — Cierre de Sesión
- El cierre de sesión debe destruir completamente la sesión y eliminar cookies.
- Opción de cerrar todas las sesiones activas.

#### RF-AUTH-05 — Registro de Usuarios
- El Admin/Bibliotecario puede registrar nuevos usuarios.
- Los usuarios reciben correo de bienvenida con sus credenciales iniciales.
- El usuario debe cambiar la contraseña en el primer acceso.

#### RF-AUTH-06 — Protección CSRF
- Todos los formularios POST/PUT/DELETE deben incluir token CSRF.
- El token se valida con `hash_equals()` para prevenir ataques de temporización.

---

### 4.2 Catálogo de Libros

#### RF-CAT-01 — Registro de Libro
Campos obligatorios:
- ISBN (10 o 13 dígitos, con validación de checksum; no aplica para libros digitales sin ISBN)
- Título
- Autor(es) — múltiples autores separados
- Editorial
- Año de publicación
- Categoría/Género
- **Tipo de libro** (`physical` | `digital`)
- **Costo de reposición** (`replacement_cost`, DECIMAL) — valor que debe cubrir el usuario en caso de pérdida o daño irreparable; campo **obligatorio**
- Número de copias totales (para libros físicos; los digitales usan `0`)
- **Sede** (`branch_id`) — obligatorio para libros físicos; indica en qué sede se encuentra el ejemplar

Campos opcionales:
- Descripción/sinopsis
- Número de páginas
- Idioma
- Imagen de portada
- Ubicación dentro de la sede (estante, sección, piso) — solo libros físicos
- Precio de adquisición (costo real pagado por la biblioteca)
- Fecha de adquisición
- URL de acceso — obligatorio para libros digitales, no aplica para físicos

#### RF-CAT-02 — Lookup Automático por ISBN
- Al ingresar un ISBN válido, el sistema debe consultar Open Library API.
- Los metadatos encontrados se pre-rellenan en el formulario (editables antes de guardar).
- Si la API no responde, el formulario sigue disponible para ingreso manual.
- Los resultados de la API se cachean por 30 días.

#### RF-CAT-03 — Edición de Libro
- Solo Admin y Bibliotecario pueden editar registros.
- Se debe registrar auditoría de quién y cuándo realizó el cambio.
- No se puede eliminar un libro con préstamos activos o reservas pendientes.

#### RF-CAT-04 — Baja de Libro (Soft Delete)
- La ficha de cada libro incluye un botón **"Dar de baja"** visible para Admin y Bibliotecario.
- La baja es lógica (soft delete): el registro permanece en la base de datos con `is_active = 0`.
- El sistema solicita confirmación antes de ejecutar la baja, indicando el título y cualquier impacto (préstamos/reservas activos).
- No se puede dar de baja un libro con préstamos activos; se debe registrar la devolución primero.
- Los libros dados de baja desaparecen del catálogo público y de los resultados de búsqueda.
- El historial de préstamos del libro se mantiene intacto.
- Se puede reactivar un libro dado de baja desde el panel de Admin.
- La acción queda registrada en el log de auditoría (quién, cuándo, motivo opcional).

#### RF-CAT-05 — Gestión de Copias
- El sistema gestiona cantidad de copias totales y copias disponibles.
- Al registrar un préstamo, se decrementa disponibles.
- Al registrar devolución, se incrementa disponibles.
- No se puede tener más copias prestadas que copias totales.

#### RF-CAT-06 — Categorías
- El Admin puede crear, editar y eliminar categorías.
- Un libro pertenece a exactamente una categoría principal.
- Las categorías se usan para filtrado en búsqueda y reportes.
- Las categorías no definen reglas de préstamo ni de multas; esas políticas son globales del sistema.

#### RF-CAT-07 — Portadas de Libros
- Se permite subir imagen JPG/PNG/WebP, máximo 2 MB.
- El sistema genera automáticamente miniatura (150×200px) y versión media (300×400px).
- Las imágenes se re-guardan via GD para eliminar metadatos EXIF.
- Si no hay portada, se muestra imagen genérica por categoría.

#### RF-CAT-08 — Nuevas Adquisiciones
- Al registrar un libro, se marca automáticamente como "nueva adquisición" durante los primeros 30 días (configurable: `new_acquisition_days`).
- El campo `acquired_at` determina la antigüedad de la adquisición.
- La sección pública muestra nuevas adquisiciones sin datos sensibles (sin precio de adquisición).
- La sección privada (usuarios) muestra datos adicionales: ubicación física, número de copias, notas.
- Admin y Bibliotecario pueden marcar o desmarcar manualmente el flag de "nueva adquisición".
- Las nuevas adquisiciones aparecen en la portada pública (máximo configurable, default: 8 títulos).

#### RF-CAT-09 — Libros Digitales de Acceso Abierto
- El sistema soporta libros de tipo `digital` con URL de acceso externo o interno.
- Los libros digitales **no requieren préstamo**: el enlace es directamente accesible.
- No se gestionan copias ni disponibilidad para libros digitales.
- El enlace de acceso es visible en la ficha pública del libro (sin necesidad de sesión).
- Tipos de enlace soportados: URL externa (Open Access, Project Gutenberg, etc.) o archivo PDF almacenado en el servidor.
- Para archivos PDF propios: se sirven via controlador PHP con validación de ruta segura.
- Los accesos a libros digitales se registran en estadísticas (conteo de clics/descargas).
- Los libros digitales aparecen diferenciados en el catálogo con un ícono/etiqueta "Digital".
- No se generan préstamos, multas ni reservas para libros digitales.

---

### 4.3 Gestión de Préstamos

#### RF-LOAN-01 — Crear Préstamo
- Solo aplica para libros físicos (los digitales no requieren préstamo).
- El Bibliotecario selecciona usuario y libro.
- El sistema verifica:
  - El libro es de tipo físico y tiene copias disponibles.
  - El usuario no ha alcanzado el límite de préstamos simultáneos (default: **3 libros**).
  - El usuario no tiene multas pendientes (configurable: bloquear o advertir).
  - El usuario no tiene préstamos vencidos sin devolver.
- El plazo de préstamo es de **72 horas** por defecto (configurable en Admin como `loan_hours`).
- Se registra `loan_at` (DATETIME) y `due_at` (DATETIME = `loan_at + loan_hours`).
- El comprobante de préstamo indica la hora exacta de vencimiento, no solo la fecha.
- Se genera comprobante de préstamo en PDF (opcional).

#### RF-LOAN-02 — Registrar Devolución
- Buscar préstamo activo por número de préstamo, ISBN o nombre del usuario.
- El sistema calcula automáticamente si hay mora (comparando `NOW()` con `due_at`).
- Si hay mora, se genera multa automáticamente antes de marcar como devuelto.
- Se incrementa disponibilidad del libro.
- Se notifica al siguiente usuario en lista de reservas (si existe).

#### RF-LOAN-03 — Renovación de Préstamo
- Un usuario puede renovar su préstamo si:
  - No ha vencido aún (o venció hace menos de `renewal_grace_hours` horas — configurable, default: 2h).
  - No hay reservas pendientes del libro.
  - No ha superado el límite de renovaciones (default: 2 renovaciones).
- Cada renovación extiende `due_at` desde el momento de la renovación + `loan_hours`.
- Se registra historial de renovaciones con timestamp.

#### RF-LOAN-04 — Historial de Préstamos
- El usuario puede ver su propio historial completo.
- El Admin/Bibliotecario puede ver historial de cualquier usuario.
- Filtros: estado (activo, devuelto, vencido), rango de fechas, libro, categoría.
- Vista con fecha y hora de préstamo, fecha y hora de vencimiento, fecha y hora de devolución.
- Exportable a CSV.

#### RF-LOAN-05 — Alertas de Vencimiento
- Dado el plazo de 72 horas, las alertas se ajustan al período corto:
  - **24 horas antes del vencimiento**: correo de recordatorio.
  - **Al vencimiento** (cron cada hora): correo de alerta de mora.
  - **12 horas después del vencimiento**: segunda notificación de mora con monto acumulado.
- Con plazos extendidos (> 5 días), se usa el esquema original de 3 días antes.
- Los envíos se gestionan mediante cron job cada hora (no diario).

#### RF-LOAN-06 — Panel de Gestión de Préstamos
- Pantalla dedicada para Admin/Bibliotecario con:
  - Listado de todos los préstamos activos con tiempo restante (countdown).
  - Filtros: estado, usuario, libro, fecha, vencimiento próximo, **sede**.
  - Acciones rápidas: registrar devolución, renovar, marcar como perdido.
  - Indicador visual de préstamos en mora (resaltado en rojo).
  - Exportar listado filtrado a CSV/PDF.

---

### 4.4 Reservas

#### RF-RES-01 — Crear Reserva
- Un usuario puede reservar un libro que:
  - No tiene copias disponibles actualmente, O
  - Quiere asegurarse disponibilidad futura.
- No se puede reservar un libro que el usuario ya tiene prestado.
- Se asigna número de posición en cola de espera.

#### RF-RES-02 — Cola de Espera (FIFO)
- Las reservas se atienden en orden de creación (FIFO estricto).
- Al devolver un libro, el sistema notifica automáticamente al primero en lista.
- El usuario notificado tiene 48 horas para retirar el libro (configurable).
- Si no retira en el plazo, la reserva se cancela y se notifica al siguiente.

#### RF-RES-03 — Cancelar Reserva
- El usuario puede cancelar su propia reserva en cualquier momento.
- El Admin/Bibliotecario puede cancelar cualquier reserva.
- Al cancelar, se reorganiza la cola y se notifica al siguiente si corresponde.

#### RF-RES-04 — Conversión de Reserva a Préstamo
- Cuando el usuario llega a retirar, el Bibliotecario convierte la reserva en préstamo.
- La conversión elimina la reserva y crea el préstamo en una sola operación atómica.

---

### 4.5 Multas y Pagos

#### RF-FINE-01 — Cálculo de Multas
- Las multas se calculan por hora de atraso (redondeo hacia arriba).
- Tarifa única global configurable desde parámetros del sistema.
- La multa se genera automáticamente al registrar la devolución tardía.
- También se puede generar manualmente por pérdida o daño del libro.

#### RF-FINE-02 — Gestión de Multas
- El Admin/Bibliotecario puede:
  - Ver todas las multas pendientes y pagadas.
  - Registrar pago total o parcial de una multa.
  - Condonar una multa con justificación obligatoria.
- Las multas condonadas quedan en historial con el motivo de condonación.

#### RF-FINE-03 — Bloqueo por Multas
- Configurable: bloquear préstamos a usuarios con multas pendientes.
- Si está habilitado: no se puede crear nuevo préstamo hasta saldar multas.
- El sistema debe mostrar monto total adeudado al intentar el préstamo bloqueado.

#### RF-FINE-04 — Reportes de Multas
- Reporte de multas pendientes por usuario.
- Reporte de multas cobradas en rango de fechas.
- Reporte de multas condonadas con motivos.

---

### 4.6 Gestión de Usuarios

#### RF-MEM-01 — Registro de Usuario
Campos obligatorios:
- Nombre completo
- Correo electrónico (único)
- Número de documento de identidad (único)
- Teléfono
- Tipo de usuario (Estudiante, Docente, Externo, etc.)

Campos opcionales:
- Dirección
- Fecha de nacimiento
- Foto de perfil
- Observaciones

#### RF-MEM-02 — Número de Carnet
- El sistema genera automáticamente un número de carnet único al registrar el usuario.
- El formato usa `carnet_prefix` de RF-ADMIN-01 (ej: `{carnet_prefix}-YYYY-NNNNN` → `BIB-2026-00001`).
- Se puede generar el carnet físico en PDF (crédito tamaño, con foto, QR y código de barras).

#### RF-MEM-03 — Estado del Usuario
Estados posibles:
- **Activo**: puede realizar operaciones normales.
- **Suspendido**: no puede solicitar préstamos ni reservas (multas o sanción).
- **Bloqueado**: acceso denegado completamente (Admin solamente).
- **Inactivo**: sin actividad por más de 1 año (configurable).

#### RF-MEM-04 — Historial del Usuario
Desde el perfil del usuario, el Admin/Bibliotecario puede ver:
- Préstamos activos
- Historial completo de préstamos
- Reservas activas
- Multas pendientes y pagadas
- Log de actividad reciente

#### RF-MEM-05 — Edición y Baja
- El usuario puede editar sus propios datos de contacto.
- Solo Admin puede cambiar el rol o estado de un usuario.
- Baja lógica (no se eliminan registros históricos).

---

### 4.7 Búsqueda y Descubrimiento

#### RF-SRCH-01 — Búsqueda General
- Campo de búsqueda único en el header disponible para todos los roles.
- Búsqueda por: título, autor, ISBN, editorial.
- Motor primario: FULLTEXT de MariaDB en modo BOOLEAN.
- Fallback automático a LIKE si FULLTEXT no encuentra resultados.
- Mínimo de caracteres: 2 (alineado con `ft_min_word_len=2`).

#### RF-SRCH-02 — Filtros y Facetas
Filtros disponibles en la página de resultados:
- Categoría (con conteo de resultados por categoría)
- Disponibilidad (disponible ahora / todos)
- **Sede** (solo libros físicos; con conteo por sede)
- Idioma
- Año de publicación (rango)
- Editorial

Los filtros son acumulables (AND lógico entre facetas).

#### RF-SRCH-03 — Autocompletado
- El campo de búsqueda ofrece sugerencias mientras se escribe (debounce 200ms).
- Muestra hasta 8 sugerencias (título + autor).
- Accesible con teclado (flechas, Enter, Escape).
- Implementado en JavaScript del lado del cliente (sin librerías externas).

#### RF-SRCH-04 — Búsqueda por Código de Barras
- Soporte para lectores de código de barras USB (entrada HID).
- Al detectar un ISBN por barcode reader, redirige directamente a la ficha del libro.
- Funciona en la interfaz del Bibliotecario para préstamos y devoluciones.

#### RF-SRCH-05 — Búsquedas Populares
- El sistema registra todas las búsquedas anónimamente.
- Se muestra una sección de "Búsquedas populares" en la página de búsqueda.
- Los términos se actualizan mediante cron diario.

#### RF-SRCH-06 — Sin Resultados
- Si no hay resultados, mostrar sugerencias basadas en SOUNDEX.
- Mostrar libros de categorías relacionadas.
- Permitir al usuario suscribirse para ser notificado si se agrega el libro buscado.

---

### 4.8 Códigos de Barras y Etiquetas

#### RF-BAR-01 — Generación de Código de Barras ISBN
- Generar código de barras EAN-13 a partir del ISBN del libro.
- Formato PNG, servido dinámicamente y cacheado en disco.
- Tamaño configurable para impresión.

#### RF-BAR-02 — Código QR
- Generar QR con URL canónica del libro (para catálogo público).
- Generar QR de carnet de usuario con datos básicos para verificación rápida.
- Implementado con phpqrcode (un solo archivo, sin dependencias).

#### RF-BAR-03 — Etiquetas de Libros
- Imprimir hoja de etiquetas para libros en formato PDF.
- La etiqueta incluye: código de barras EAN-13, QR, título (truncado), número de clasificación.
- Plantilla: grilla configurable (3×8, 4×10) en hoja A4.
- Se puede imprimir etiqueta individual o lote por categoría.

#### RF-BAR-04 — Carnet de Usuario
- PDF tamaño tarjeta de crédito (85.6 × 53.98 mm).
- Anverso: `library_name`, nombre del usuario, número de carnet, tipo de usuario, foto, código de barras.
- Reverso: `library_logo`, `library_address`, `library_phone`, QR con `library_website` + datos del usuario, vigencia.
- Todos los datos institucionales se toman de `system_settings` (RF-ADMIN-01).
- Imprimible individualmente o en lote.

---

### 4.9 Notificaciones y Email

#### RF-EMAIL-01 — Configuración SMTP
- El sistema envía correos via SMTP (PHPMailer).
- Configuración: host, puerto, usuario, contraseña, from, from_name.
- Fallback a función `mail()` de PHP si no hay SMTP configurado.
- Pantalla de prueba de configuración SMTP en panel Admin.

#### RF-EMAIL-05 — Plantilla Base de Correos
- Todas las plantillas de correo comparten un layout HTML base con:
  - Encabezado: `library_logo` y `library_name`.
  - Pie de correo: `library_name`, `library_address`, `library_phone`, `library_email`, `library_schedule`.
  - Enlace a `library_website` para acceder al sistema.
- Los datos se toman dinámicamente de `system_settings` (RF-ADMIN-01).
- El campo `reply-to` de los correos usa `library_email`.
- Las fechas en el cuerpo del correo se formatean según `date_format` y `timezone`.

#### RF-EMAIL-02 — Cola de Correos
- Los correos se encolan en base de datos para envío asíncrono.
- Worker ejecutado por cron job cada 5 minutos.
- Reintento automático hasta 3 veces con backoff exponencial.
- Los correos fallidos se marcan y quedan visibles en el panel Admin.

#### RF-EMAIL-03 — Plantillas de Correo
| Evento                       | Destinatario  | Contenido |
|------------------------------|---------------|-----------|
| Bienvenida (nuevo usuario)   | Usuario       | Credenciales, enlace de acceso |
| Recuperación de contraseña   | Usuario       | Enlace de reset (60 min) |
| Confirmación de préstamo     | Usuario       | Detalle del libro, fecha vencimiento |
| Recordatorio (3 días antes)  | Usuario       | Alerta de vencimiento próximo |
| Préstamo vencido             | Usuario       | Alerta de mora, monto acumulado |
| Reserva disponible           | Usuario       | Libro disponible, plazo de retiro (48h) |
| Multa generada               | Usuario       | Monto, instrucciones de pago |
| Reserva cancelada            | Usuario       | Confirmación de cancelación |
| Asignación de lectura creada | Alumno        | Libro, link al catálogo, fecha límite, instrucciones |
| Recordatorio de asignación   | Alumno        | 48h antes de vencimiento: libro pendiente de cumplir |
| Incumplimiento de asignación | Docente       | Resumen de alumnos que no cumplieron al vencer |
| Sugerencia aprobada          | Docente       | Confirmación con nota del revisor |
| Sugerencia rechazada         | Docente       | Motivo del rechazo |

#### RF-EMAIL-04 — Preferencias de Notificación
- El usuario puede desactivar notificaciones no críticas.
- Las alertas de mora y vencimiento no son desactivables.

---

### 4.10 Reportes y Estadísticas

#### RF-REP-01 — Dashboard Principal
Métricas en tiempo real (o cache de 5 minutos):
- Total de libros en catálogo / total de copias
- Préstamos activos / vencidos hoy
- Nuevos usuarios este mes
- Multas pendientes (monto total)
- Libros más solicitados (top 5 visual)
- Préstamos por día (últimos 30 días, gráfico SVG)

#### RF-REP-02 — Reporte de Préstamos
- Selector de período con las siguientes opciones:
  - **Hoy** (desglose por hora)
  - **Esta semana** (desglose por día)
  - **Este mes** (desglose por día)
  - **Este año** (desglose por mes)
  - **Rango personalizado**: selector de fecha inicio y fecha fin (con hora)
- Filtros adicionales: usuario, libro, categoría, estado (activo/devuelto/vencido/perdido), bibliotecario responsable, **sede**.
- Columnas del reporte: N° préstamo, usuario, libro, ISBN, fecha/hora préstamo, fecha/hora vencimiento, fecha/hora devolución, estado, mora (horas), multa generada.
- Gráfico de tendencia SVG (enriquecible con JS del cliente).
- Totales: cantidad de préstamos, en mora, devueltos a tiempo, perdidos.
- Exportable: CSV (con BOM UTF-8), Excel (HTML), PDF con encabezado institucional (`library_name`, `library_logo`, `library_address` de RF-ADMIN-01).

#### RF-REP-03 — Reporte Total de Libros (Inventario)
- Resumen ejecutivo: total libros registrados, total copias físicas, total libros digitales, total libros activos, total dados de baja.
- Desglose por categoría: cantidad de títulos y copias por categoría.
- Desglose por tipo: físico vs digital.
- **Desglose por sede**: cantidad de títulos y copias por sede (solo libros físicos).
- Listado completo con: título, ISBN, categoría, tipo, copias totales, disponibles, prestadas, costo de reposición.
- Libros nunca prestados (candidatos a revisión).
- Libros con alta demanda (ratio préstamos/copias > umbral configurable).
- Libros dados de baja (con motivo y fecha).
- Valor total del inventario (suma de `replacement_cost` × copias totales).
- Exportable: CSV, Excel, PDF.

#### RF-REP-04 — Reporte de Usuarios
- Usuarios más activos.
- Usuarios con préstamos vencidos.
- Usuarios por tipo.
- Nuevos registros por mes.

#### RF-REP-05 — Reporte de Multas
- Multas pendientes por usuario.
- Multas cobradas en rango de fechas.
- Multas condonadas con motivo.
- Monto total recaudado por período.

#### RF-REP-06 — Gráficos
- Los gráficos se generan en SVG del lado del servidor (fallback sin JS garantizado).
- Se puede enriquecer la interactividad con JavaScript del lado del cliente (tooltips, animaciones).
- No se requieren librerías JS pesadas para renderizar los gráficos (Chart.js, D3, etc. son opcionales).
- Tipos: barras horizontales, dona/pie, línea con área rellena.
- Listos para impresión (CSS `@media print`).

#### RF-REP-07 — Reporte de Visitas Diarias
- El sistema registra y reporta las visitas al sistema web por período.
- Selector de período: hoy, esta semana, este mes, este año, rango personalizado.
- Métricas mostradas:
  - Visitas únicas por día (basadas en sesión/cookie anónima de 24h).
  - Visitantes anónimos vs. usuarios autenticados.
  - Páginas más visitadas (catálogo, búsqueda, ficha de libro, noticias).
  - Accesos a libros digitales (clics en enlace/descarga PDF).
  - Pico de visitas por hora del día (heatmap o gráfico de barras).
- Gráfico SVG de visitas por día (línea de tendencia).
- Comparativa: período actual vs. período anterior.
- Exportable: CSV, PDF.
- Los datos de visita no contienen información personal identificable.

#### RF-REP-08 — Panel de Estadísticas e Informes
- Sección dedicada `/informes` accesible para Admin y Bibliotecario.
- Agrupa todos los reportes en un menú lateral categorizado:
  - **Circulación**: préstamos activos, historial, mora, renovaciones.
  - **Inventario**: total de libros, nuevas adquisiciones, dados de baja.
  - **Usuarios**: activos, nuevos, con mora, por tipo.
  - **Finanzas**: multas pendientes, cobradas, condonadas.
  - **Tráfico**: visitas diarias, accesos a digitales, búsquedas populares.
- Cada sección permite seleccionar período y exportar.
- Dashboard con tarjetas KPI resumen al inicio de la sección.

---

### 4.11 Gestión de Archivos e Imágenes

#### RF-FILE-01 — Subida de Portadas
- Tipos permitidos: JPEG, PNG, WebP.
- Tamaño máximo: 2 MB.
- Validación de tipo real via `finfo` (no solo extensión).
- Regeneración via GD para eliminar EXIF y normalizar formato.
- Nombre de archivo: UUID v4 + extensión (sin datos originales en el nombre).

#### RF-FILE-02 — Almacenamiento Seguro
- Los archivos subidos se guardan fuera de `public_html` cuando sea posible.
- Si no es posible (hosting compartido), el directorio tiene `.htaccess` con `deny from all`.
- Servicio de archivos via controlador PHP con validación de rutas (`realpath()` prefix check).

#### RF-FILE-03 — Generación de PDFs
- Biblioteca: TCPDF.
- Documentos generados: comprobante de préstamo, carnet de usuario, etiquetas, reportes.
- Los PDFs se generan al vuelo y se sirven directamente o se guardan temporalmente.
- Los PDFs temporales se limpian automáticamente (cron, archivos > 1 hora).

---

### 4.12 Administración del Sistema

#### RF-ADMIN-01 — Configuración General
Panel de configuración con los siguientes parámetros:

| Parámetro                        | Valor Default | Descripción |
|----------------------------------|---------------|-------------|
| `loan_hours`                     | 72            | Horas de préstamo estándar (máximo para físicos) |
| `loan_hours_extended`            | 120           | Horas para usuarios docentes/especiales |
| `renewal_grace_hours`            | 2             | Horas de gracia para renovar tras vencimiento |
| `max_loans_per_member`           | 3             | Máximo de préstamos físicos simultáneos |
| `max_renewals`                   | 2             | Renovaciones permitidas por préstamo |
| `fine_per_hour`                  | 0.05          | Multa por hora de atraso (moneda local) |
| `reservation_hold_hours`         | 48            | Horas para retirar libro reservado |
| `block_loans_with_fines`         | true          | Bloquear préstamos si hay multas |
| `reminder_hours_before`          | 24            | Horas antes del vencimiento para recordatorio |
| `new_acquisition_days`           | 30            | Días que un libro se muestra como nueva adquisición |
| `news_on_home`                   | 3             | Número de noticias en la página principal |
| `max_fine_multiplier`            | 2.0           | Máximo: multa no puede exceder X × costo de reposición |
| `library_name`                   | —             | Nombre de la biblioteca (encabezado, PDFs, emails) |
| `library_address`                | —             | Dirección física (pie de página, PDFs, carnet) |
| `library_logo`                   | —             | Logo (encabezado, PDFs, carnet, emails). Archivo subido via panel Admin |
| `library_phone`                  | —             | Teléfono de contacto (pie de página, PDFs, emails) |
| `library_email`                  | —             | Correo institucional (pie de página, contacto público, emails como reply-to) |
| `library_website`                | —             | URL pública del sistema (QR de carnet, enlaces en emails) |
| `library_schedule`               | —             | Horario de atención (pie de página, página pública, emails de recordatorio) |
| `library_slogan`                 | —             | Lema o descripción corta (meta description, página pública) |
| `library_favicon`                | —             | Ícono de pestaña del navegador (ICO/PNG, subido via panel Admin) |
| `timezone`                       | America/Mexico_City | Zona horaria para cálculos de `due_at`, logs y reportes (`date_default_timezone_set`) |
| `locale`                         | es_MX         | Locale para formato de fechas, números y moneda (`setlocale`) |
| `date_format`                    | d/m/Y H:i     | Formato de fecha/hora en la UI y reportes (notación PHP `date()`) |
| `carnet_prefix`                  | BIB           | Prefijo del número de carnet (formato: `{prefix}-YYYY-NNNNN`) |
| `currency_symbol`                | $             | Símbolo de moneda local |

#### RF-ADMIN-06 — Gestión de Sedes (Sucursales)
- El Admin puede crear, editar y desactivar sedes físicas de la biblioteca.
- Campos de una sede:
  - Nombre (obligatorio, único) — ej: "Sede Central", "Facultad de Ingeniería", "Campus Norte"
  - Código corto (obligatorio, único, 2-10 caracteres) — ej: `CENTRAL`, `ING`, `NORTE`
  - Dirección completa (obligatorio)
  - Teléfono (opcional)
  - Email de contacto (opcional)
  - Horario de atención (opcional)
  - Responsable (FK → users, opcional) — bibliotecario a cargo
  - Estado: `active` | `inactive`
  - Orden de visualización (para listados públicos)
- Una sede no se puede desactivar si tiene libros activos con préstamos pendientes.
- Al desactivar una sede, sus libros activos se pueden transferir a otra sede.
- El sistema debe tener al menos una sede activa (la principal).
- La sede marcada como `is_main = true` es la que se usa como sede principal por defecto.
- Los datos de la sede principal alimentan `library_address`, `library_phone` y `library_schedule` cuando estos no se configuran explícitamente en `system_settings`.
- La ficha pública de cada libro físico muestra el nombre de la sede y su dirección.
- Los correos de préstamo y devolución incluyen la dirección de la sede donde se realizó la operación.

#### RF-ADMIN-02 — Gestión de Usuarios del Sistema
- El Admin puede crear, editar, suspender y eliminar usuarios (Admin/Bibliotecario).
- No se puede eliminar el último usuario Admin activo.
- Se puede forzar el cambio de contraseña en el próximo acceso.

#### RF-ADMIN-03 — Logs de Auditoría
- Registro de todas las acciones críticas: préstamos, devoluciones, multas, accesos, cambios de configuración.
- Visualización con filtros por usuario, acción, fecha.
- No editable ni eliminable por ningún rol.
- Rotación automática de logs: compresión de archivos > 30 días.

#### RF-ADMIN-04 — Migraciones de Base de Datos
- Script `bin/migrate.php` para aplicar migraciones versionadas.
- Tabla `migrations` para rastrear migraciones aplicadas.
- Las migraciones son irreversibles por diseño (sin rollback automático).

#### RF-ADMIN-05 — Respaldo y Mantenimiento
- Instrucciones de respaldo con `mysqldump` (VPS) o panel de hosting.
- Comando de verificación de integridad del sistema.
- Limpieza de archivos temporales desde panel Admin.

---

### 4.13 Módulo Docente

#### RF-DOC-01 — Gestión de Grupos de Alumnos
- El Docente puede crear grupos que representan clases, materias o cohortes.
- Campos de un grupo: nombre, descripción, materia/asignatura, ciclo escolar (ej: 2026-1), estado (`active` / `archived`).
- Los alumnos se agregan al grupo buscándolos por nombre, número de carnet o documento.
- Un alumno puede pertenecer a múltiples grupos simultáneos.
- El Docente puede archivar un grupo al finalizar el ciclo; el historial de asignaciones y actividad se conserva.
- El Admin puede ver y gestionar todos los grupos de todos los docentes.

#### RF-DOC-02 — Panel del Docente
- Pantalla de inicio específica para el rol Docente con:
  - Resumen de grupos activos con conteo de alumnos y asignaciones vigentes.
  - Alertas: alumnos con asignaciones próximas a vencer, alumnos sin actividad en los últimos 7 días.
  - Mis sugerencias de libros (estado: pendiente / aprobada / rechazada).
  - Acceso rápido a "Ver actividad", "Nueva asignación", "Sugerir libro".
- El panel es exclusivo del Docente; no comparte vistas con el panel del Usuario ni del Bibliotecario.

#### RF-DOC-03 — Asignaciones de Lectura
- El Docente puede crear asignaciones de lectura vinculadas a un libro (físico o digital) y a un grupo o alumnos individuales.
- Campos de una asignación:
  - Libro asignado (obligatorio)
  - Grupo y/o alumnos individuales (obligatorio)
  - Fecha límite (opcional)
  - Tipo de cumplimiento: `loan` (debe tomar prestado el físico) | `digital` (debe acceder al digital) | `any` (cualquiera de los dos) | `both` (ambos)
  - Instrucciones o nota para el alumno (texto libre)
- Las asignaciones aparecen en el panel del alumno en la sección "Mis lecturas asignadas".
- El alumno ve: título del libro, docente asignante, fecha límite, estado (pendiente / cumplida / vencida).
- El sistema marca automáticamente la asignación como `fulfilled` cuando detecta que el alumno realizó la acción requerida (préstamo o acceso digital) sobre el libro asignado.

#### RF-DOC-04 — Notificaciones de Asignación
- Al crear una asignación, el sistema envía correo a cada alumno asignado con: título del libro, link al catálogo, fecha límite e instrucciones.
- Si la asignación tiene fecha límite, se envía recordatorio 48 horas antes.
- Al vencer la fecha límite sin cumplimiento, el Docente recibe un resumen de incumplimientos.
- Las notificaciones de asignación son adicionales a las de préstamo; el alumno las ve diferenciadas.

#### RF-DOC-05 — Estadísticas de Actividad del Grupo
Vista de grupo disponible para el Docente con las siguientes métricas:

| Métrica | Descripción |
|---------|-------------|
| Alumnos activos | Usuarios con al menos un préstamo o acceso digital en los últimos 30 días |
| Alumnos inactivos | Usuarios sin ninguna actividad en los últimos 30 días |
| Préstamos activos | Libros físicos actualmente prestados a usuarios del grupo |
| Préstamos vencidos | Préstamos en mora de usuarios del grupo |
| Asignaciones cumplidas | % de asignaciones activas marcadas como `fulfilled` |
| Accesos digitales | Total de clics/descargas en libros digitales por usuarios del grupo |
| Libro más solicitado | Libro con más préstamos o accesos entre los usuarios del grupo |
| Multas activas | Cantidad de alumnos del grupo con multas pendientes (sin montos) |

- Las estadísticas se muestran con gráficos SVG simples (barras por alumno, dona de cumplimiento).
- Período configurable: semana actual, mes actual, ciclo completo, o rango personalizado.
- Exportable a PDF y CSV.

#### RF-DOC-06 — Perfil de Actividad Individual del Alumno
Desde el perfil de un alumno dentro de su grupo, el Docente puede ver:
- **Préstamos registrados**: listado de todos los libros físicos prestados (título, fecha, estado, devuelto o activo).
- **Accesos a digitales**: libros digitales a los que el alumno ha accedido (título, fecha/hora del primer y último acceso, total de accesos).
- **Asignaciones**: todas las asignaciones que el alumno tiene de este Docente con su estado de cumplimiento.
- **Indicador de último acceso al sistema**: cuándo fue la última vez que el alumno inició sesión.
- **Estado de multas**: indicador simple (sin mora / tiene mora) sin revelar el monto exacto.
- El Docente **no puede ver**: datos personales del alumno más allá del nombre y carnet, contraseñas, historial con otros docentes, ni detalles financieros de multas.

#### RF-DOC-07 — Verificación de Cumplimiento
- El Docente puede consultar, para cualquier libro de su asignación, si un alumno específico:
  - Tiene o tuvo prestado ese libro (`loan` registrado en `loans`).
  - Ha accedido al libro digital (`digital_access_count` > 0 para ese alumno y libro).
  - Tiene una reserva activa del libro.
- La verificación se muestra como una tabla: alumno | préstamo (sí/no/fecha) | acceso digital (sí/no/fecha) | asignación cumplida (sí/no).
- El Docente puede exportar esta tabla a CSV o PDF.

#### RF-DOC-08 — Sugerencias de Libros para Adquisición
- El Docente puede enviar sugerencias de libros para que la biblioteca los adquiera.
- Una sugerencia contiene: título, autor, ISBN (opcional), motivo/justificación, materia relacionada, urgencia.
- El sistema valida automáticamente si el ISBN ya existe en el catálogo y advierte antes de enviar.
- Si el libro ya fue sugerido por otro docente, se notifica y se puede co-suscribir la sugerencia existente.
- Estados del flujo: `pending` → `approved` | `rejected` | `duplicate`.
- El Bibliotecario o Admin puede aprobar la sugerencia, lo que la convierte en una tarea de adquisición visible en el panel de administración.
- Al aprobar o rechazar, el Docente recibe notificación con el motivo.
- El Docente puede ver el historial de todas sus sugerencias y su estado actual.

#### RF-DOC-09 — Reporte del Grupo Exportable
- Reporte PDF/CSV con:
  - Encabezado: nombre del grupo, ciclo, nombre del docente, fecha de generación.
  - Tabla de alumnos: nombre, carnet, préstamos en el período, accesos digitales, asignaciones cumplidas/total, estado (sin mora / con mora).
  - Resumen de asignaciones activas con % de cumplimiento.
  - Libros más consultados por el grupo.
- El reporte solo contiene datos del grupo del Docente solicitante.
- El Admin puede generar este reporte para cualquier grupo y docente.

#### RF-DOC-10 — Vista del Alumno: Mis Lecturas Asignadas
- En el panel del Usuario (estudiante), aparece una sección "Lecturas asignadas".
- Muestra todas las asignaciones activas que le han enviado sus docentes.
- Por cada asignación: libro, docente asignante, fecha límite, tipo de cumplimiento esperado, estado.
- El alumno puede hacer clic en el libro para ir directamente a su ficha en el catálogo.
- Las asignaciones vencidas sin cumplir aparecen resaltadas; las cumplidas con marca de completado.
- El alumno no puede modificar ni rechazar asignaciones, solo visualizarlas.

### 4.14 Noticias y Comunicados

#### RF-NEWS-01 — Publicación de Noticias
- Admin y Bibliotecario pueden crear, editar y eliminar noticias/comunicados.
- Campos de una noticia:
  - Título (obligatorio)
  - Contenido (editor de texto con soporte HTML básico: párrafos, negrita, listas, enlaces)
  - Imagen destacada (opcional, JPG/PNG/WebP, máximo 1 MB)
  - Categoría de noticia (Eventos, Comunicados, Nuevas Adquisiciones, Horarios, General)
  - Estado: `borrador` | `publicado` | `archivado`
  - Fecha de publicación (puede ser futura para programar)
  - Visibilidad: `pública` (sin sesión) | `privada` (solo usuarios autenticados)
  - Autor (usuario que publica)

#### RF-NEWS-02 — Listado Público de Noticias
- Página `/noticias` accesible sin sesión.
- Muestra noticias con visibilidad `pública` y estado `publicado`.
- Ordenadas por fecha de publicación descendente.
- Paginación (10 por página, configurable).
- Filtro por categoría.
- Vista de tarjetas con: imagen destacada (thumbnail), título, extracto (primeros 150 caracteres), fecha, categoría.

#### RF-NEWS-03 — Detalle de Noticia
- Página individual `/noticias/{slug}` con el contenido completo.
- Muestra: título, imagen destacada, fecha, autor, categoría, contenido HTML.
- Noticias privadas redirigen al login si el visitante no tiene sesión.
- Navegación entre noticias: anterior / siguiente.

#### RF-NEWS-04 — Gestión de Noticias (Admin/Bibliotecario)
- Panel en `/admin/noticias` con listado de todas las noticias (todos los estados).
- Filtros: estado, categoría, autor, fecha.
- Acciones: crear, editar, cambiar estado, eliminar (soft delete).
- Vista previa antes de publicar.
- El slug se genera automáticamente desde el título (editable).

#### RF-NEWS-05 — Noticias en Portada
- Las 3 noticias públicas más recientes aparecen en la página principal (RF-PUB-01).
- Configurable: número de noticias en portada (`news_on_home`, default: 3).

---

## 5. Requerimientos No Funcionales

### 5.1 Rendimiento

| Métrica                          | Objetivo |
|----------------------------------|----------|
| Tiempo de respuesta (p95)        | < 500ms  |
| Tiempo de respuesta (p99)        | < 1500ms |
| Largest Contentful Paint (LCP)   | < 2.5s   |
| First Input Delay (FID)          | < 100ms  |
| Cumulative Layout Shift (CLS)    | < 0.1    |
| Consultas SQL por página         | ≤ 10     |
| Tamaño de página (sin imágenes)  | < 100KB  |

**Estrategias:**
- OPcache habilitado con `validate_timestamps=0` en producción.
- Cache de consultas con APCu (fallback a FileCache).
- Paginación keyset (cursor) para listados grandes.
- Imágenes servidas en WebP con lazy loading.
- JavaScript solo en el frontend (cliente); ninguna lógica de negocio en JS.
- Sin librerías JavaScript pesadas en el servidor ni frameworks JS como React, Vue o Angular.

### 5.2 Seguridad

- OWASP Top 10 mitigado (ver SKILL skill-security).
- HTTPS obligatorio en producción (HSTS con `max-age=31536000`).
- Contraseñas: ARGON2ID con `memory_cost=65536, time_cost=4`.
- Sin exposición de rutas internas, stack traces ni configuración en producción.
- Cabeceras de seguridad completas:
  - `Content-Security-Policy` con nonce
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy`
- Auditoría de dependencias: `composer audit` en cada despliegue.
- Tokens de sesión y CSRF con `random_bytes()` (nunca `rand()` o `mt_rand()`).
- Preparación contra SQL injection: solo PDO con parámetros enlazados.
- Protección CSRF en todos los formularios mutantes.

### 5.3 Compatibilidad de Despliegue

El sistema debe operar sin modificaciones en los siguientes entornos:

| Entorno             | Requisitos mínimos |
|---------------------|-------------------|
| VPS (Nginx+PHP-FPM) | PHP 8.1+, MariaDB 10.5+, `mod_rewrite` o `try_files` |
| cPanel (Apache)     | PHP 8.1+, MySQL/MariaDB, `mod_rewrite` |
| Plesk               | PHP 8.1+, MariaDB, `.htaccess` habilitado |
| DirectAdmin         | PHP 8.1+, MySQL/MariaDB |
| Hosting compartido  | PHP 8.1+, MySQL 5.7+ o MariaDB 10.3+ |

**Extensiones PHP requeridas:**
`pdo`, `pdo_mysql`, `mbstring`, `gd`, `json`, `fileinfo`, `openssl`, `intl`

**Extensiones PHP opcionales (con fallback):**
`apcu` (fallback FileCache), `zip`, `imagick`

### 5.4 Accesibilidad

- Cumplimiento WCAG 2.1 nivel AA.
- Navegación completa por teclado (Tab, flechas, Enter, Escape).
- ARIA labels en componentes interactivos.
- Contraste de color mínimo 4.5:1 (texto normal) y 3:1 (texto grande).
- Soporte para lectores de pantalla (screen readers).
- Textos alternativos en todas las imágenes.
- Sin contenido que parpadee más de 3 veces por segundo.

### 5.5 Mantenibilidad

- Arquitectura MVC sin framework (implementación propia).
- Autoloader PSR-4 sin Composer (o con Composer si disponible).
- Separación estricta entre lógica de negocio, datos y presentación.
- Cobertura de tests unitarios ≥ 80% en servicios críticos.
- Tests de integración con transacciones y rollback (sin datos persistentes).
- Logs estructurados (JSON) en producción, texto en desarrollo.
- Documentación de migraciones de base de datos.
- Sin código muerto ni dependencias no utilizadas.

---

## 6. Restricciones Técnicas

### 6.1 Stack Tecnológico Obligatorio

| Componente       | Tecnología      | Versión mínima | Notas |
|------------------|-----------------|----------------|-------|
| Lenguaje         | PHP             | 8.1            | Enums, readonly, fibers |
| Base de datos    | MariaDB / MySQL | 10.5 / 8.0     | FULLTEXT, JSON, Window functions |
| Servidor web     | Nginx o Apache  | Cualquiera     | Configuraciones incluidas para ambos |
| Generación PDF   | TCPDF           | 6.x            | Única librería externa permitida para PDF |
| Email            | PHPMailer       | 6.x            | Única librería externa permitida para email |
| QR Codes         | phpqrcode       | Último commit  | Archivo único, sin dependencias |
| Frontend CSS     | Tailwind CSS    | CDN o compilado| O CSS personalizado equivalente |
| Frontend JS      | Vanilla JS      | ES2020+        | Solo en cliente; sin frameworks JS (React, Vue, Angular) |

### 6.2 Prohibiciones Explícitas

- **No** frameworks PHP (Laravel, Symfony, CodeIgniter, Yii, CakePHP).
- **No** ORM con migraciones automáticas (Eloquent, Doctrine).
- **No** frameworks JavaScript de UI (React, Vue, Angular) — se permite Vanilla JS en el cliente.
- **No** lógica de negocio en JavaScript del lado del cliente — toda la lógica reside en PHP.
- **No** Redis, Memcached ni otros servicios de caché externos (solo APCu + FileCache).
- **No** Node.js en el servidor para ninguna funcionalidad.
- **No** Docker en el entorno de producción final.
- **No** Elasticsearch ni motores de búsqueda externos.
- **No** servicios de cola externos (RabbitMQ, SQS) — solo tabla de cola en MariaDB.

### 6.3 Composer

- El uso de Composer es **opcional** pero recomendado para PHPMailer y TCPDF.
- Si el hosting no permite Composer, se incluyen alternativas de instalación manual.
- Solo se permiten dependencias de producción explícitamente aprobadas:
  - `phpmailer/phpmailer`
  - `tecnickcom/tcpdf`
- `composer audit` debe ejecutarse en cada despliegue.

---

## 7. Reglas de Negocio

### 7.1 Préstamos

| Regla | Descripción |
|-------|-------------|
| RN-L1 | Un usuario no puede tener más de `max_loans_per_member` préstamos físicos simultáneos (default: **3**). |
| RN-L2 | No se puede prestar un libro sin copias disponibles. |
| RN-L3 | No se puede prestar a un usuario con préstamos vencidos sin devolver. |
| RN-L4 | Si `block_loans_with_fines=true`, no se puede prestar a usuarios con multas pendientes. |
| RN-L5 | El vencimiento se calcula como: `due_at = loan_at + INTERVAL loan_hours HOUR`. El plazo por defecto es **72 horas**. |
| RN-L6 | Los usuarios de tipo Docente/Especial usan `loan_hours_extended` (default: 120h). |
| RN-L7 | No se puede renovar si hay reservas activas del mismo libro. |
| RN-L8 | No se puede renovar si ya se alcanzó el límite de `max_renewals`. |
| RN-L9 | Los libros digitales nunca generan préstamo, multa ni reserva; su enlace es de acceso libre. |
| RN-L10 | La multa por mora se calcula por hora: `horas_atraso × fine_per_hour`. Las fracciones de hora cuentan como hora completa. |
| RN-L11 | La multa total no puede exceder `replacement_cost × max_fine_multiplier` del libro. Si se alcanza el tope, se registra como pérdida. |

### 7.2 Reservas

| Regla | Descripción |
|-------|-------------|
| RN-R1 | Un usuario no puede tener más de 5 reservas activas simultáneas. |
| RN-R2 | No se puede reservar un libro que el usuario ya tiene prestado. |
| RN-R3 | Al quedar disponible, el sistema notifica al primero en la cola (FIFO). |
| RN-R4 | El usuario tiene `reservation_hold_hours` horas para retirar (default: 48h). |
| RN-R5 | Si no retira en el plazo, la reserva se cancela automáticamente (cron). |
| RN-R6 | La cancelación automática activa notificación al siguiente en la cola. |

### 7.3 Multas

| Regla | Descripción |
|-------|-------------|
| RN-F1 | La multa se calcula como: `CEIL(horas_atraso) × fine_per_hour` (horas redondeadas hacia arriba). |
| RN-F2 | El cálculo comienza en el momento exacto del vencimiento (`due_at`). |
| RN-F3 | La multa se genera automáticamente al registrar la devolución tardía. |
| RN-F4 | Se puede registrar pago parcial; la multa queda como "parcialmente pagada". |
| RN-F5 | Las multas condonadas requieren justificación escrita obligatoria. |
| RN-F6 | La condonación queda registrada en auditoría con usuario y motivo. |
| RN-F7 | La multa total no puede exceder `replacement_cost × max_fine_multiplier` del libro (configurable, default ×2). |
| RN-F8 | Si la multa alcanza el tope de `replacement_cost`, se genera automáticamente un caso de pérdida y el libro se descuenta del inventario disponible. |
| RN-F9 | En caso de pérdida declarada, la multa equivale al `replacement_cost` completo del libro (no al precio de adquisición). |

### 7.4 Docente

| Regla | Descripción |
|-------|-------------|
| RN-D1 | El rol Docente hereda todos los permisos del rol Usuario. |
| RN-D2 | Un Docente solo puede ver actividad de alumnos que pertenecen a sus grupos activos. |
| RN-D3 | El Docente no puede ver montos exactos de multas de sus alumnos, solo el indicador (sin mora / con mora). |
| RN-D4 | El Docente no puede modificar datos personales, préstamos ni multas de sus alumnos; solo tiene acceso de lectura. |
| RN-D5 | Una asignación de lectura se marca como `fulfilled` automáticamente cuando el sistema detecta que el alumno tomó prestado el libro (si `fulfillment_type = loan` o `any`) o lo accedió digitalmente (si `fulfillment_type = digital` o `any`). |
| RN-D6 | Si `fulfillment_type = both`, la asignación requiere que el alumno realice ambas acciones para marcarse como cumplida. |
| RN-D7 | Las sugerencias de libros con ISBN existente en el catálogo se bloquean con advertencia; el docente debe confirmar para continuar. |
| RN-D8 | Un docente no puede co-suscribirse a su propia sugerencia. |
| RN-D9 | El Admin tiene acceso a todos los grupos, asignaciones, actividades y sugerencias de todos los docentes. |
| RN-D10 | Los datos de actividad de alumnos (préstamos, accesos) visibles para el Docente son exactamente los mismos que ya están disponibles en el sistema; no se recolecta información adicional. |

### 7.5 Usuarios

| Regla | Descripción |
|-------|-------------|
| RN-M1 | El correo electrónico es único en el sistema. |
| RN-M2 | El número de documento de identidad es único. |
| RN-M3 | Un usuario Bloqueado no puede acceder al sistema. |
| RN-M4 | Un usuario Suspendido puede acceder pero no puede crear préstamos ni reservas. |
| RN-M5 | No se puede dar de baja un usuario con préstamos activos. |
| RN-M6 | La contraseña debe tener mínimo 8 caracteres con al menos una mayúscula, un número y un símbolo. |

---

## 8. Modelo de Datos

### 8.1 Entidades Principales

```
library_branches
├── id (PK)
├── code (VARCHAR 10, UNIQUE)      ← código corto: CENTRAL, ING, NORTE
├── name (VARCHAR 150, UNIQUE)
├── address (TEXT)
├── phone (VARCHAR 30, NULL)
├── email (VARCHAR 150, NULL)
├── schedule (TEXT, NULL)           ← horario de atención
├── manager_id (FK → users, NULL)  ← bibliotecario responsable
├── is_main (TINYINT 1, default 0) ← sede principal
├── status (ENUM: active, inactive)
├── sort_order (TINYINT, default 0)
├── created_at
└── updated_at

books
├── id (PK)
├── isbn_13 (CHAR 13, UNIQUE, NULL para digitales sin ISBN)
├── title
├── authors (JSON array)
├── publisher
├── publication_year
├── category_id (FK → categories)
├── branch_id (FK → library_branches, NULL) ← sede donde está el libro (NULL para digitales)
├── book_type (ENUM: physical, digital)
├── description
├── pages
├── language (CHAR 2)
├── cover_image
├── location (estante/sección/piso dentro de la sede, solo físicos)
├── digital_url (TEXT, NULL — URL externa o ruta interna del PDF)
├── acquisition_price (DECIMAL 8,2, NULL)
├── replacement_cost (DECIMAL 8,2, NOT NULL) ← costo de reposición obligatorio
├── acquisition_date
├── acquired_at (DATETIME) ← para nuevas adquisiciones
├── is_new_acquisition (TINYINT 1, default 1)
├── total_copies (TINYINT, default 0)
├── available_copies (TINYINT, default 0)
├── digital_access_count (INT, default 0) ← contador de accesos
├── is_active (TINYINT 1, default 1)
├── deactivated_at (DATETIME, NULL)
├── deactivated_by (FK → users, NULL)
├── deactivation_reason (TEXT, NULL)
├── created_at
└── updated_at

users
├── id (PK)
├── user_number (UNIQUE)
├── name
├── email (UNIQUE)
├── document_number (UNIQUE)
├── phone
├── address
├── birthdate
├── photo
├── role (ENUM: admin, librarian, teacher, member, guest)
├── user_type (ENUM: student, teacher, external, staff)
├── status (ENUM: active, suspended, blocked, inactive)
├── password_hash
├── remember_token
├── email_verified_at
├── force_password_change (TINYINT 1)
├── created_at
└── updated_at

loans
├── id (PK)
├── book_id (FK → books)       ← solo libros físicos
├── user_id (FK → users)
├── librarian_id (FK → users)
├── branch_id (FK → library_branches) ← sede donde se realizó el préstamo
├── loan_at (DATETIME)         ← fecha y hora exacta del préstamo
├── due_at (DATETIME)          ← fecha y hora exacta de vencimiento
├── returned_at (DATETIME, NULL si activo)
├── loan_hours_applied (SMALLINT) ← horas configuradas al momento del préstamo
├── renewals_count (TINYINT, default 0)
├── status (ENUM: active, returned, overdue, lost)
├── notes
└── created_at

reservations
├── id (PK)
├── book_id (FK → books)
├── user_id (FK → users)
├── queue_position (INT)
├── status (ENUM: waiting, notified, fulfilled, cancelled, expired)
├── notified_at (NULL)
├── expires_at (NULL, se llena al notificar)
├── created_at
└── updated_at

fines
├── id (PK)
├── loan_id (FK → loans)
├── user_id (FK → users)
├── amount (DECIMAL 8,2)
├── hours_overdue (INT)        ← horas de atraso (reemplaza days_overdue)
├── replacement_cost_at_fine (DECIMAL 8,2) ← costo de reposición al momento de la multa
├── reason (ENUM: overdue, damage, loss)
├── status (ENUM: pending, partially_paid, paid, waived)
├── amount_paid (DECIMAL 8,2, default 0)
├── waiver_reason (TEXT, NULL)
├── waived_by (FK → users, NULL)
├── created_at
└── updated_at

categories
├── id (PK)
├── name (UNIQUE)
├── slug (UNIQUE)
├── description
├── fine_per_day_override (DECIMAL 5,2, NULL)
├── loan_days_override (TINYINT, NULL)
└── created_at

email_queue
├── id (PK)
├── to_email
├── to_name
├── subject
├── body_html
├── body_text
├── status (ENUM: pending, sent, failed)
├── attempts (TINYINT, default 0)
├── scheduled_at
├── sent_at (NULL)
├── error_message (TEXT, NULL)
└── created_at

audit_logs
├── id (PK)
├── user_id (FK → users, NULL)
├── action (VARCHAR 100)
├── entity_type (VARCHAR 50)
├── entity_id (INT, NULL)
├── old_values (JSON, NULL)
├── new_values (JSON, NULL)
├── ip_address
├── user_agent
└── created_at

system_settings
├── key (PK, VARCHAR 100)
├── value (TEXT)
├── type (ENUM: string, integer, decimal, boolean, json)
└── updated_at

password_resets
├── id (PK)
├── user_id (FK → users)
├── token_hash (CHAR 64, UNIQUE)
├── used_at (NULL)
├── expires_at
└── created_at

search_log
├── id (PK)
├── query (VARCHAR 255)
├── results_count (INT)
├── user_id (FK → users, NULL)
├── ip_address
└── created_at

visits_log
├── id (PK)
├── session_token (CHAR 64)    ← hash anónimo de sesión/cookie
├── user_id (FK → users, NULL) ← NULL si visitante anónimo
├── ip_hash (CHAR 64)          ← SHA-256 de IP (nunca IP en texto claro)
├── user_agent_category (ENUM: desktop, mobile, tablet, bot)
├── page (VARCHAR 200)         ← ruta visitada
├── visited_at (DATETIME)
└── created_at

news
├── id (PK)
├── title
├── slug (UNIQUE)
├── content (TEXT)             ← HTML básico saneado
├── excerpt (VARCHAR 300, NULL)
├── featured_image (VARCHAR 255, NULL)
├── category (ENUM: events, announcements, acquisitions, schedules, general)
├── status (ENUM: draft, published, archived)
├── visibility (ENUM: public, private)
├── author_id (FK → users)
├── published_at (DATETIME, NULL)
├── created_at
└── updated_at

teacher_groups
├── id (PK)
├── teacher_id (FK → users)   ← rol: teacher
├── name
├── description
├── subject                   ← materia/asignatura
├── school_year               ← ciclo escolar (ej: 2026-1)
├── status (ENUM: active, archived)
├── created_at
└── updated_at

teacher_group_students
├── id (PK)
├── group_id (FK → teacher_groups)
├── student_id (FK → users)
└── enrolled_at (DATETIME)

reading_assignments
├── id (PK)
├── teacher_id (FK → users)
├── group_id (FK → teacher_groups, NULL) ← NULL si es asignación individual
├── book_id (FK → books)
├── title                     ← nombre descriptivo de la asignación
├── instructions (TEXT, NULL)
├── fulfillment_type (ENUM: loan, digital, any, both)
├── due_at (DATETIME, NULL)
├── status (ENUM: active, archived)
├── created_at
└── updated_at

reading_assignment_students
├── id (PK)
├── assignment_id (FK → reading_assignments)
├── student_id (FK → users)
├── status (ENUM: pending, fulfilled, overdue)
├── fulfilled_at (DATETIME, NULL)
├── fulfillment_evidence (ENUM: loan, digital, both, NULL)
└── notified_at (DATETIME, NULL)

book_suggestions
├── id (PK)
├── suggested_by (FK → users) ← rol: teacher o admin
├── title
├── author (VARCHAR 255, NULL)
├── isbn (VARCHAR 20, NULL)
├── subject                   ← materia relacionada
├── justification (TEXT)
├── urgency (ENUM: normal, high)
├── status (ENUM: pending, approved, rejected, duplicate)
├── reviewed_by (FK → users, NULL)
├── review_note (TEXT, NULL)
├── reviewed_at (DATETIME, NULL)
├── parent_suggestion_id (FK → book_suggestions, NULL) ← co-suscripción
├── created_at
└── updated_at

digital_access_log
├── id (PK)
├── book_id (FK → books)      ← solo libros digitales
├── user_id (FK → users, NULL)← NULL si acceso anónimo
├── session_token (CHAR 64)
├── ip_hash (CHAR 64)
├── access_type (ENUM: link_click, pdf_download)
├── accessed_at (DATETIME)
└── created_at
```

### 8.2 Índices Críticos

```sql
-- Búsqueda FULLTEXT
ALTER TABLE books ADD FULLTEXT INDEX ft_books (title, authors, description);

-- Libros activos por tipo
CREATE INDEX idx_books_type_active ON books (book_type, is_active);

-- Libros por sede
CREATE INDEX idx_books_branch ON books (branch_id, is_active);

-- Préstamos por sede
CREATE INDEX idx_loans_branch_status ON loans (branch_id, status);

-- Sede principal
CREATE UNIQUE INDEX idx_branches_code ON library_branches (code);

-- Nuevas adquisiciones
CREATE INDEX idx_books_new_acquisition ON books (is_new_acquisition, acquired_at);

-- Préstamos activos por usuario
CREATE INDEX idx_loans_user_status ON loans (user_id, status);

-- Préstamos vencidos (para cron — ahora con DATETIME)
CREATE INDEX idx_loans_due_status ON loans (due_at, status);

-- Cola de reservas
CREATE INDEX idx_reservations_book_queue ON reservations (book_id, queue_position, status);

-- Multas pendientes por usuario
CREATE INDEX idx_fines_user_status ON fines (user_id, status);

-- Cola de email
CREATE INDEX idx_email_queue_status_scheduled ON email_queue (status, scheduled_at);

-- Visitas por día
CREATE INDEX idx_visits_date ON visits_log (visited_at);
CREATE INDEX idx_visits_session ON visits_log (session_token, visited_at);

-- Noticias publicadas
CREATE INDEX idx_news_status_visibility ON news (status, visibility, published_at);
CREATE UNIQUE INDEX idx_news_slug ON news (slug);

-- Grupos del docente
CREATE INDEX idx_teacher_groups_teacher ON teacher_groups (teacher_id, status);

-- Alumnos por grupo
CREATE INDEX idx_group_students_group ON teacher_group_students (group_id);
CREATE INDEX idx_group_students_student ON teacher_group_students (student_id);

-- Asignaciones activas por docente y alumno
CREATE INDEX idx_assignments_teacher ON reading_assignments (teacher_id, status);
CREATE INDEX idx_assignment_students_status ON reading_assignment_students (assignment_id, status);
CREATE INDEX idx_assignment_students_student ON reading_assignment_students (student_id, status);

-- Sugerencias de libros
CREATE INDEX idx_suggestions_status ON book_suggestions (status, created_at);
CREATE INDEX idx_suggestions_teacher ON book_suggestions (suggested_by, status);

-- Accesos a digitales por libro y usuario
CREATE INDEX idx_digital_access_book ON digital_access_log (book_id, accessed_at);
CREATE INDEX idx_digital_access_user ON digital_access_log (user_id, book_id);
```

---

## 9. Integraciones Externas

### 9.1 Open Library API

- **URL base:** `https://openlibrary.org/api/books`
- **Propósito:** Lookup de metadatos por ISBN.
- **Método:** GET con `bibkeys=ISBN:XXXXXXXXXX&format=json&jscmd=data`
- **Timeout:** 5 segundos.
- **Cache:** 30 días en FileCache.
- **Fallback:** Formulario manual si la API no responde.
- **Seguridad:** Sin envío de datos de usuarios a la API (solo ISBN).

### 9.2 SMTP (correo saliente)

- PHPMailer con configuración vía variables de entorno.
- Soporte TLS (puerto 587) y SSL (puerto 465).
- Fallback a `mail()` de PHP si no hay SMTP.
- Registros SPF, DKIM y DMARC recomendados para reducir spam.

### 9.3 Lectores de Código de Barras

- Lectores USB HID (se comportan como teclado).
- El input se detecta por velocidad de escritura (< 50ms entre caracteres).
- Compatible con formatos EAN-13, EAN-8, Code-128.
- Sin SDK ni driver adicional — JavaScript puro.

---

## 10. Estructura del Proyecto

```
biblioteca/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── PublicController.php      ← catálogo, home, nuevas adquisiciones
│   │   ├── BookController.php
│   │   ├── LoanController.php
│   │   ├── ReservationController.php
│   │   ├── FineController.php
│   │   ├── UserController.php
│   │   ├── SearchController.php
│   │   ├── BarcodeController.php
│   │   ├── ReportController.php
│   │   ├── NewsController.php
│   │   ├── TeacherController.php     ← panel docente, grupos, asignaciones
│   │   ├── AssignmentController.php  ← asignaciones de lectura
│   │   ├── SuggestionController.php  ← sugerencias de libros
│   │   ├── BranchController.php      ← gestión de sedes/sucursales
│   │   └── AdminController.php
│   ├── Models/
│   │   ├── Book.php
│   │   ├── User.php
│   │   ├── Loan.php
│   │   ├── Reservation.php
│   │   ├── Fine.php
│   │   ├── News.php
│   │   ├── Visit.php
│   │   ├── Branch.php
│   │   ├── TeacherGroup.php
│   │   ├── ReadingAssignment.php
│   │   ├── BookSuggestion.php
│   │   └── Category.php
│   ├── Repositories/
│   │   ├── BookRepository.php
│   │   ├── UserRepository.php
│   │   ├── LoanRepository.php
│   │   ├── ReservationRepository.php
│   │   ├── FineRepository.php
│   │   ├── BranchRepository.php
│   │   ├── NewsRepository.php
│   │   ├── VisitRepository.php
│   │   ├── TeacherGroupRepository.php
│   │   ├── ReadingAssignmentRepository.php
│   │   ├── BookSuggestionRepository.php
│   │   └── ReportRepository.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── BookService.php
│   │   ├── LoanService.php
│   │   ├── ReservationService.php
│   │   ├── FineService.php
│   │   ├── SearchService.php
│   │   ├── BarcodeService.php
│   │   ├── QrService.php
│   │   ├── LabelService.php
│   │   ├── ImageService.php
│   │   ├── PdfService.php
│   │   ├── MailService.php
│   │   ├── MailQueue.php
│   │   ├── NotificationService.php
│   │   ├── ReportService.php
│   │   ├── ChartService.php
│   │   ├── CacheService.php
│   │   ├── NewsService.php
│   │   ├── VisitTracker.php
│   │   ├── TeacherService.php
│   │   ├── AssignmentService.php
│   │   ├── BookSuggestionService.php
│   │   └── IsbnLookupService.php
│   ├── Core/
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Container.php
│   │   ├── View.php
│   │   ├── Database.php
│   │   ├── Session.php
│   │   ├── Validator.php
│   │   └── Logger.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── GuestMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   ├── SecurityHeadersMiddleware.php
│   │   └── RequestLoggerMiddleware.php
│   ├── Enums/
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── LoanStatus.php
│   │   ├── FineStatus.php
│   │   ├── ReservationStatus.php
│   │   └── UserStatus.php
│   └── Helpers/
│       ├── Isbn.php
│       ├── Sanitize.php
│       ├── SafeRedirect.php
│       ├── SafeHttpClient.php
│       └── AccessControl.php
├── views/
│   ├── layouts/
│   │   ├── app.php
│   │   ├── auth.php
│   │   └── print.php
│   ├── partials/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   ├── flash.php
│   │   └── pagination.php
│   ├── public/               ← home, catálogo público, nuevas adquisiciones
│   ├── auth/
│   ├── books/
│   ├── loans/
│   ├── reservations/
│   ├── fines/
│   ├── members/
│   ├── search/
│   ├── news/
│   ├── teacher/           ← panel docente, grupos, estadísticas
│   ├── assignments/       ← asignaciones de lectura
│   ├── suggestions/       ← sugerencias de libros
│   ├── reports/
│   ├── admin/
│   └── errors/
│       ├── 403.php
│       ├── 404.php
│       └── 500.php
├── public/
│   ├── index.php          ← Único punto de entrada
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   └── img/
├── storage/
│   ├── covers/            ← Portadas de libros
│   ├── barcodes/          ← Códigos de barras cacheados
│   ├── qrcodes/           ← QR cacheados
│   ├── reports/           ← PDFs temporales
│   ├── temp/              ← Archivos temporales
│   ├── cache/             ← FileCache
│   └── logs/              ← Archivos de log
├── database/
│   ├── migrations/
│   │   ├── 001_create_categories.sql
│   │   ├── 002_create_library_branches.sql
│   │   ├── 003_create_books.sql
│   │   ├── 004_create_users.sql
│   │   ├── 005_create_loans.sql
│   │   ├── 006_create_reservations.sql
│   │   ├── 007_create_fines.sql
│   │   ├── 008_create_email_queue.sql
│   │   ├── 009_create_audit_logs.sql
│   │   ├── 010_create_system_settings.sql
│   │   ├── 011_create_password_resets.sql
│   │   ├── 012_create_search_log.sql
│   │   ├── 013_create_news.sql
│   │   ├── 014_create_visits_log.sql
│   │   ├── 015_create_teacher_groups.sql
│   │   ├── 016_create_teacher_group_students.sql
│   │   ├── 017_create_reading_assignments.sql
│   │   ├── 018_create_reading_assignment_students.sql
│   │   ├── 019_create_book_suggestions.sql
│   │   └── 020_create_digital_access_log.sql
│   └── seeds/
│       ├── categories.sql
│       ├── admin_user.sql
│       └── teacher_demo.sql   ← grupo de ejemplo con asignaciones
├── bin/
│   ├── migrate.php           ← Ejecutar migraciones
│   ├── mail_worker.php       ← Procesar cola de email
│   ├── reservation_check.php ← Verificar reservas expiradas
│   ├── overdue_check.php     ← Alertas de préstamos vencidos (cada hora)
│   ├── new_acquisition_check.php ← Desmarcar nuevas adquisiciones antiguas
│   ├── assignment_check.php  ← Verificar asignaciones vencidas y recordatorios
│   └── cache_clear.php       ← Limpiar cache
├── config/
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   └── environment.php    ← Fallback si no hay .env
├── tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Feature/
│   └── Builders/
├── vendor/                ← Composer (opcional)
├── .env.example
├── .env                   ← No versionar
├── composer.json          ← Opcional
├── phpunit.xml
└── REQUIREMENTS.md        ← Este archivo
```

---

## 11. Criterios de Aceptación

### 11.1 Módulo de Autenticación
- [ ] Login con email/contraseña válido otorga acceso al dashboard.
- [ ] 5 intentos fallidos bloquean por 15 minutos.
- [ ] Recuperación de contraseña envía email y el token expira en 60 min.
- [ ] Cierre de sesión elimina cookie y sesión completamente.

### 11.2 Página Pública
- [ ] La URL raíz carga sin sesión con catálogo, noticias y nuevas adquisiciones.
- [ ] El catálogo público muestra libros físicos y digitales diferenciados.
- [ ] Los libros digitales muestran enlace de acceso sin requerir login.
- [ ] Las estadísticas públicas (totales) se muestran actualizadas.
- [ ] Las visitas anónimas se registran sin guardar datos personales.

### 11.3 Catálogo
- [ ] Se puede agregar libro físico con ISBN, el lookup trae datos de Open Library.
- [ ] Se puede agregar libro digital con URL y sin ISBN.
- [ ] El campo `replacement_cost` es obligatorio; guardado sin él falla con error de validación.
- [ ] ISBN inválido (checksum erróneo) muestra error de validación.
- [ ] Portada subida aparece en 3 tamaños; el original no es accesible directamente.
- [ ] Botón "Dar de baja" aparece en la ficha; requiere confirmación antes de ejecutarse.
- [ ] Libro dado de baja desaparece del catálogo público; reactivable desde Admin.
- [ ] Libro con préstamos activos no puede ser dado de baja.

### 11.4 Préstamos
- [ ] Crear préstamo decrementa `available_copies` en 1.
- [ ] No se puede crear préstamo si `available_copies = 0`.
- [ ] El vencimiento se registra como DATETIME (fecha + hora exacta).
- [ ] Con `loan_hours = 72`, `due_at` es exactamente 72 horas después de `loan_at`.
- [ ] Devolución tardía genera multa en horas (no en días).
- [ ] Devolución incrementa `available_copies` en 1.
- [ ] Renovación falla si hay reservas activas del libro.
- [ ] No se puede crear préstamo de un libro digital.
- [ ] El panel de gestión muestra countdown por préstamo activo.

### 11.5 Reservas
- [ ] Reserva asigna posición en cola correctamente.
- [ ] Al devolver libro, el sistema notifica al primero en cola por email.
- [ ] Reserva expira automáticamente tras 48h sin retiro.
- [ ] Conversión de reserva a préstamo es atómica.

### 11.6 Multas
- [ ] Multa calculada correctamente en horas: `CEIL(horas_atraso) × fine_per_hour`.
- [ ] La multa no supera `replacement_cost × max_fine_multiplier`.
- [ ] Pago parcial actualiza `amount_paid` sin cerrar la multa.
- [ ] Condonación sin justificación falla con error de validación.
- [ ] Usuario con multas no puede prestar (si `block_loans_with_fines=true`).

### 11.7 Búsqueda
- [ ] Búsqueda FULLTEXT en título, autor y descripción retorna resultados relevantes.
- [ ] Autocompletado responde en < 200ms.
- [ ] Filtro por categoría reduce resultados correctamente.
- [ ] Filtro por tipo (físico/digital) funciona correctamente.
- [ ] Búsqueda con barcode reader redirige a ficha del libro.

### 11.8 Noticias
- [ ] Noticia con visibilidad `pública` es visible sin sesión.
- [ ] Noticia con visibilidad `privada` redirige al login si no hay sesión.
- [ ] Las 3 noticias más recientes aparecen en la portada.
- [ ] El slug se genera automáticamente y es único.
- [ ] Noticia en estado `borrador` no aparece en el sitio público.

### 11.9 Reportes
- [ ] Dashboard carga en < 1 segundo con cache activo.
- [ ] Reporte de préstamos con rango personalizado de fechas exporta correctamente.
- [ ] Reporte de inventario incluye total de libros, copias y valor total de reposición.
- [ ] Reporte de visitas muestra visitantes únicos por día.
- [ ] Exportar CSV de préstamos descarga archivo válido con BOM UTF-8.
- [ ] Gráficos SVG renderizan sin JavaScript; JS del cliente puede añadir interactividad.
- [ ] Impresión de reporte oculta menú y muestra solo contenido.

### 11.10 Seguridad
- [ ] Intentar acceder a `/admin` sin sesión redirige a login.
- [ ] Formulario sin token CSRF retorna 419.
- [ ] Subir un PHP disfrazado de JPG es rechazado.
- [ ] Intentar path traversal en servicio de archivos retorna 403.
- [ ] Cabecera CSP presente en todas las respuestas HTML.
- [ ] Los accesos a libros digitales (PDF propios) pasan por controlador PHP.

### 11.11 Despliegue
- [ ] El sistema funciona en Nginx + PHP-FPM con la configuración incluida.
- [ ] El sistema funciona en Apache con el `.htaccess` incluido.
- [ ] Las migraciones se aplican correctamente con `php bin/migrate.php`.
- [ ] Sin APCu instalado, el sistema usa FileCache sin errores.

### 11.12 Módulo Docente
- [ ] Docente puede crear grupo con nombre, materia y ciclo escolar.
- [ ] Docente puede agregar y remover alumnos de un grupo por nombre, carnet o documento.
- [ ] Un alumno puede pertenecer a múltiples grupos simultáneos.
- [ ] Docente puede archivar un grupo; el historial se conserva.
- [ ] Docente puede crear asignación de lectura vinculada a un libro y un grupo.
- [ ] Asignación se marca como `fulfilled` automáticamente al detectar préstamo registrado.
- [ ] Asignación se marca como `fulfilled` automáticamente al detectar acceso a libro digital.
- [ ] Con `fulfillment_type = both`, la asignación requiere ambas acciones para cumplirse.
- [ ] Al crear asignación, se envía correo a cada alumno asignado.
- [ ] 48h antes de vencer la asignación, se envía recordatorio al alumno.
- [ ] Al vencer sin cumplimiento, el Docente recibe resumen de incumplimientos por correo.
- [ ] El alumno ve "Lecturas asignadas" en su panel con estado de cada asignación.
- [ ] Docente puede ver dashboard del grupo con métricas de actividad.
- [ ] Docente puede ver perfil de actividad individual del alumno (préstamos, accesos, asignaciones).
- [ ] Docente NO puede ver montos exactos de multas de sus alumnos, solo indicador.
- [ ] Docente NO puede modificar datos personales, préstamos ni multas de sus alumnos.
- [ ] Docente puede verificar cumplimiento de asignación por alumno (tabla exportable).
- [ ] Docente puede sugerir libro para adquisición con título, autor, ISBN, justificación.
- [ ] Sugerencia con ISBN existente en catálogo muestra advertencia antes de enviar.
- [ ] Bibliotecario/Admin puede aprobar o rechazar sugerencias con motivo.
- [ ] Al aprobar/rechazar sugerencia, el Docente recibe notificación con el resultado.
- [ ] Docente puede exportar reporte de grupo a PDF y CSV.
- [ ] El reporte del grupo solo contiene datos del grupo del Docente solicitante.
- [ ] Admin puede ver y gestionar todos los grupos de todos los docentes.

---

---

### Historial de Versiones

| Versión | Fecha       | Cambios |
|---------|-------------|---------|
| 1.0     | 2026-04-11  | Versión inicial |
| 1.1     | 2026-04-11  | Préstamos en horas (72h), costo de reposición obligatorio, libros digitales, página pública, noticias/blog, nuevas adquisiciones (pública/privada), reportes de visitas y de inventario extendido, baja individual de libro, panel de gestión de préstamos |
| 1.2     | 2026-04-11  | Correcciones módulo docente: rol `teacher` en ENUM de `users.role`, migraciones 014-019, criterios de aceptación 11.12, plantillas email docente, vistas y cron de asignaciones, reordenamiento de secciones 4.13/4.14 y 7.4/7.5, seed data docente |
| 1.3     | 2026-04-11  | Personalización de biblioteca: 10 nuevos parámetros en `system_settings` (`library_phone`, `library_email`, `library_website`, `library_schedule`, `library_slogan`, `library_favicon`, `timezone`, `locale`, `date_format`, `carnet_prefix`), referencias cruzadas en RF-PUB-01, RF-BAR-04, RF-REP-02, RF-MEM-02, plantilla base de emails RF-EMAIL-05 |
| 1.4     | 2026-04-11  | Soporte multi-sede: entidad `library_branches`, RF-ADMIN-06, `branch_id` en `books` y `loans`, filtros por sede en búsqueda/reportes/préstamos, migración `002_create_library_branches`, controlador y repositorio de sedes, renumeración de migraciones |

*Documento de requerimientos — Sistema de Gestión de Biblioteca — PHP puro, MariaDB, sin frameworks.*
