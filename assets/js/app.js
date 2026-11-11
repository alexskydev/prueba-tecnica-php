/* global jQuery, bootstrap */
(function ($) {
    'use strict';

    var API = 'api/people.php';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var personModal = new bootstrap.Modal(document.getElementById('person-modal'));
    var deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
    var toast = new bootstrap.Toast(document.getElementById('app-toast'), { delay: 3800 });
    var page = 1;
    var pages = 1;
    var editing = false;
    var deletionId = null;
    var requestSequence = 0;
    var searchTimer;

    function apiError(xhr) {
        return (xhr.responseJSON && xhr.responseJSON.message)
            || 'No se pudo comunicar con el servidor. Revise Apache, MySQL y la conexión.';
    }

    function showToast(message) {
        $('#toast-message').text(message);
        toast.show();
    }

    function setError(field, message) {
        var $control = $('#' + field);
        $control.addClass('is-invalid').attr('aria-invalid', 'true');
        $control.siblings('.invalid-feedback').first().text(message);
    }

    function clearErrors() {
        $('#person-form .is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $('#person-form .invalid-feedback').text('');
        $('#form-general-error').addClass('d-none').text('');
    }

    function setFormError(message) {
        $('#form-general-error').removeClass('d-none').text(message);
    }

    function validDate(value) {
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        if (!match) return false;
        var year = Number(match[1]), month = Number(match[2]), day = Number(match[3]);
        var date = new Date(year, month - 1, day);
        var now = new Date();
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day &&
            value >= '1900-01-01' && date <= now;
    }

    function validateForm() {
        clearErrors();
        var good = true;
        ['firstName', 'lastName'].forEach(function (key) {
            var fieldValue = $('#' + key).val().trim();
            if (fieldValue.length < 2 || fieldValue.length > 100 || !/^[\p{L}\p{M}][\p{L}\p{M} .'\-]*$/u.test(fieldValue)) {
                setError(key, 'Ingrese entre 2 y 100 caracteres válidos.');
                good = false;
            }
        });
        if (!/^\d{4,20}$/.test($('#identification').val().trim())) {
            setError('identification', 'Ingrese entre 4 y 20 dígitos.');
            good = false;
        }
        if (!['M', 'F'].includes($('#gender').val())) {
            setError('gender', 'Seleccione una opción.');
            good = false;
        }
        if (!validDate($('#birthDate').val())) {
            setError('birthDate', 'Seleccione una fecha válida (desde 1900 hasta hoy).');
            good = false;
        }
        if (!['0', '1'].includes($('#active').val())) {
            setError('active', 'Seleccione un estado.');
            good = false;
        }
        var passwordValue = $('#password').val();
        if ((!editing && passwordValue.length === 0) || (passwordValue && (new TextEncoder().encode(passwordValue).length < 8 || new TextEncoder().encode(passwordValue).length > 72))) {
            setError('password', 'Ingrese una contraseña de 8 a 72 bytes.');
            good = false;
        }
        if (!good) $('#person-form .is-invalid').first().trigger('focus');
        return good;
    }

    function makePersonRow(person) {
        var id = Number(person.id);
        var active = Number(person.active) === 1;
        var name = person.firstName + ' ' + person.lastName;
        var initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (word) {
            return word.charAt(0).toUpperCase();
        }).join('');
        var $row = $('<tr>');
        var $name = $('<div class="person-cell">');
        $('<span class="avatar">').text(initials).appendTo($name);
        var $personText = $('<div>');
        $('<strong class="person-name">').text(name).appendTo($personText);
        $('<span class="person-id">').text('ID #' + id).appendTo($personText);
        $personText.appendTo($name);
        $('<td>').append($name).appendTo($row);
        $('<td class="document">').text(person.identification).appendTo($row);
        $('<td>').text(person.gender === 'M' ? 'Masculino' : 'Femenino').appendTo($row);
        $('<td class="birth">').text(formatDate(person.birthDate)).appendTo($row);
        $('<td>').append($('<span>').addClass('status-pill ' + (active ? 'status-active' : 'status-inactive'))
            .text(active ? '● active' : '● Inactivo')).appendTo($row);
        var $actions = $('<div class="row-actions">');
        $('<button type="button" class="btn btn-table btn-edit" aria-label="Editar persona">')
            .text('Editar').attr('data-id', id).appendTo($actions);
        $('<button type="button" class="btn btn-table btn-delete" aria-label="Eliminar persona">')
            .text('Eliminar').attr('data-id', id).attr('data-name', name).appendTo($actions);
        $('<td class="text-end">').append($actions).appendTo($row);
        return $row;
    }

    function formatDate(date) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return date;
        return date.slice(8, 10) + '/' + date.slice(5, 7) + '/' + date.slice(0, 4);
    }

    function listPeople() {
        var currentRequest = ++requestSequence;
        $('#page-alert').addClass('d-none').text('');
        $('#people-body').html('<tr><td colspan="6" class="loading-cell text-center py-5">Cargando personas...</td></tr>');
        $.ajax({
            url: API,
            method: 'GET',
            dataType: 'json',
            data: { action: 'list', q: $('#search').val(), status: $('#filter-status').val(), page: page }
        }).done(function (response) {
            if (currentRequest !== requestSequence) return;
            var $body = $('#people-body').empty();
            response.people.forEach(function (person) {
                $body.append(makePersonRow(person));
            });
            if (!response.people.length) {
                var $empty = $('<td colspan="6" class="text-center empty-cell">');
                $('<div class="empty-illustration" aria-hidden="true">').text('⌕').appendTo($empty);
                $('<strong>').text('No encontramos personas').appendTo($empty);
                $('<p class="mb-0">').text('Prueba otra búsqueda o agrega tu primer registro.').appendTo($empty);
                $body.append($('<tr>').append($empty));
            }
            $('#stat-total').text(response.statistics.total);
            $('#stat-active').text(response.statistics.active);
            $('#stat-inactive').text(response.statistics.inactive);
            $('#results-badge').text(response.pagination.filtered);
            page = response.pagination.page;
            pages = response.pagination.pages;
            $('#page-indicator').text(page + ' / ' + pages);
            $('#btn-prev').prop('disabled', page <= 1);
            $('#btn-next').prop('disabled', page >= pages);
            var first = response.pagination.filtered ? (page - 1) * response.pagination.perPage + 1 : 0;
            var last = response.pagination.filtered ? first + response.people.length - 1 : 0;
            $('#pagination-summary').text('Mostrando ' + first + '–' + last + ' de ' + response.pagination.filtered + ' resultados');
        }).fail(function (xhr) {
            if (currentRequest !== requestSequence) return;
            $('#people-body').html('<tr><td colspan="6" class="text-center py-5 text-muted">No se pudieron cargar los registros.</td></tr>');
            $('#page-alert').empty().append(document.createTextNode(apiError(xhr) + ' '));
            $('<a href="install.php" class="alert-link">').text('Ir al instalador').appendTo($('#page-alert'));
            $('#page-alert').removeClass('d-none');
        });
    }

    function requestPost(data, button, done, fail) {
        var $button = $(button);
        var original = $button.text();
        $button.prop('disabled', true).text('Procesando...');
        data.csrf_token = csrfToken;
        $.ajax({ url: API, method: 'POST', dataType: 'json', data: data })
            .done(done)
            .fail(function (xhr) {
                if (fail) fail(xhr);
                else showToast(apiError(xhr));
            }).always(function () {
                $button.prop('disabled', false).text(original);
            });
    }

    $('#btn-new').on('click', function () {
        editing = false;
        $('#person-form')[0].reset();
        $('#id').val('');
        $('#active').val('1');
        $('#person-modal-title').text('Nueva persona');
        $('#modal-subtitle').text('Completa la información para agregar un registro.');
        $('#btn-save').text('Guardar persona');
        $('#password-required').show();
        $('#password-help').text('Se almacena como hash seguro (bcrypt); nunca se muestra en la tabla.');
        clearErrors();
        personModal.show();
    });

    $(document).on('click', '.btn-edit', function () {
        var $button = $(this);
        $button.prop('disabled', true);
        $.getJSON(API, { action: 'get', id: $button.attr('data-id') })
            .done(function (response) {
                editing = true;
                clearErrors();
                $('#person-form')[0].reset();
                ['id', 'firstName', 'lastName', 'identification', 'gender', 'birthDate', 'active'].forEach(function (key) {
                    $('#' + key).val(response.person[key]);
                });
                $('#password').val('');
                $('#person-modal-title').text('Editar persona');
                $('#modal-subtitle').text('Actualiza los datos del registro seleccionado.');
                $('#btn-save').text('Guardar cambios');
                $('#password-required').hide();
                $('#password-help').text('Deja este campo vacío para conservar la contraseña actual.');
                personModal.show();
            }).fail(function (xhr) {
                showToast(apiError(xhr));
                listPeople();
            }).always(function () {
                $button.prop('disabled', false);
            });
    });

    $('#person-form').on('submit', function (event) {
        event.preventDefault();
        if (!validateForm()) return;
        var data = {
            action: editing ? 'update' : 'create',
            id: $('#id').val(),
            firstName: $('#firstName').val().trim(),
            lastName: $('#lastName').val().trim(),
            identification: $('#identification').val().trim(),
            gender: $('#gender').val(),
            birthDate: $('#birthDate').val(),
            active: $('#active').val(),
            password: $('#password').val()
        };
        requestPost(data, '#btn-save', function (response) {
            personModal.hide();
            showToast(response.message);
            page = 1;
            listPeople();
        }, function (xhr) {
            var json = xhr.responseJSON || {};
            if (json.errors) {
                Object.keys(json.errors).forEach(function (key) { setError(key, json.errors[key]); });
                $('#person-form .is-invalid').first().trigger('focus');
            }
            setFormError(apiError(xhr));
        });
    });

    $('#person-form').on('input change', 'input,select', function () {
        $(this).removeClass('is-invalid').removeAttr('aria-invalid');
        $(this).siblings('.invalid-feedback').first().text('');
    });

    $(document).on('click', '.btn-delete', function () {
        deletionId = $(this).attr('data-id');
        $('#delete-person-name').text($(this).attr('data-name'));
        deleteModal.show();
    });

    $('#btn-confirm-delete').on('click', function () {
        if (!deletionId) return;
        requestPost({ action: 'delete', id: deletionId }, '#btn-confirm-delete', function (response) {
            deleteModal.hide();
            deletionId = null;
            showToast(response.message);
            listPeople();
        });
    });

    $('#btn-backup').on('click', function () {
        requestPost({ action: 'backup' }, '#btn-backup', function (response) {
            showToast('Respaldo actualizado: ' + response.total + ' registro(s) copiados a persona_respaldo.');
        });
    });

    $('#search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { page = 1; listPeople(); }, 270);
    });
    $('#filter-status').on('change', function () { page = 1; listPeople(); });
    $('#btn-prev').on('click', function () { if (page > 1) { page--; listPeople(); } });
    $('#btn-next').on('click', function () { if (page < pages) { page++; listPeople(); } });
    $('#person-modal').on('shown.bs.modal', function () { $('#firstName').trigger('focus'); });

    listPeople();
})(jQuery);
