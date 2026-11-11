<?php
/** Pruebas unitarias sin dependencia de MySQL (ejecutar por consola). */
require dirname(__DIR__) . '/app/bootstrap.php';

function runValidationTest(string $description, array $changes, bool $isUpdate, array $expectedErrors): bool
{
    $validInput = [
        'firstName' => 'María José',
        'lastName' => 'Pérez-López',
        'identification' => '12345678',
        'gender' => 'F',
        'birthDate' => '1995-06-20',
        'password' => 'Segura123!',
        'active' => '1',
    ];
    $result = PersonValidator::validate(array_merge($validInput, $changes), $isUpdate);
    $actualErrors = array_keys($result['errors']);
    sort($actualErrors);
    sort($expectedErrors);
    $passed = $actualErrors === $expectedErrors;
    echo ($passed ? '[OK] ' : '[ERROR] ') . $description . PHP_EOL;
    if (!$passed) {
        echo '  Esperados: ' . json_encode($expectedErrors) . PHP_EOL;
        echo '  Obtenidos: ' . json_encode($actualErrors) . PHP_EOL;
    }
    return $passed;
}

$results = [];
$results[] = runValidationTest('Registro válido', [], false, []);
$results[] = runValidationTest('Nombre vacío', ['firstName' => ''], false, ['firstName']);
$results[] = runValidationTest('Apellido inválido', ['lastName' => '123'], false, ['lastName']);
$results[] = runValidationTest('Identificación no numérica', ['identification' => '12abc'], false, ['identification']);
$results[] = runValidationTest('Fecha futura', ['birthDate' => '2099-01-01'], false, ['birthDate']);
$results[] = runValidationTest('Fecha imposible', ['birthDate' => '2001-02-30'], false, ['birthDate']);
$results[] = runValidationTest('Género inválido', ['gender' => 'X'], false, ['gender']);
$results[] = runValidationTest('Contraseña demasiado corta', ['password' => 'abc'], false, ['password']);
$results[] = runValidationTest('Estado inválido', ['active' => 'other'], false, ['active']);
$results[] = runValidationTest('Edición sin cambiar contraseña', ['password' => ''], true, []);
$results[] = runValidationTest('Alta requiere contraseña', ['password' => ''], false, ['password']);
$results[] = runValidationTest('Límite de bytes en contraseña', ['password' => str_repeat('ñ', 40)], false, ['password']);
$passwordHash = password_hash('Segura123!', PASSWORD_BCRYPT);
$results[] = password_verify('Segura123!', $passwordHash) && $passwordHash !== 'Segura123!';
echo (end($results) ? '[OK] ' : '[ERROR] ') . 'Hash bcrypt verificable' . PHP_EOL;
$passedCount = count(array_filter($results));
echo "Resultado: {$passedCount}/" . count($results) . ' pruebas superadas.' . PHP_EOL;
exit($passedCount === count($results) ? 0 : 1);
