<?php
require __DIR__ . '/app/bootstrap.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= Http::escapeHtml(Http::csrfToken()) ?>">
    <meta name="description" content="Aplicación CRUD de personas para prueba técnica PHP y MySQL">
    <title>Gestión de personas | Prueba Narváez</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container-xl d-flex align-items-center justify-content-between gap-3">
            <div class="brand d-flex align-items-center gap-3">
                <div class="brand-symbol" aria-hidden="true">PN</div>
                <div class="brand-caption">
                    <strong>Prueba Narváez</strong>
                    <span>Panel de administración</span>
                </div>
            </div>
            <div class="header-tech d-none d-sm-flex align-items-center gap-2">
                <span class="header-dot"></span> PHP · MySQL · AJAX
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container-xl hero-inner">
                <div class="row align-items-end g-4">
                    <div class="col-lg-8">
                        <span class="overline"><span class="mini-star">✦</span> PRUEBA TÉCNICA · DESARROLLADOR PHP</span>
                        <h1>Gestión de <span>personas.</span></h1>
                        <p>Administra registros de forma sencilla, segura y sin recargar la página.</p>
                    </div>
                    <div class="col-lg-4 hero-actions">
                        <button type="button" id="btn-backup" class="btn btn-outline-light btn-action-secondary">
                            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg>
                            Clonar tabla
                        </button>
                        <button type="button" id="btn-new" class="btn btn-light btn-add">
                            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Nueva persona
                        </button>
                    </div>
                </div>
                <div class="stats-row row g-3">
                    <div class="col-12 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon stat-total" aria-hidden="true">◫</div>
                            <div><div class="stat-name">Total de registros</div><strong id="stat-total">—</strong></div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon stat-active" aria-hidden="true">✓</div>
                            <div><div class="stat-name">Personas activas</div><strong id="stat-active">—</strong></div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon stat-inactive" aria-hidden="true">○</div>
                            <div><div class="stat-name">Personas inactivas</div><strong id="stat-inactive">—</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-xl content-section" aria-labelledby="section-title">
            <div class="panel card border-0">
                <div class="panel-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h2 id="section-title" class="panel-title mb-0">Directorio de personas</h2>
                            <span id="results-badge" class="count-badge">0</span>
                        </div>
                        <p class="panel-subtitle mb-0">Consulta, edita o elimina registros en tiempo real.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap panel-controls">
                        <div class="search-wrap">
                            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                            <input type="search" id="search" class="form-control" placeholder="Buscar por nombre o documento" aria-label="Buscar personas" maxlength="100" autocomplete="off">
                        </div>
                        <select id="filter-status" class="form-select" aria-label="Filtrar por estado">
                            <option value="all">Todos los estados</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
                <div id="page-alert" class="alert alert-danger mx-4 mt-3 d-none" role="alert"></div>
                <div class="table-responsive">
                    <table class="table align-middle people-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">PERSONA</th>
                                <th scope="col">IDENTIFICACIÓN</th>
                                <th scope="col">GÉNERO</th>
                                <th scope="col">NACIMIENTO</th>
                                <th scope="col">ESTADO</th>
                                <th scope="col" class="text-end">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="people-body">
                            <tr><td colspan="6" class="loading-cell text-center py-5">Cargando personas...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="panel-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <span id="pagination-summary" class="pagination-summary">—</span>
                    <nav aria-label="Paginación de personas">
                        <div class="d-flex align-items-center gap-2">
                            <button id="btn-prev" type="button" class="btn btn-page" disabled aria-label="Página anterior">‹</button>
                            <span id="page-indicator" class="page-indicator">1 / 1</span>
                            <button id="btn-next" type="button" class="btn btn-page" disabled aria-label="Página siguiente">›</button>
                        </div>
                    </nav>
                </div>
            </div>
            <div class="bottom-note d-flex flex-wrap align-items-center justify-content-between gap-3">
                <span><span class="note-dot"></span> Operaciones asíncronas mediante AJAX y jQuery</span>
                <span>PHP 7.4 · jQuery 3.6.1 · Bootstrap 5.1.1</span>
            </div>
        </section>
    </main>

    <div class="modal fade" id="person-modal" tabindex="-1" aria-labelledby="person-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content person-modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="modal-eyebrow">ADMINISTRACIÓN DE REGISTROS</span>
                        <h2 class="modal-title h4 mt-1" id="person-modal-title">Nueva persona</h2>
                        <p class="text-muted small mb-0" id="modal-subtitle">Completa la información para agregar un registro.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="person-form" novalidate>
                    <div class="modal-body pt-4">
                        <input type="hidden" name="id" id="id" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">Nombres <span class="req">*</span></label>
                                <input type="text" id="firstName" name="firstName" class="form-control" maxlength="100" autocomplete="given-name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Apellidos <span class="req">*</span></label>
                                <input type="text" id="lastName" name="lastName" class="form-control" maxlength="100" autocomplete="family-name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="identification" class="form-label">Identificación <span class="req">*</span></label>
                                <input type="text" id="identification" name="identification" class="form-control" maxlength="20" inputmode="numeric" placeholder="Solo números" autocomplete="off" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Género <span class="req">*</span></label>
                                <select id="gender" name="gender" class="form-select" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="M">Masculino (M)</option>
                                    <option value="F">Femenino (F)</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="birthDate" class="form-label">Fecha de nacimiento <span class="req">*</span></label>
                                <input type="date" id="birthDate" name="birthDate" class="form-control" min="1900-01-01" max="<?= Http::escapeHtml(date('Y-m-d')) ?>" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="active" class="form-label">Estado <span class="req">*</span></label>
                                <select id="active" name="active" class="form-select" required>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label for="password" class="form-label">Contraseña <span class="req" id="password-required">*</span></label>
                                <input type="password" id="password" name="password" class="form-control" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                                <div class="invalid-feedback"></div>
                                <div id="password-help" class="form-text">Se protege mediante un hash seguro (bcrypt); nunca se muestra en la tabla.</div>
                            </div>
                        </div>
                        <div id="form-general-error" class="alert alert-danger mt-3 d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn-save" class="btn btn-primary btn-save">Guardar persona</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete-modal" tabindex="-1" aria-labelledby="delete-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content person-modal-content">
                <div class="modal-body text-center p-4 p-sm-5">
                    <div class="danger-icon mx-auto mb-3">!</div>
                    <h2 class="h4 fw-bold" id="delete-modal-title">¿Eliminar este registro?</h2>
                    <p class="text-muted mb-4">Se eliminará a <strong id="delete-person-name"></strong>. Esta acción no se puede deshacer.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-confirm-delete" class="btn btn-danger px-4">Sí, eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div id="app-toast" class="toast shadow border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="toast-header">
                <span class="toast-mark me-2" aria-hidden="true">✓</span>
                <strong class="me-auto">Gestión de personas</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body" id="toast-message"></div>
        </div>
    </div>

    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
