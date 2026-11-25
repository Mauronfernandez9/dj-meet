# TP2 - Cliente Android de DJ Meet

## Integrantes

- Fernandez Mauro Nicolas
- Franco Cobatti

## Descripción

En este trabajo práctico agregamos un cliente Android nativo en Java para utilizar la API de DJ Meet desarrollada en PHP. El código de la aplicación se encuentra en la carpeta `android` de este mismo repositorio.

La aplicación permite crear una sala, ingresar a una sala mediante un código de ocho caracteres, consultar sus propuestas y agregar o eliminar una propuesta.

## Pantallas

1. Inicio: solicita el nombre del participante y permite crear una sala o ingresar con un código.
2. Sala: muestra el nombre, el código y las propuestas de la sala.
3. Nueva propuesta: permite ingresar los datos de una canción y enviarlos a la API.

Los datos necesarios se envían entre las pantallas mediante `Intent` y `putExtra`.

## Conexión con la API

El cliente utiliza `HttpURLConnection`. Las solicitudes se ejecutan fuera del hilo principal y la respuesta se muestra nuevamente en la interfaz.

Durante las pruebas con el emulador, la dirección base es:

```text
http://10.0.2.2/dj-meet/api/
```

`10.0.2.2` permite que el emulador acceda al servidor Apache de XAMPP que está funcionando en la computadora.

Operaciones utilizadas:

- `POST api/salas.php`: crear una sala.
- `POST api/participantes.php`: ingresar a una sala.
- `GET api/propuestas.php`: consultar las propuestas.
- `POST api/propuestas.php`: crear una propuesta.
- `DELETE api/propuestas.php`: eliminar una propuesta propia.

## Cómo ejecutar el proyecto

1. Clonar este repositorio dentro de `C:\xampp\htdocs\dj-meet`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Importar `sql/dj_meet_script.sql` en una base llamada `dj_meet`.
4. Abrir la carpeta `android` con Android Studio.
5. Esperar la sincronización de Gradle.
6. Iniciar un emulador Android.
7. Ejecutar la aplicación con el botón Run.

La aplicación requiere Android 7.0 (API 24) o una versión posterior.

## Estado de la entrega

La aplicación se prueba de forma local porque finalizó el período gratuito de Railway y para volver a activar el despliegue sería necesario pagar una membresía. Por este motivo, durante el desarrollo utilizamos XAMPP para ejecutar Apache, PHP y MariaDB en la computadora.

La comunicación local entre Android y el backend está configurada. Para probar la aplicación fuera de la computadora se deberá reemplazar la dirección local por la URL HTTPS donde se despliegue nuevamente el backend.
