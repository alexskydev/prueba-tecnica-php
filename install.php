<?php
require __DIR__ . '/app/bootstrap.php';

// Nunca habilite este instalador desde una IP remota.
$clientAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$isLocalRequest = in_array($clientAddress, ['127.0.0.1', '::1'], true);
$isInstalled = is_file(__DIR__ . '/storage/installed.lock');
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLocalRequest) {
        http_response_code(403);
        $errorMessage = 'Por seguridad, la instalación solo se permite desde este mismo equipo (localhost).';
    } elseif ($isInstalled) {
        $successMessage = 'El proyecto ya fue instalado. Puede abrir la aplicación.';
    } elseif (!Http::validCsrf()) {
        $errorMessage = 'Sesión expirada. Actualice la página e inténtelo de nuevo.';
    } else {
        try {
            $connection = Database::connect($config['database'], false);
            $databaseName = $config['database']['name'];
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $databaseName)) {
                throw new RuntimeException('Nombre de la base de datos inválido.');
            }
            $connection->exec('CREATE DATABASE IF NOT EXISTS `' . $databaseName . '`
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $connection->exec('USE `' . $databaseName . '`');
            $connection->exec('CREATE TABLE IF NOT EXISTS persona (
                IdPersona INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Nombres VARCHAR(100) NOT NULL,
                Apellidos VARCHAR(100) NOT NULL,
                Identificacion VARCHAR(20) NOT NULL,
                Genero ENUM(\'M\', \'F\') NOT NULL,
                FechaNacimiento DATE NOT NULL,
                Contrasena VARCHAR(255) NOT NULL,
                Activo TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (IdPersona),
                UNIQUE KEY uq_persona_identificacion (Identificacion),
                KEY idx_persona_activo (Activo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $connection->exec('DROP PROCEDURE IF EXISTS sp_clonar_personas');
            $connection->exec('CREATE PROCEDURE sp_clonar_personas()
                BEGIN
                    DROP TABLE IF EXISTS persona_respaldo;
                    CREATE TABLE persona_respaldo LIKE persona;
                    INSERT INTO persona_respaldo SELECT * FROM persona;
                END');

            if (!is_dir(__DIR__ . '/storage')) {
                mkdir(__DIR__ . '/storage', 0755, true);
            }
            if (file_put_contents(__DIR__ . '/storage/installed.lock', date('c')) === false) {
                throw new RuntimeException('No se pudo crear el archivo de instalación en storage/.');
            }
            $isInstalled = true;
            $successMessage = 'Base de datos, tabla persona y procedimiento almacenado instalados correctamente.';
        } catch (Throwable $exception) {
            error_log('[Prueba_Narvaez][Instalación] ' . $exception->getMessage());
            $errorMessage = 'No se completó la instalación. Revise la conexión y el registro de errores de Apache.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalación | Prueba Narváez</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="install-page">
    <main class="container py-5" style="max-width:740px">
        <div class="install-logo">PN</div>
        <div class="card border-0 shadow-lg install-card">
            <div class="card-body p-4 p-md-5">
                <span class="overline dark-overline">PRUEBA TÉCNICA · CONFIGURACIÓN LOCAL</span>
                <h1 class="h2 fw-bold mt-2 mb-3">Instalar la aplicación</h1>
                <p class="text-muted mb-4">Con Apache y MySQL encendidos en XAMPP, este instalador crea la base de datos, la tabla <code>persona</code> y el procedimiento <code>sp_clonar_personas</code>.</p>
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="status"><?= Http::escapeHtml($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger" role="alert"><?= Http::escapeHtml($errorMessage) ?></div>
                <?php endif; ?>
                <?php if (!$isLocalRequest): ?>
                    <div class="alert alert-warning">Por seguridad, acceda desde <strong>localhost</strong> en el mismo equipo donde está instalado XAMPP.</div>
                <?php endif; ?>
                <dl class="row small mb-4 info-dl">
                    <dt class="col-sm-5">Base de datos</dt><dd class="col-sm-7"><code><?= Http::escapeHtml($config['database']['name']) ?></code></dd>
                    <dt class="col-sm-5">Servidor</dt><dd class="col-sm-7"><?= Http::escapeHtml($config['database']['host']) ?>:<?= (int) $config['database']['port'] ?></dd>
                    <dt class="col-sm-5">Usuario MySQL</dt><dd class="col-sm-7"><code><?= Http::escapeHtml($config['database']['user']) ?></code></dd>
                    <dt class="col-sm-5">Archivos SQL</dt><dd class="col-sm-7"><code>database/</code></dd>
                </dl>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (!$isInstalled && $isLocalRequest): ?>
                        <form method="post" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= Http::escapeHtml(Http::csrfToken()) ?>">
                            <button type="submit" class="btn btn-primary btn-lg">Instalar ahora</button>
                        </form>
                    <?php endif; ?>
                    <a class="btn btn-outline-secondary btn-lg" href="index.php"><?= $isInstalled ? 'Abrir aplicación' : 'Volver a la aplicación' ?></a>
                </div>
                <p class="small text-muted mt-4 mb-0">Si MySQL usa contraseña o puerto personalizado, edite <code>config.php</code>. También puede importar manualmente los scripts de <code>database/</code>.</p>
            </div>
        </div>
        <p class="text-center small text-muted mt-4">Proyecto de evaluación · PHP 7.4 · MySQL · jQuery · Bootstrap</p>
    </main>
</body>
</html>
