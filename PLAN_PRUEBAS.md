# Plan de pruebas y mejoras de SisColog

Este documento permite registrar el avance de la preparación, pruebas y mejoras del sistema.

> [!CAUTION]
> Las credenciales indicadas son exclusivamente para el ambiente local de desarrollo. No deben utilizarse con información clínica real ni mantenerse en producción.

## Cuentas de prueba

| Perfil | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin@siscolog.local` | `admin123` |
| Profesional | `profesional@siscolog.local` | `demo123` |
| Supervisor | `supervisor@siscolog.local` | `demo123` |

---

## 1. Preparación del entorno

- [x] Verificar que PHP 8.1 o superior esté instalado.
- [x] Verificar que MySQL 8 o superior esté activo.
- [x] Revisar la configuración local del archivo `.env`.
- [x] Confirmar la conexión con la base de datos.
- [x] Crear la base de datos si todavía no existe.
- [x] Ejecutar todas las migraciones.
- [x] Ejecutar los datos iniciales o *seeders*.
- [x] Definir `http://localhost:8787` como dirección independiente de pruebas.
- [x] Confirmar que la página de acceso cargue sin errores.
- [ ] Revisar que no existan errores en la consola del navegador.
- [x] Validar el acceso con la cuenta de administrador.
- [x] Validar el acceso con la cuenta de profesional.
- [x] Validar el acceso con la cuenta de supervisor.

### Resultado

- [ ] Entorno local preparado y estable.

Notas:

