# Plan de pruebas y mejoras de SisColog

Este documento permite registrar el avance de la preparación, pruebas y mejoras del sistema.

> [!CAUTION]
> Las credenciales indicadas son exclusivamente para el ambiente local de desarrollo. No deben utilizarse con información clínica real ni mantenerse en producción.

## Cuentas de prueba

| Perfil | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin@siscolog.local` | `1` |
| Profesional | `profesional@siscolog.local` | `1` |
| Supervisor | `supervisor@siscolog.local` | `1` |

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
- [x] Revisar que no existan errores en la consola del navegador.
- [x] Validar el acceso con la cuenta de administrador.
- [x] Validar el acceso con la cuenta de profesional.
- [x] Validar el acceso con la cuenta de supervisor.

### Resultado

- [x] Entorno local preparado y estable.

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

12-08-2026:
- PHP 8.3.30 verificado usando `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`.
- Base de datos `siscolog` conectada; 36 tablas y 35 migraciones registradas.
- Servidor local levantado en `http://localhost:8787`.
- `/login` y `/panel` cargaron en navegador sin errores de consola, `pageerror`, solicitudes fallidas ni respuestas HTTP 4xx/5xx relevantes.
- Las credenciales reales del ambiente local son `1` para administrador, profesional y supervisor; se actualizó la tabla de cuentas de prueba.
```

---

## 2. Autenticación y sesiones

- [x] Comprobar el acceso con credenciales válidas.
- [x] Comprobar el rechazo de credenciales inválidas.
- [ ] Verificar que la contraseña no quede visible después de un error.
- [x] Comprobar el cierre de sesión.
- [x] Verificar que no sea posible volver a una pantalla protegida después de cerrar sesión.
- [ ] Comprobar la expiración de sesión por inactividad.
- [x] Verificar la regeneración de la sesión después de iniciar sesión.
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

12-08-2026:
- Las contraseñas documentadas anteriormente (`admin123` y `demo123`) fueron rechazadas; las tres cuentas demo autentican con `1`.
- Credenciales inválidas redirigen a `/login`.
- Después de un error de login, el campo de contraseña vuelve a mostrarse con valor precargado `1`; queda pendiente corregirlo.
- El inicio de sesión regenera `PHPSESSID`.
- `/logout` redirige a `/login` y una visita posterior a `/usuarios` también queda en `/login`.
- Un POST a `/login` sin `_csrf` fue rechazado y no inició sesión.
- La cookie local `PHPSESSID` se observó como `path=/`, sin flags `HttpOnly`, `Secure` ni `SameSite` en la cabecera.
```

---

## 3. Permisos por perfil

### Administrador

- [x] Puede acceder al panel principal.
- [x] Puede administrar usuarios.
- [x] Puede administrar mantenedores.
- [x] Puede administrar instrumentos psicométricos.
- [x] Puede consultar la auditoría.
- [x] Puede acceder a informes permitidos.
- [ ] No puede acceder a acciones fuera de su autorización.

### Profesional

- [x] Puede acceder al panel principal.
- [ ] Debe completar su perfil profesional cuando corresponda.
- [ ] Puede consultar los pacientes autorizados.
- [ ] Puede registrar y editar pacientes autorizados.
- [ ] Puede registrar sesiones clínicas.
- [ ] Puede registrar consentimientos.
- [ ] Puede aplicar instrumentos psicométricos.
- [ ] Puede solicitar análisis asistidos por IA.
- [x] No puede administrar usuarios.
- [x] No puede acceder a mantenedores restringidos.
- [ ] No puede consultar información de pacientes ajenos.

### Supervisor

- [x] Puede acceder al panel principal.
- [x] Puede consultar los módulos autorizados.
- [ ] Puede revisar información clínica permitida.
- [ ] Puede revisar resultados y análisis cuando corresponda.
- [ ] Puede consultar auditoría si su rol lo permite.
- [x] No puede realizar acciones administrativas no autorizadas.
- [ ] No puede modificar información fuera de su alcance.

### Verificación directa

- [ ] Probar el acceso desde la navegación visible.
- [x] Probar el acceso escribiendo directamente una URL restringida.
- [x] Probar solicitudes de modificación con un perfil sin permiso.
- [x] Confirmar que el servidor rechace la acción y no solo oculte el enlace.

### Resultado

- [ ] Matriz de permisos aprobada para los tres perfiles.

Notas:

```text
27-07-2026:
- `/usuarios` fue accesible para administrador.
- El acceso directo a `/usuarios` fue rechazado para profesional y supervisor.
- Todavía falta recorrer todos los módulos y operaciones de cada perfil.

12-08-2026:
- Administrador accedió por URL directa a `/`, `/panel`, `/usuarios`, `/maintainers`, `/instrumentos`, `/auditoria`, `/informes`, `/patients` y `/citas`.
- Profesional accedió a `/`, `/panel`, `/informes`, `/patients` y `/citas`; `/usuarios`, `/maintainers`, `/instrumentos` y `/auditoria` redirigieron a `/`.
- Supervisor accedió a `/`, `/panel`, `/informes`, `/patients` y `/citas`; `/usuarios`, `/maintainers`, `/instrumentos` y `/auditoria` redirigieron a `/`.
- Un POST a `/usuarios` como profesional, con CSRF válido, fue rechazado por permisos; el conteo de usuarios permaneció en 4.
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
12-08-2026:
- El formulario de login sigue mostrando cuentas demo y precarga la contraseña `1`.
- `/logout` está implementado como GET.
- Se observaron rutas GET para activar/desactivar usuarios, pacientes, consentimientos, sesiones, resultados psicométricos, instrumentos, análisis IA y mantenedores.
- La respuesta local de `/login` no incluyó `Content-Security-Policy`, `X-Content-Type-Options`, protección contra `iframe`, `Referrer-Policy` ni `Strict-Transport-Security`.
- La cookie `PHPSESSID` se observó sin `HttpOnly`, `Secure` ni `SameSite`.
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
12-08-2026:
- La pantalla de login carga correctamente en escritorio y queda disponible en `http://localhost:8787/login`.
- Se confirmó visualmente que el formulario de login muestra credenciales demo y precarga la contraseña `1`.
- El panel carga tras autenticar con administrador.
- Queda pendiente recorrer visualmente módulos internos, tablet, móvil, modo oscuro, formularios extensos, tablas, calendario, mapa familiar y navegación por teclado.
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
| 1 | 12-08-2026 | Autenticación | El login precarga la contraseña `1` y vuelve a mostrarla después de un error de credenciales. | Alta | Pendiente |  |
| 2 | 12-08-2026 | Seguridad | `/logout` y varias activaciones/desactivaciones siguen usando GET para cambios de estado. | Alta | Pendiente |  |
| 3 | 12-08-2026 | Seguridad | La cookie `PHPSESSID` local se entrega sin flags `HttpOnly`, `Secure` ni `SameSite`. | Alta | Pendiente |  |
| 4 | 12-08-2026 | Seguridad | No se observaron cabeceras `Content-Security-Policy`, `X-Content-Type-Options`, protección contra `iframe`, `Referrer-Policy` ni HSTS. | Media | Pendiente |  |

## Resumen de avance

- [x] Preparación del entorno
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
