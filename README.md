# DJ Meet

Aplicación para elegir entre amigos qué música escuchar. El anfitrión crea una sala y comparte su código. Los invitados proponen canciones de YouTube y votan. La cola se ordena por votos y el anfitrión selecciona qué reproducir en su equipo.

Trabajo práctico 1 de Aplicaciones Móviles y Cloud Computing.

- Integrantes: **Fernandez Mauro Nicolas** y **Franco**.
- Frontend público: **PENDIENTE de despliegue**.
- API pública: **PENDIENTE de despliegue**, ruta `/api/propuestas.php?salaId=ID`.
- Repositorio: https://github.com/Mauronfernandez9/dj-meet
- Estado: preparado para probar y desplegar; completar las URLs antes de entregar.

## Cómo correrla en local

Requiere PHP 8.1 o superior (se usa `execute([...])`), extensiones mysqli con mysqlnd, mbstring y cURL, y MySQL/MariaDB. El desarrollo local usa XAMPP con PHP 8.2 y MariaDB 10.4.

1. Ubicar el proyecto en `C:\xampp\htdocs\dj-meet`.
2. Iniciar Apache y MySQL en XAMPP.
3. En phpMyAdmin crear una base vacía llamada `dj_meet`, con utf8mb4. Seleccionarla e importar `sql/dj_meet_script.sql`.
4. Configurar `DB_HOST` y `DB_USER` en el entorno que recibe Apache. Configurar también contraseña, base y puerto si difieren de los valores locales predeterminados indicados abajo. En Windows, al cambiar variables, cerrar y volver a abrir el panel de XAMPP y reiniciar Apache. Si Apache corre como servicio, debe recibir las variables de ese servicio.
5. Abrir http://localhost/dj-meet/. Crear una sala. Entrar desde otro navegador o ventana privada con el código para simular un invitado.

El SQL crea cinco tablas, índices y claves foráneas; no contiene datos ni contraseñas. No se vuelve a importar encima de tablas existentes. No incluye `CREATE DATABASE` ni `USE` para poder usar el nombre de base asignado por el proveedor cloud.

No se carga automáticamente un archivo `.env`: PHP usa las variables del proceso mediante `getenv()`. No pegar contraseñas en archivos PHP.

## API

Todas las respuestas son JSON. Los POST reciben campos de formulario (FormData), no un cuerpo JSON. La sesión se conserva mediante la cookie PHPSESSID que el navegador envía en las peticiones al mismo origen.

| Método | Ruta | Recibe | Devuelve / estados principales |
| --- | --- | --- | --- |
| POST | api/salas.php | nombre_sala, nombre_anfitrion | 201: id, nombreSala, estado, codigo; 422 inválido |
| GET | api/salas.php | codigo en URL | 200: id, nombre, estado, creada_en, codigo, rol; 401 sin sesión, 403 sin participación, 404 inexistente, 422 código inválido |
| POST | api/participantes.php | nombre, codigo | 201 nueva participación o 200 ya existente; id de sala, nombre, codigo, estado, rol; 422 inválido, 404 inexistente, 409 cerrada |
| GET | api/propuestas.php | salaId en URL | 200: array de propuestas pendientes y seleccionada, con votos, autor y permisos; 401, 404, 422 |
| POST | api/propuestas.php | salaId, youtube_url, titulo, artista | 201: id, titulo, url, artista; 401, 404, 409 sala cerrada, 422 inválido |
| PUT | api/propuestas.php | salaId e id en URL; sin cuerpo | 200: selecciona una propuesta pendiente; 401, 403 no anfitrión, 404, 409 estado incompatible, 422 |
| DELETE | api/propuestas.php | id en URL; sin cuerpo | 200: borrado lógico de una propuesta propia; 401, 404 inexistente o ajena, 422 |
| POST | api/votos.php | propuesta_id | 201: voto creado; 401, 404, 409 voto repetido o propuesta no votable, 422 |
| GET | api/servicios.php | salaId, busqueda en URL | 200: resultados con titulo, artista y enlace; 401, 404, 422, 503 catálogo no disponible |