```text
27-07-2026:
- PHP 8.3.30 verificado.
- MySQL 8.4.3 activo.
- Base de datos `siscolog` creada.
- 35 migraciones aplicadas; quedaron 36 tablas incluyendo `migrations`.
- Datos iniciales y demo cargados.
- Tres usuarios demo activos y sus contraseñas verificadas.
- El acceso independiente se ejecutará en `http://localhost:8787`.
- Se creó `INICIAR_SISCOLOG.bat` para iniciar el servidor y abrir el navegador.
- La aplicación no depende de Apache de Laragon para este modo de ejecución.
- Pendiente: revisión visual de la consola del navegador.
```

---

## 2. Autenticación y sesiones

- [x] Comprobar el acceso con credenciales válidas.
- [x] Comprobar el rechazo de credenciales inválidas.
- [ ] Verificar que la contraseña no quede visible después de un error.
- [ ] Comprobar el cierre de sesión.
- [ ] Verificar que no sea posible volver a una pantalla protegida después de cerrar sesión.
- [ ] Comprobar la expiración de sesión por inactividad.
- [ ] Verificar la regeneración de la sesión después de iniciar sesión.
- [ ] Probar el registro de un usuario nuevo.
- [ ] Probar el ingreso mediante Google cuando esté configurado.
- [ ] Confirmar que Google solo permita usuarios autorizados y activos.
- [x] Comprobar la protección CSRF en el formulario de acceso.
- [ ] Verificar el comportamiento al enviar un formulario con una sesión expirada.
- [ ] Comprobar que las cookies tengan `HttpOnly`, `Secure` y `SameSite` según el ambiente.
- [ ] Comprobar que exista un límite de intentos de acceso.

### Resultado

- [ ] Autenticación y manejo de sesiones aprobados.

Notas:

```text
27-07-2026:
- Acceso HTTP aprobado para administrador, profesional y supervisor.
- Una contraseña incorrecta fue rechazada y permaneció en `/login`.
- Una solicitud sin sesión a `/usuarios` fue redirigida a `/login`.
- El formulario de acceso contiene y valida un token CSRF.
- La comprobación CSRF del resto de los formularios sigue pendiente.
```

---

## 3. Permisos por perfil

### Administrador

- [ ] Puede acceder al panel principal.
- [x] Puede administrar usuarios.
- [ ] Puede administrar mantenedores.
- [ ] Puede administrar instrumentos psicométricos.
- [ ] Puede consultar la auditoría.
- [ ] Puede acceder a informes permitidos.
- [ ] No puede acceder a acciones fuera de su autorización.

### Profesional

- [ ] Puede acceder al panel principal.
- [ ] Debe completar su perfil profesional cuando corresponda.
- [ ] Puede consultar los pacientes autorizados.
- [ ] Puede registrar y editar pacientes autorizados.
- [ ] Puede registrar sesiones clínicas.
- [ ] Puede registrar consentimientos.
- [ ] Puede aplicar instrumentos psicométricos.
- [ ] Puede solicitar análisis asistidos por IA.
- [x] No puede administrar usuarios.
- [ ] No puede acceder a mantenedores restringidos.
- [ ] No puede consultar información de pacientes ajenos.

### Supervisor

- [ ] Puede acceder al panel principal.
- [ ] Puede consultar los módulos autorizados.
- [ ] Puede revisar información clínica permitida.
- [ ] Puede revisar resultados y análisis cuando corresponda.
- [ ] Puede consultar auditoría si su rol lo permite.
- [x] No puede realizar acciones administrativas no autorizadas.
- [ ] No puede modificar información fuera de su alcance.

### Verificación directa

- [ ] Probar el acceso desde la navegación visible.
- [x] Probar el acceso escribiendo directamente una URL restringida.
- [ ] Probar solicitudes de modificación con un perfil sin permiso.
- [ ] Confirmar que el servidor rechace la acción y no solo oculte el enlace.

### Resultado

- [ ] Matriz de permisos aprobada para los tres perfiles.

Notas:

```text
27-07-2026:
- `/usuarios` fue accesible para administrador.
- El acceso directo a `/usuarios` fue rechazado para profesional y supervisor.
- Todavía falta recorrer todos los módulos y operaciones de cada perfil.
```

---

## 4. Flujo clínico completo

Usar exclusivamente un paciente ficticio.

- [ ] Crear un paciente ficticio.
- [ ] Editar los antecedentes del paciente.
- [ ] Registrar un contacto de emergencia.
- [ ] Registrar una persona asociada.
- [ ] Crear relaciones familiares.
- [ ] Mover y guardar los nodos del mapa familiar.
- [ ] Registrar un consentimiento.
- [ ] Desactivar y volver a activar el consentimiento.
- [ ] Crear una cita.
- [ ] Agregar participantes a la cita.
- [ ] Agregar temas a la cita.
- [ ] Editar la cita.
- [ ] Registrar una sesión clínica.
- [ ] Verificar los participantes de la sesión.
- [ ] Verificar los temas clínicos de la sesión.
- [ ] Aplicar un instrumento psicométrico.
- [ ] Comprobar el cálculo del resultado.
- [ ] Editar el resultado cuando esté permitido.
- [ ] Solicitar un análisis asistido por IA.
- [ ] Revisar y aprobar o rechazar el análisis.
- [ ] Consultar el historial de análisis.
- [ ] Generar el informe del paciente.
- [ ] Exportar el informe.
- [ ] Comprobar que todas las acciones relevantes aparezcan en auditoría.

### Resultado

- [ ] Flujo clínico completo aprobado.

Notas:

```text

```

---

## 5. Validaciones y manejo de errores

- [ ] Probar formularios dejando campos obligatorios vacíos.
- [ ] Probar correos con formato inválido.
- [ ] Probar fechas imposibles o incoherentes.
- [ ] Probar valores numéricos fuera de rango.
- [ ] Probar registros duplicados.
- [ ] Probar identificadores inexistentes en las URL.
- [ ] Probar identificadores pertenecientes a otro paciente.
- [ ] Probar el envío repetido de un mismo formulario.
- [ ] Verificar que una actualización fallida no guarde información parcial.
- [ ] Verificar mensajes comprensibles para el usuario.
- [ ] Confirmar que los errores técnicos no expongan consultas, rutas o credenciales.
- [ ] Comprobar la conservación segura de los datos ingresados después de un error.

### Resultado

- [ ] Validaciones y manejo de errores aprobados.

Notas:

```text

