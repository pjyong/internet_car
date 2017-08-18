<?php
define( 'SRC_PATH', __DIR__ . '/' );
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
			'host' => '101.200.47.51',
			'user' => 'root',
			'password' => 'KeYpZrZx',
			'driver' => 'pdo_mysql',
			'port' => 3306,
			'dbname' => 'yu',
		),
        'wechat' => array(
            'token' => 'fdasdfas323232',
            'appid' => 'wx2c5bd898ceab87b7',
            'appsecret' => 'd4624c36b6795d1d99dcf0547af5443d',
            'debug' => true,
            'logcallback' => 'logInfo'
        ),
        'qcloud' => array(
            'SecretId'       => '你的secretId',
            'SecretKey'      => '你的secretKey',
            'RequestMethod'  => 'GET',
            'DefaultRegion'  => '区域参数'
        ),
    ],
];
