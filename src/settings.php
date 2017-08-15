<?php
define( 'TEMPLATE_PATH', __DIR__ . '/../templates/' );
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => TEMPLATE_PATH,
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'db'=>array(
			'host' => '192.168.31.234',
			'user' => 'root',
			'password' => 'cheyoo',
			'driver' => 'pdo_mysql',
			'port' => 3307,
			'dbname' => 'yu',
		),
    ],
];
