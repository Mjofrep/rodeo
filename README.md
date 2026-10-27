# Torneo ENEL MT

Aplicación PHP para registrar la evaluación de cuatro cuadrillas en dos competencias y mostrar el tablero actualizado.

## Inicio

1. Inicie MySQL en MAMP con `localhost:8889`, usuario `root` y contraseña `root`.
2. Abra `http://localhost:8888/rodeo/` o sirva el directorio con `php -S localhost:8000`.
3. La primera petición crea la base de datos `rodeo` y la tabla `rodeo`. El esquema equivalente está en `database.sql`.

La página específica de evaluación es `evaluar.php`. Requiere inicio de sesión y recupera el borrador almacenado al volver a abrirla. La primera ejecución crea la tabla `jueces` con código, nombre completo y hash de contraseña. Los accesos iniciales son `J01` a `J04`, con las contraseñas temporales `1001` a `1004`; reemplácelos directamente en la tabla antes del evento.

Los cuatro jueces evalúan ambas competencias; el tablero calcula cada actividad como el promedio de las cuatro pautas. Una calificación `NC` en un indicador crítico deja la prueba detenida y no permite cerrarla.