```

---

## 6. Seguridad prioritaria

- [ ] Eliminar las contraseñas precargadas del formulario de acceso.
- [ ] Ocultar las cuentas demo fuera del ambiente local.
- [ ] Cambiar el cierre de sesión de GET a POST.
- [ ] Cambiar activaciones y desactivaciones de GET a POST.
- [ ] Proteger todas las modificaciones mediante CSRF.
- [ ] Agregar límite de intentos y bloqueo temporal de acceso.
- [ ] Configurar `Content-Security-Policy`.
- [ ] Configurar `X-Content-Type-Options: nosniff`.
- [ ] Configurar protección contra inclusión en `iframe`.
- [ ] Configurar `Referrer-Policy`.
- [ ] Configurar HSTS en producción con HTTPS.
- [ ] Revisar todas las consultas SQL dinámicas.
- [ ] Comprobar el escape de todo contenido mostrado en HTML.
- [ ] Revisar el uso de `innerHTML` en el JavaScript.
- [ ] Evitar que archivos sensibles sean accesibles desde la web.
- [ ] Confirmar que `.env`, migraciones y registros no sean públicos.
- [ ] Revisar la retención y protección de los registros de auditoría.
- [ ] Verificar copias de seguridad cifradas.
- [ ] Documentar el procedimiento de restauración.

### Resultado

- [ ] Revisión de seguridad aprobada.

Notas:

```text

```

---

## 7. Privacidad y uso de IA

- [ ] Identificar exactamente qué información se envía al proveedor de IA.
- [ ] Reducir los datos enviados al mínimo necesario.
- [ ] Anonimizar o seudonimizar la información antes del envío.
- [ ] Evitar el envío de identificadores personales innecesarios.
- [ ] Registrar el consentimiento aplicable al uso de IA.
- [ ] Informar claramente que el resultado requiere revisión profesional.
- [ ] Impedir que una respuesta de IA se publique automáticamente como diagnóstico.
- [ ] Registrar quién solicitó, revisó y aprobó cada análisis.
- [ ] Definir estados de revisión y rechazo.
- [ ] Definir una política de conservación de solicitudes y respuestas.
- [ ] Documentar el comportamiento cuando el proveedor no esté disponible.
- [ ] Evitar que errores del proveedor expongan información sensible.
- [ ] Revisar las condiciones contractuales y normativas aplicables.

### Resultado

- [ ] Privacidad y uso responsable de IA aprobados.

Notas:

```text

```

---

## 8. Experiencia de uso y accesibilidad

- [ ] Revisar la interfaz en escritorio.
- [ ] Revisar la interfaz en tablet.
- [ ] Revisar la interfaz en móvil.
- [ ] Probar resoluciones estrechas y pantallas amplias.
- [ ] Comprobar el menú superior.
- [ ] Comprobar el menú lateral.
- [ ] Comprobar el modo oscuro.
- [ ] Comprobar formularios extensos.
- [ ] Comprobar tablas con muchos registros.
- [ ] Comprobar el calendario.
- [ ] Comprobar el mapa de relaciones familiares.
- [ ] Navegar todo el sistema únicamente con teclado.
- [ ] Verificar que el foco sea siempre visible.
- [ ] Verificar el orden lógico del foco.
- [ ] Revisar contraste de texto, botones y estados.
- [ ] Confirmar que todos los campos tengan etiquetas comprensibles.
- [ ] Confirmar que iconos y botones tengan nombres accesibles.
- [ ] Agregar soporte para `prefers-reduced-motion`.
- [ ] Verificar los mensajes dinámicos con lectores de pantalla.
- [ ] Confirmar que los errores indiquen claramente cómo corregirlos.

### Resultado

- [ ] Experiencia de uso y accesibilidad aprobadas.

Notas:

```text

