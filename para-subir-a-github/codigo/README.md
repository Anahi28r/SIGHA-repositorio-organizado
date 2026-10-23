# Código de la demostración SIGHA

Esta carpeta contiene el código original revisado de la aplicación web, escrito en JavaScript y PHP. Sirve para mostrar el funcionamiento y distinguirlo de las propuestas académicas de POO, todavía pendientes de integrar.

## Archivos

- [app.js](htdocs/app.js): pantallas, objetos, formularios y horarios.
- [api.php](htdocs/api.php): sesiones, permisos, validaciones y almacenamiento.
- [notifications.js](htdocs/notifications.js): recordatorios de clases.
- [index.html](htdocs/index.html): estructura inicial y carga de archivos.
- [style.css](htdocs/style.css): diseño visual.

## Ubicación de los seis temas

| Tema | Código para mostrar | Situación real |
| --- | --- | --- |
| Fundamentos | [Lista de días](htdocs/app.js#L8), [función de cruces](htdocs/app.js#L19), [bucles](htdocs/api.php#L218-L221) | Hay arreglos, funciones, condiciones y bucles en JavaScript y PHP; no son código Python. |
| Clases, objetos y métodos | [Objetos iniciales](htdocs/app.js#L5), [métodos de arreglos](htdocs/app.js#L18) | Hay objetos literales y uso de métodos del lenguaje. No hay clases propias Aula, Docente o Horario. |
| Encapsulamiento | [Validación del formulario](htdocs/app.js#L119), [validación del servidor](htdocs/api.php#L209-L221) | Hay control de cambios; no encapsulamiento del dominio mediante clases con estado privado. |
| Herencia | No hay una jerarquía propia Persona, Docente y Estudiante. | Pendiente. Los roles no equivalen a herencia. |
| Abstracción | [Función api](htdocs/app.js#L42), [refresh y save](htdocs/app.js#L43-L44) | Hay abstracción funcional; no clases abstractas propias como ConsultaHorario. |
| Polimorfismo | [Consulta por rol](htdocs/api.php#L198-L204) | Se utilizan condiciones. El polimorfismo entre clases propuesto no está implementado. |

## Guía de explicación

### 1. Datos y objetos: app.js, línea 5

initial() construye datos iniciales mediante arreglos y objetos. Un aula tiene id y name. Una asignación reúne subject, teacher, room, course, day, start y end. Esto permite relacionar materia, docente, aula, curso, día y horas. Son objetos literales, no instancias de clases propias.

En la aplicación: registros de gestión y horario semanal.

### 2. Cruces: app.js, línea 19

conflicts(candidate, skip) compara una propuesta con las asignaciones existentes. Busca coincidencia de día, superposición de horas y coincidencia de docente, aula o curso. skip excluye la propia asignación al editarla.

La condición de superposición utiliza c.start < candidate.end y candidate.start < c.end. Por ejemplo, dos clases del mismo docente de 08:00 a 10:00 y de 09:00 a 11:00 se cruzan. Dos bloques consecutivos de 08:00 a 09:00 y de 09:00 a 10:00 no se superponen.

En la aplicación: creación y edición de clases, y revisión de cruces.

### 3. Validación: app.js, línea 119; api.php, líneas 209 a 221

El formulario convierte día y horas a números, verifica que la hora final sea posterior a la inicial y busca cruces antes de actualizar la asignación. El servidor comprueba permisos de administrador, recursos existentes, días, intervalos y conflictos antes de guardar.

Estas validaciones controlan cambios, pero no son por sí solas encapsulamiento de POO.

### 4. Abstracción funcional: app.js, líneas 42 a 44

api(action, payload) reúne los detalles de comunicación y procesamiento de respuestas del servidor. refresh() consulta el estado y save() intenta guardarlo. El resto del código puede utilizar estas operaciones sin repetir sus detalles. Esto no es una clase abstracta.

### 5. Consultas por rol: api.php, líneas 198 a 204

scoped() devuelve el estado general al administrador. Para un docente filtra por teacher_id y para un estudiante por course_id. Limita los temas a las clases resultantes y elimina la lista de estudiantes de esas respuestas. La selección de comportamiento utiliza condiciones, no métodos sobrescritos de clases Docente y Estudiante.

En la aplicación: cada perfil consulta sus clases.

### 6. Avisos: notifications.js, líneas 3 a 10 y 16 a 28

dueReminders() filtra clases por usuario y día y calcula cuáles empiezan en los próximos cinco minutos. checkClassReminders() recorre esos resultados y genera avisos evitando duplicados. Los avisos dependen de que SIGHA esté abierto; las notificaciones del navegador requieren permiso.

En la aplicación: campana y avisos de clases próximas.

## Enlaces para la presentación

Después de subir esta carpeta, abre este README en GitHub y pulsa sus enlaces al código. Copia la dirección del navegador y úsala como enlace en tu diapositiva. Los fragmentos #L19 o #L198-L204 señalan líneas en GitHub.

Los números corresponden al contenido original. Si modificas o reformateas los archivos, revisa los enlaces. El sitio público api.php ejecuta PHP; para enseñar su código utiliza GitHub.

## Alcance

Se excluyen las cuentas, la base de datos y las sesiones. Esta entrega permite revisar código, pero no es una instalación autónoma: api.php necesita una base SQLite compatible que no se incluye. GitHub Pages no ejecuta este servidor PHP.

No se añadieron implementaciones nuevas de herencia, clases abstractas ni polimorfismo. Esos temas siguen siendo propuestas que el equipo debe acordar y desarrollar.
