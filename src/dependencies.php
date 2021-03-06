<?php
// DIC configuration
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// 将DB注入进来
$container['db'] = function ($c) {
    $settings = $c->get('settings')['db'];
    $config = new Configuration();
    return DriverManager::getConnection( $settings, $config );
};

// 404
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['renderer']->render($response, 'error.phtml');
    };
};