Errores: `{"error":"Mensaje entendible"}`. Fallas de base/servidor devuelven 500 sin mostrar credenciales ni trazas. Los métodos no admitidos devuelven 405 y la cabecera Allow.

El recurso con CRUD completo es **propuestas**. El PUT modifica su estado: la canción seleccionada pasa a `reproduciendo` y la anterior a `reproducida`. Repetir el PUT sobre la misma canción seleccionada devuelve 200. DELETE completa `borrada_en`, sin borrar físicamente el registro. Un participante puede votar una sola vez cada propuesta; la base refuerza esta regla con UNIQUE.

Ejemplo para el anfitrión, desde la consola del navegador dentro de su sala:

```js
fetch('api/propuestas.php?salaId=ID_SALA&id=ID_PROPUESTA', {method: 'PUT'})
    .then(respuesta => respuesta.json())
    .then(datos => console.log(datos));
```

Reemplazar los identificadores por los de esa sala. Abrir la API desde otro navegador sin haber entrado devuelve 401: es el comportamiento esperado.

## Variables de entorno

| Variable | Uso |
| --- | --- |
| DB_HOST | Servidor de la base; obligatoria |
| DB_USER | Usuario de la base; obligatoria |
| DB_PASS | Contraseña; localmente, si no está definida, se usa una cadena vacía. Obligatoria en producción |
| DB_NAME | Nombre de la base; localmente se usa dj_meet si no está definida. Obligatoria en producción |
| DB_PORT | Puerto; por defecto 3306 |
| APP_ENV | Usar production en cloud: exige configurar las credenciales |
| CATALOGO_ACTIVO | Opcional. 0 desactiva la búsqueda para probar su caída; habilitada por defecto |
| PORT | En Railway, usar 80 para este Dockerfile de Apache |

Los valores reales se configuran en XAMPP/Windows o en el panel del proveedor. No van en Git. El archivo `deploy/php.ini` apaga errores visibles en producción y configura cookies de sesión para HTTPS.

## Servicios externos

### YouTube: navegador del anfitrión

El iframe carga `youtube.com/embed/ID` para reproducir la propuesta seleccionada. No usa YouTube Data API ni necesita una clave. Se elige porque permite reproducir el video oficial sin descargar ni extraer audio.

YouTube recibe el identificador del video y los datos habituales de una conexión del navegador, como IP, agente de usuario y posiblemente cookies según su configuración. No se le envían los nombres de participantes ni los votos. Los invitados ven los datos de la canción; no cargan ese reproductor.

Si el video no permite reproducción integrada, el anfitrión puede abrir su enlace o elegir otra canción. Si YouTube está caído, crear salas, proponer, votar y seleccionar sigue funcionando; la reproducción depende del proveedor.

Documentación: https://developers.google.com/youtube/player_parameters

### iTunes Search API: backend PHP

`js/servicios.js` consulta nuestra `api/servicios.php`. PHP consulta `https://itunes.apple.com/search` con cURL y devuelve hasta cinco coincidencias. Al elegir una se completan título y artista; el usuario revisa los datos y pega el enlace de YouTube.

Apple recibe el texto de búsqueda, el país del catálogo y la IP del servidor. No enviamos cookies de DJ Meet, códigos de sala ni nombres de participantes. Al abrir voluntariamente el enlace a Apple Music, el navegador establece una conexión con Apple. Sólo usamos metadatos y enlaces, no audio de muestra ni portadas.

La consulta espera como máximo seis segundos y verifica HTTPS. Si falla, devuelve 503 y muestra la opción de completar manualmente. No forma parte del INSERT ni bloquea el CRUD. La búsqueda se hace sólo al enviar el formulario. Apple documenta aproximadamente 20 llamadas por minuto, sujeto a cambios; para más usuarios sería necesario agregar un límite y caché compartidos.

