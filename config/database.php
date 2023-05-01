<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-01-2023
 * Time: 07:55
 */
return[
    'default' => 'config_db',
    'connections' => [
        'config_db' => [
            'driver' => 'mysql',
            'host' => 't2slive-rds.c2j0o56fpven.ap-south-1.rds.amazonaws.com',
            'database' => 'liteqa_t2s',
            'username' => 't2sliteqaeditor',
            'password' => 'uKxLH8nzq8RlHSc9g1',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ],
        'school_db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => '',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
	]
    ]
];
