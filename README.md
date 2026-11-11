# Prueba_Narvaez — Prueba técnica de desarrollo PHP

Aplicación CRUD de personas desarrollada con **PHP 7.4, PDO MySQL, jQuery (AJAX) y Bootstrap**. Todos los textos de la interfaz están en español. Las rutas, clases, funciones y variables del código utilizan nombres en inglés; se conservan los **nombres de base de datos, tabla, columnas y procedimiento almacenado** de la versión verificada para cumplir el enunciado y mantener compatibilidad.

## 1. Requisitos

- Windows con XAMPP (Apache + MySQL/MariaDB), o un entorno equivalente.
- PHP 7.4 o superior con la extensión `pdo_mysql` habilitada.
- MySQL 5.7/8.0 o MariaDB 10.4+.
- Navegador moderno (Chrome, Edge o Firefox).
- No requiere Composer, Node.js, conexión a internet ni compilación. jQuery **3.6.1** y Bootstrap **5.1.1** se incluyen localmente.

> PHP 7.4 está fuera de soporte de seguridad; se mantiene por compatibilidad con la prueba. Este proyecto es una **demostración local**, sin sistema de autenticación; no lo exponga en internet tal cual.

## 2. Instalación rápida

1. Extraiga `Prueba_Narvaez` dentro de la carpeta `htdocs` de XAMPP. Ejemplo: `C:\xampp\htdocs\Proyectos\Prueba_Narvaez\`.
2. Inicie **Apache** y **MySQL** desde el panel de XAMPP.
3. Abra `config.php` y revise los parámetros de conexión: por defecto servidor **127.0.0.1**, puerto **3306**, usuario **root** y contraseña **vacía** (valor habitual de XAMPP, pero puede variar).
4. Abra **http://localhost/Proyectos/Prueba_Narvaez/install.php** en el navegador (ajuste `/Proyectos/` si instaló directamente dentro de `htdocs`).
5. Pulse **Instalar ahora**. Se crearán, si no existen, la base de datos `NombreDB_Narvaez` y la tabla `persona`, y se registrará el procedimiento `sp_clonar_personas`.
6. Haga clic en **Abrir aplicación** o visite **http://localhost/Proyectos/Prueba_Narvaez/**.

**Importante:** si su instalación usa otro puerto (por ejemplo **3307**, como en una instalación local donde 3306 está ocupado), cambie únicamente `'port' => 3306` por `'port' => 3307` en **su copia local** de `config.php`. El paquete de entrega mantiene **3306** como valor predeterminado.

**Instalador:** se puede ejecutar solo desde `localhost`. Tras una instalación correcta se genera `storage/installed.lock` para evitar repeticiones accidentales. El ZIP no incluye ese archivo de bloqueo ni información personal de pruebas. No es necesario ejecutar el instalador para utilizar una base de datos ya creada con los mismos scripts SQL.

## 3. Instalación manual con MySQL Workbench o phpMyAdmin

1. Ejecute `database/01_schema.sql` para crear la base de datos y la tabla.
2. Ejecute `database/02_stored_procedure.sql` para crear el procedimiento almacenado.
3. Configure `config.php` con los datos del servidor MySQL y abra `index.php` desde Apache.

No se incluyen datos precargados: los registros de demostración se pueden crear desde la interfaz. **No se requiere el antiguo archivo de datos opcionales**, por eso se retiró de la entrega.

## 4. Funcionalidades

- **Crear:** nombres, apellidos, identificación única, género M/F, fecha de nacimiento, contraseña y estado.
- **Consultar:** listado con búsqueda, filtro de activos/inactivos, tarjetas de estadísticas y paginación asíncrona.
- **Actualizar:** todos los campos. Dejar la contraseña vacía conserva el hash almacenado.
- **Eliminar:** con confirmación y petición AJAX.
- **Clonar tabla:** ejecuta `CALL sp_clonar_personas()` y recrea `persona_respaldo` con la misma estructura, índices y registros de `persona`.
- **Validar:** comprobaciones de formulario con jQuery y validación independiente del lado del servidor.
- **Seguridad básica:** sentencias preparadas PDO, protección CSRF en operaciones POST, contraseñas con `password_hash(..., PASSWORD_BCRYPT)` y HTML escapado al presentar datos.

El respaldo es una **copia puntual**, no un historial. Cada ejecución reemplaza `persona_respaldo` con el estado actual de `persona`.

## 5. Arquitectura y estructura del código

```text
Prueba_Narvaez/
├── .htaccess                     Protección básica de archivos
├── index.php                     Vista y formulario (español)
├── install.php                   Instalación local protegida
├── config.php                    Configuración PDO (puerto 3306)
├── api/
│   └── people.php                Controlador HTTP de operaciones AJAX
├── app/
│   ├── bootstrap.php             Carga clases, sesión y token CSRF
│   ├── Database.php              Factoría de conexión PDO
│   ├── Http.php                  Lectura/respuestas HTTP y escape HTML
│   ├── PersonRepository.php      Consultas SQL y llamada al procedimiento
│   └── PersonValidator.php       Reglas de validación independientes
├── assets/
│   ├── css/styles.css            Estilos visuales
│   ├── js/app.js                  Eventos jQuery y solicitudes AJAX
│   └── vendor/                   Bootstrap + jQuery distribuidos localmente
├── database/
│   ├── 01_schema.sql             Creación de la base y tabla
│   └── 02_stored_procedure.sql   Creación del procedimiento almacenado
├── storage/                      Bloqueo de instalador (generado en ejecución)
├── tests/
│   └── person_validation_test.php Pruebas unitarias sin MySQL
```


**Dependencias de terceros:** jQuery 3.6.1 y Bootstrap 5.1.1, ambas bajo licencia MIT. Los avisos de autoría y licencia se mantienen en los archivos distribuidos; puede consultarse el texto de licencia en los sitios oficiales de [jQuery](https://jquery.org/license/) y [Bootstrap](https://github.com/twbs/bootstrap/blob/v5.1.1/LICENSE).

### ¿Por qué esa arquitectura?

Para una prueba de este alcance, la separación por responsabilidades es suficiente sin introducir frameworks o abstracciones innecesarias:

- `index.php` construye la interfaz, pero **no contiene consultas SQL**.
- `assets/js/app.js` controla los modales, las validaciones de experiencia de usuario y las solicitudes AJAX; **no accede directamente a MySQL**.
- `api/people.php` recibe la solicitud, comprueba el método y el token CSRF, invoca la validación o el repositorio y responde en JSON.
- `PersonValidator.php` define reglas reutilizables y comprobables sin base de datos.
- `PersonRepository.php` centraliza el mapeo entre **atributos en inglés** y **columnas SQL del enunciado**.
- `Database.php` concentra la conexión PDO y los parámetros de seguridad.

**Contrato de la base de datos preservado:** `NombreDB_Narvaez`, `persona` (`IdPersona`, `Nombres`, `Apellidos`, `Identificacion`, `Genero`, `FechaNacimiento`, `Contrasena`, `Activo`) y `sp_clonar_personas()`. Renombrar esas columnas rompería el esquema previamente probado y no aportaría valor a la evaluación.

## 6. Comprobación del procedimiento almacenado

Ejecute en MySQL Workbench después de crear algunos registros:

```sql
USE NombreDB_Narvaez;
CALL sp_clonar_personas();
SELECT COUNT(*) AS total_original FROM persona;
SELECT COUNT(*) AS total_respaldo FROM persona_respaldo;
SHOW COLUMNS FROM persona;
SHOW COLUMNS FROM persona_respaldo;
```

Ambos conteos deben coincidir y las tablas deben tener los mismos campos. **Atención:** el procedimiento reemplaza la copia anterior.

## 7. Pruebas y verificación

Desde la raíz del proyecto, con PHP disponible en la terminal:

```bash
php tests/person_validation_test.php
```

Verifica escenarios de datos válidos/inválidos y bcrypt; no requiere servidor MySQL. Para verificar el CRUD, ejecute la aplicación con Apache + MySQL y pruebe crear, listar, editar, eliminar, buscar, filtrar, paginar y clonar. Con F12 → **Red** puede ver las solicitudes AJAX a `api/people.php` sin recargar la página.

### Problemas habituales

| Problema | Solución |
| --- | --- |
| `SQLSTATE[HY000] [2002]` al conectarse | Verifique que MySQL esté iniciado y el host/puerto en `config.php`. |
| Puerto **3306** ocupado | Use otro puerto solo en su entorno local (p. ej., 3307) y modifique `config.php`. |
| `404` en el navegador | Compruebe la ruta real dentro de `htdocs` y el nombre del archivo `install.php`. |
| `403` por CSRF | Actualice la página para obtener un token de sesión válido. |
| Identificación duplicada | Use una identificación distinta. La base de datos también tiene un índice único. |

## 8. Versiones utilizadas

| Componente | Versión / detalle |
| --- | --- |
| PHP | Código compatible con PHP **7.4+** (sin framework) |
| MySQL / MariaDB | MySQL 5.7+/8.0 o MariaDB 10.4+ |
| PDO MySQL | Consultas preparadas, `ATTR_EMULATE_PREPARES = false` |
| jQuery | **3.6.1**, archivo local |
| Bootstrap | **5.1.1**, CSS y bundle JS locales |

El código fuente es editable en Visual Studio Code. La documentación de esta entrega se encuentra en este README y en los archivos del proyecto.