Documentación: https://performance-partners.apple.com/search-api

## Despliegue

Proveedor propuesto: **Railway**, modelo PaaS. Permite ejecutar PHP/Apache y MySQL dentro del mismo proyecto y configurar secretos por entorno. Su elección debe confirmarse revisando el plan disponible y su costo; no se presupone alojamiento gratuito permanente.

La guía está en [docs/DESPLIEGUE.md](docs/DESPLIEGUE.md). El Dockerfile prepara PHP 8.2, Apache y mysqli; cURL y mbstring vienen en la imagen oficial. Copia sólo los archivos que usa la web, sin Git, documentación ni SQL. Apache escucha en 80. El dominio público termina HTTPS en Railway y conecta con ese puerto.

Se usa un único servicio web y una base con almacenamiento persistente. Las sesiones PHP viven en el servicio web: no se debe escalar a varias réplicas con esta implementación. Un nuevo despliegue puede cerrar las sesiones, aunque las salas y canciones permanecen en MySQL. Volver a desplegar no requiere volver a importar el SQL.

**Pendiente:** construir la imagen en Railway, cargar variables, importar la base vacía, generar dominio y ejecutar las pruebas públicas. No se declara el despliegue como realizado.

## Pruebas antes de entregar

1. Crear sala y entrar desde otro navegador. El anfitrión puede seleccionar; el invitado no.
2. Buscar una canción, usar sus datos, pegar una URL válida y agregarla. Probar también la carga manual.
3. Votar desde ambos navegadores; verificar el orden y que un voto repetido no se duplique.
4. Seleccionar una propuesta, repetir la selección por API y avanzar con Siguiente. El cambio persiste al recargar.
5. Borrar una propuesta propia; intentar borrar una ajena debe fallar.
6. Enviar título vacío, sala inexistente y URL ajena a YouTube; revisar los códigos HTTP y mensajes JSON.
7. Poner temporalmente CATALOGO_ACTIVO=0, reiniciar el servicio y buscar: debe avisar el problema y permitir proponer manualmente. Volver a habilitarlo.
8. Bloquear las peticiones a YouTube con DevTools: la cola y las operaciones deben seguir funcionando. Desbloquear después.
9. Repetir el recorrido en la URL HTTPS desde un celular con datos móviles.
10. Revisar código e historial de Git por secretos, completar URLs y verificar el acceso al repositorio de ambos docentes si es privado.

## Limitaciones conocidas

- Participantes temporales: el nombre no identifica una cuenta. La sesión del navegador es la identidad. Sin esa sesión se crea otro participante; no hay recuperación del rol de anfitrión.
- Una misma sesión reutiliza su participante; escribir otro nombre al entrar no cambia el ya guardado.
- La selección no confirma que YouTube esté sonando. El navegador puede pedir un clic para reproducir.
- Siguiente es manual: no hay avance automático cuando termina el video.
- Cada cinco segundos se actualiza la cola; no se usan WebSockets.
- No hay pantalla para editar título/artista después de guardar. El PUT del CRUD modifica el estado.
- El catálogo puede no encontrar una canción y no garantiza que coincida con el video elegido.
- No hay interfaz para cerrar salas ni administrar usuarios.
- No se guardan portadas; imagen_url queda reservado para una posible ampliación.
- El script SQL requiere una base vacía previamente creada o provista por el hosting.

## Participación y entrega

Con GitHub la consigna exige commits de ambos integrantes que reflejen trabajo real. Revisar los aportes antes de entregar; no se atribuye trabajo a otra persona automáticamente.

`ENTREGA.txt` contiene el texto para el campus. Completar las URLs públicas y el proveedor confirmado. Fecha indicada por la consigna: 20/10/2026 a las 23:59.
