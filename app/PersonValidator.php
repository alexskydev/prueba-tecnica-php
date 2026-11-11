<?php
/** Reglas de negocio de un registro. No depende de HTTP ni de MySQL. */
final class PersonValidator
{
    private static function stringField(array $input, string $key, bool $trim = true): string
    {
        $value = isset($input[$key]) && is_string($input[$key]) ? $input[$key] : '';
        return $trim ? trim($value) : $value;
    }

    private static function textLength(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : (int) preg_match_all('/./us', $value);
    }

    private static function validBirthDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return false;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value &&
            $value >= '1900-01-01' && $value <= date('Y-m-d');
    }

    /**
     * @return array{data: array<string, string>, errors: array<string, string>}
     * Se utilizan nombres de atributos en inglés; las columnas SQL se conservan
     * en español porque así las exige el documento de la prueba.
     */
    public static function validate(array $input, bool $isUpdate): array
    {
        $data = [
            'firstName' => self::stringField($input, 'firstName'),
            'lastName' => self::stringField($input, 'lastName'),
            'identification' => self::stringField($input, 'identification'),
            'gender' => self::stringField($input, 'gender'),
            'birthDate' => self::stringField($input, 'birthDate'),
            'password' => self::stringField($input, 'password', false),
            'active' => self::stringField($input, 'active'),
        ];
        $errors = [];

        foreach (['firstName', 'lastName'] as $field) {
            $value = $data[$field];
            if (self::textLength($value) < 2 || self::textLength($value) > 100 ||
                !preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\'\-]*$/uD', $value)) {
                $errors[$field] = 'Ingrese entre 2 y 100 caracteres válidos.';
            }
        }
        if (!preg_match('/^[0-9]{4,20}$/D', $data['identification'])) {
            $errors['identification'] = 'Use entre 4 y 20 dígitos.';
        }
        if (!in_array($data['gender'], ['M', 'F'], true)) {
            $errors['gender'] = 'Seleccione M o F.';
        }
        if (!self::validBirthDate($data['birthDate'])) {
            $errors['birthDate'] = 'Seleccione una fecha válida (desde 1900 hasta hoy).';
        }
        if (!$isUpdate && $data['password'] === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        }
        if ($data['password'] !== '' &&
            (strlen($data['password']) < 8 || strlen($data['password']) > 72)) {
            $errors['password'] = 'Use entre 8 y 72 bytes de contraseña.';
        }
        if (!in_array($data['active'], ['0', '1'], true)) {
            $errors['active'] = 'Seleccione un estado válido.';
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
