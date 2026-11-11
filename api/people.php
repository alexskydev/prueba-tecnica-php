<?php
/** Controlador HTTP: valida solicitudes y delega el acceso SQL al repositorio. */
require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($requestMethod, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        Http::json(405, ['ok' => false, 'message' => 'Método HTTP no permitido.']);
    }
    if ($requestMethod === 'POST' && !Http::validCsrf()) {
        Http::json(403, ['ok' => false, 'message' => 'La sesión expiró. Actualice la página.']);
    }

    $action = $requestMethod === 'GET' ? Http::getString('action', 'list') : Http::postString('action');
    $repository = new PersonRepository(Database::connect($config['database']));

    if ($requestMethod === 'GET' && $action === 'get') {
        $personId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$personId || $personId < 1) {
            Http::json(422, ['ok' => false, 'message' => 'Identificador inválido.']);
        }
        $person = $repository->find($personId);
        if ($person === null) {
            Http::json(404, ['ok' => false, 'message' => 'La persona no existe.']);
        }
        Http::json(200, ['ok' => true, 'person' => $person]);
    }

    if ($requestMethod === 'GET' && $action === 'list') {
        $searchTerm = Http::getString('q');
        $status = Http::getString('status', 'all');
        if ((function_exists('mb_strlen') ? mb_strlen($searchTerm, 'UTF-8') :
                preg_match_all('/./us', $searchTerm)) > 100) {
            Http::json(422, ['ok' => false, 'message' => 'La búsqueda es demasiado larga.']);
        }
        if (!in_array($status, ['all', '1', '0'], true)) {
            Http::json(422, ['ok' => false, 'message' => 'Filtro de estado inválido.']);
        }
        $pageValue = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $page = $pageValue && $pageValue > 0 ? $pageValue : 1;
        Http::json(200, ['ok' => true] + $repository->paginate($searchTerm, $status, $page));
    }

    if ($requestMethod === 'POST' && in_array($action, ['create', 'update'], true)) {
        $isUpdate = $action === 'update';
        $personId = $isUpdate ? filter_var(Http::postString('id'), FILTER_VALIDATE_INT) : null;
        if ($isUpdate && (!$personId || $personId < 1)) {
            Http::json(422, ['ok' => false, 'message' => 'Identificador inválido.']);
        }
        $validation = PersonValidator::validate($_POST, $isUpdate);
        if ($validation['errors']) {
            Http::json(422, [
                'ok' => false, 'message' => 'Revise los campos del formulario.',
                'errors' => $validation['errors'],
            ]);
        }
        if ($isUpdate) {
            if (!$repository->update($personId, $validation['data'])) {
                Http::json(404, ['ok' => false, 'message' => 'La persona que intenta editar ya no existe.']);
            }
            Http::json(200, ['ok' => true, 'message' => 'Persona actualizada correctamente.']);
        }
        $createdId = $repository->create($validation['data']);
        Http::json(201, [
            'ok' => true, 'message' => 'Persona registrada correctamente.', 'id' => $createdId,
        ]);
    }

    if ($requestMethod === 'POST' && $action === 'delete') {
        $personId = filter_var(Http::postString('id'), FILTER_VALIDATE_INT);
        if (!$personId || $personId < 1) {
            Http::json(422, ['ok' => false, 'message' => 'Identificador inválido.']);
        }
        if (!$repository->delete($personId)) {
            Http::json(404, ['ok' => false, 'message' => 'La persona ya no existe.']);
        }
        Http::json(200, ['ok' => true, 'message' => 'Persona eliminada correctamente.']);
    }

    if ($requestMethod === 'POST' && $action === 'backup') {
        $count = $repository->cloneTable();
        Http::json(200, [
            'ok' => true, 'message' => 'Tabla persona_respaldo creada con los mismos campos y registros.',
            'total' => $count,
        ]);
    }

    Http::json(404, ['ok' => false, 'message' => 'Acción no encontrada.']);
} catch (PDOException $exception) {
    error_log('[Prueba_Narvaez] ' . $exception->getMessage());
    $mysqlCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : 0;
    if ($mysqlCode === 1062) {
        Http::json(409, [
            'ok' => false, 'message' => 'Ya existe una persona con esa identificación.',
            'errors' => ['identification' => 'Esta identificación ya está registrada.'],
        ]);
    }
    Http::json(500, [
        'ok' => false, 'message' => 'No se pudo completar la operación. Verifique MySQL y la instalación.',
    ]);
} catch (Throwable $exception) {
    error_log('[Prueba_Narvaez] ' . $exception->getMessage());
    Http::json(500, ['ok' => false, 'message' => 'Ocurrió un error inesperado.']);
}