```

---

## 9. Rendimiento y mantenibilidad

- [ ] Medir el tiempo de carga del panel.
- [ ] Medir el tiempo de carga de la ficha del paciente.
- [ ] Medir consultas con grandes volúmenes de pacientes y sesiones.
- [ ] Detectar consultas repetidas o innecesarias.
- [ ] Revisar índices de la base de datos.
- [ ] Separar el JavaScript de `app/Views/layout.php`.
- [ ] Dividir el JavaScript por funcionalidad.
- [ ] Modularizar `public/assets/css/app.css`.
- [ ] Unificar el idioma de las rutas.
- [ ] Documentar las decisiones de arquitectura.
- [ ] Incorporar registro controlado de errores.
- [ ] Definir configuración diferenciada para desarrollo y producción.
- [ ] Desactivar mensajes técnicos detallados en producción.

### Resultado

- [ ] Rendimiento y mantenibilidad aprobados.

Notas:

```text

```

---

## 10. Pruebas automatizadas

- [ ] Configurar una herramienta de pruebas para PHP.
- [ ] Crear una base de datos aislada para pruebas.
- [ ] Probar inicio y cierre de sesión.
- [ ] Probar expiración de sesión.
- [ ] Probar CSRF.
- [ ] Probar permisos de administrador.
- [ ] Probar permisos de profesional.
- [ ] Probar permisos de supervisor.
- [ ] Probar aislamiento entre pacientes y profesionales.
- [ ] Probar creación y edición de pacientes.
- [ ] Probar consentimientos.
- [ ] Probar sesiones clínicas.
- [ ] Probar instrumentos psicométricos y cálculos.
- [ ] Simular el proveedor de IA sin enviar datos reales.
- [ ] Probar revisión de análisis.
- [ ] Probar generación de informes.
- [ ] Probar registros de auditoría.
- [ ] Ejecutar automáticamente las pruebas antes de publicar cambios.

### Resultado

- [ ] Suite mínima de pruebas automatizadas aprobada.

Notas:

```text

```

---

## 11. Preparación para producción

- [ ] Eliminar o desactivar todas las cuentas demo.
- [ ] Cambiar todas las contraseñas iniciales.
- [ ] Confirmar que no existan datos ficticios innecesarios.
- [ ] Configurar HTTPS.
- [ ] Configurar cookies seguras.
- [ ] Configurar encabezados HTTP de seguridad.
- [ ] Configurar credenciales exclusivas para producción.
- [ ] Proteger las claves del proveedor de IA y de Google.
- [ ] Configurar respaldos automáticos.
- [ ] Probar una restauración completa.
- [ ] Definir monitoreo y alertas.
- [ ] Definir procedimiento de respuesta ante incidentes.
- [ ] Documentar instalación, actualización y reversión.
- [ ] Ejecutar la regresión completa con los tres perfiles.
- [ ] Obtener aprobación funcional.
- [ ] Obtener aprobación de seguridad y privacidad.

### Resultado final

- [ ] SisColog está autorizado para su publicación.

---

## Registro de incidencias

| N.º | Fecha | Módulo | Descripción | Prioridad | Estado | Responsable |
|---:|---|---|---|---|---|---|
| 1 |  |  |  |  | Pendiente |  |
| 2 |  |  |  |  | Pendiente |  |
| 3 |  |  |  |  | Pendiente |  |

## Resumen de avance

- [ ] Preparación del entorno
- [ ] Autenticación y sesiones
- [ ] Permisos por perfil
- [ ] Flujo clínico completo
- [ ] Validaciones y errores
- [ ] Seguridad
- [ ] Privacidad e IA
- [ ] Experiencia y accesibilidad
- [ ] Rendimiento y mantenibilidad
- [ ] Pruebas automatizadas
- [ ] Preparación para producción
