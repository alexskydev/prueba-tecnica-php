<?php
/**
 * Configuración predeterminada para la entrega (MySQL: 3306).
 * En una instalación local con otro puerto (por ejemplo 3307), modifíquelo solo en su copia.
 */
return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'NombreDB_Narvaez',
        'user' => 'root',
        'password' => '',
    ],
    'app' => [
        'timezone' => 'America/Bogota',
    ],
];
