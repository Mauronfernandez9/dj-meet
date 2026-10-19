# Despliegue guiado de DJ Meet

Esta guía prepara Railway como opción PaaS. El despliegue aún no está realizado. Primero revisá el plan disponible en tu cuenta: ejecutar la web y MySQL consume recursos y la prueba gratuita no garantiza que alcance para todo el período de corrección. No es necesario contratar nada antes de revisar esto.

## 1. Entender qué vamos a subir

Tu PC hoy ejecuta Apache, PHP y MySQL mediante XAMPP. En Railway vamos a tener dos servicios dentro de un proyecto:

- **dj-meet:** Apache sirve HTML/CSS/JS y ejecuta los PHP de api/.
- **MySQL:** conserva las cinco tablas y sus datos en un volumen.

El navegador llama a la API del mismo dominio. Por eso no necesitamos CORS ni cambiar las rutas fetch. La web habla con MySQL mediante variables de entorno; el navegador nunca recibe la contraseña.

El Dockerfile es la receta de instalación del servidor web. No requiere que instales Docker en tu PC para desplegar desde GitHub.

## 2. Preparar el repositorio

Desde C:\xampp\htdocs\dj-meet:

```powershell
git status
git diff
```

Revisá los cambios. Incluí los archivos del proyecto, README, Dockerfile, deploy/, docs/ y ENTREGA.txt. No incluyas archivos .env, claves, planillas ajenas al TP ni bases con datos privados. Hacé el commit y el push de los archivos revisados a la rama que vayas a desplegar.

## 3. Crear el proyecto y la base

1. Entrá a https://railway.com/ con tu cuenta.
2. Creá un proyecto vacío.
3. En + New elegí Database y MySQL.
4. Esperá a que arranque. Conservá el volumen de MySQL.
5. Revisá las variables que genera: MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD y MYSQLDATABASE.

No copies la contraseña al chat ni al código. Las variables del servicio web harán referencia a las de MySQL.

## 4. Conectar el repositorio y configurar PHP

Dentro del mismo proyecto, agregá un servicio desde GitHub y elegí el repositorio dj-meet y la rama correcta. Dejá la raíz del proyecto en la raíz del repositorio. Railway detecta el archivo Dockerfile. No agregues un Start Command: la imagen ya arranca Apache.

En Variables del servicio web configurá estas referencias. El ejemplo supone que el servicio de base se llama exactamente MySQL; si tiene otro nombre, elegilo desde el selector del panel.

| Variable en dj-meet | Valor o referencia |
| --- | --- |
| DB_HOST | ${{MySQL.MYSQLHOST}} |
| DB_PORT | ${{MySQL.MYSQLPORT}} |
| DB_USER | ${{MySQL.MYSQLUSER}} |
| DB_PASS | ${{MySQL.MYSQLPASSWORD}} |
| DB_NAME | ${{MySQL.MYSQLDATABASE}} |
| APP_ENV | production |
| PORT | 80 |

Son referencias, no contraseñas pegadas en Git. Aplicá los cambios y desplegá. En los logs debería verse la detección del Dockerfile y el arranque de Apache.

## 5. Importar las tablas

La base cloud ya existe; su nombre puede no ser dj_meet. Hay que importar el script en la base indicada por MYSQLDATABASE, sin crear otra distinta.

Para importar desde tu computadora necesitás la conexión **pública** de MySQL. En MySQL → Settings → Networking habilitá Public Access/TCP Proxy. Usá host y puerto públicos; el host privado terminado en railway.internal sólo sirve entre servicios de Railway.

El cliente mysql de XAMPP puede no soportar la autenticación de versiones modernas de MySQL. Si ocurre, usá un cliente oficial MySQL actual, en lugar de cambiar la autenticación del servidor.

En un cliente gráfico, conectá con esos datos, seleccioná la base provista y ejecutá sql/dj_meet_script.sql. Si usás el cliente oficial mysql desde PowerShell, reemplazá los marcadores:

```powershell
mysql --host=HOST_PUBLICO --port=PUERTO_PUBLICO --user=USUARIO --password --database=NOMBRE_BASE --default-character-set=utf8mb4 --execute="source C:/xampp/htdocs/dj-meet/sql/dj_meet_script.sql"
```

La contraseña se solicita sin escribirla en el comando. El puerto público puede ser distinto de 3306. No publiques la cadena de conexión. Comprobá que existan participantes, salas, sala_participantes, propuestas y votos.

Después de importar podés quitar el acceso público a MySQL; la web sigue conectándose por la red privada. No importes el script otra vez si las tablas ya existen.

## 6. Obtener la URL pública

En el servicio web, abrí Settings → Networking → Generate Domain. Usá el puerto de destino **80**, el que escucha Apache.

Abrí la URL con https://. La página estará en la raíz del dominio, sin /dj-meet ni /frontend. Las rutas relativas ya se adaptan.

Una página de inicio visible no demuestra por sí sola que MySQL funciona: creá una sala y entrá desde otro navegador para comprobarlo.

## 7. Comprobación y entrega

- Crear sala, entrar como invitado, buscar, proponer, votar, elegir, avanzar y eliminar.
- Abrir desde un celular con datos móviles.
- Verificar estados HTTP con Network y que los errores sean JSON.
- Probar la caída del catálogo como se explica en README.
- Completar URLs e integrantes en README y ENTREGA.txt.
- Conservar el servicio y la base durante la corrección. No borrar el volumen.
- Un push a la rama vinculada puede iniciar otro despliegue; revisar que termine bien. No volver a importar el SQL.
- Mantener una sola réplica web porque las sesiones se guardan en archivos locales.

## Si algo falla

| Síntoma | Qué revisar |
| --- | --- |
| 502 / Application failed to respond | Logs de despliegue, Apache iniciado y puerto público 80 |
| No se pudo conectar a la base | Referencias DB_* del servicio web, base iniciada y nombre correcto |
| Tabla inexistente / 500 al crear | Importación del SQL en la base indicada por DB_NAME |
| 401 al abrir directamente la API | Primero crear o entrar a una sala en ese mismo navegador |
| El buscador falla pero la cola funciona | Logs de PHP, conexión saliente HTTPS y CATALOGO_ACTIVO; carga manual disponible |
| El video no suena | Pulsar Play del iframe, revisar restricciones del video o elegir otro |
| Se perdió la sesión tras desplegar | Limitación de sesiones temporales; entrar de nuevo, sin borrar la base |

## Fuentes

- Dockerfile en Railway: https://docs.railway.com/builds/dockerfiles
- MySQL y conexión privada/pública: https://docs.railway.com/databases/mysql
- Variables de entorno: https://docs.railway.com/variables
- Dominio público y HTTPS: https://docs.railway.com/networking/public-networking
- Planes y costos: https://docs.railway.com/pricing/plans
