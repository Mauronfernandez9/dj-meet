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

La dirección pública utilizada por la aplicación es:

```text
https://dj-meet-production-efe3c1e7.up.railway.app/api/
```

La dirección HTTPS permite utilizar la aplicación sin depender de XAMPP ni de la red local de la computadora.

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

El backend PHP, el frontend web y la base MySQL están publicados en Railway. El cliente Android utiliza la URL pública HTTPS de la API y puede ejecutarse sin XAMPP.
