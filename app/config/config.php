<?php
/**
 * @var \Silex\Application $app
 */

$app['app.name'] = 'reast_api_fun';

$app['api.endpoint'] = '/api';
$app['api.version'] = 'v1';

$app['config.upload'] = [
    'root_path' => WEB_PATH,
    'path' => '/uploads/{author}',
    'file_name_generator' => new \Components\FileNameGenerator\UniqueFileNameGenerator('image_'),
];

$app['config.monolog'] = [
    'monolog.logfile' => VAR_PATH . '/logs/default.log',
    'monolog.bubble' => true,
    'monolog.level' => \Psr\Log\LogLevel::DEBUG,
    'monolog.name' => $app['app.name'],
];

$app['config.doctrine'] = [

    // connection settings
    'doctrine.odm.mongodb.connection_options' => [
        'database' => null,
        'host'     => null,
        'options'  => ['fsync' => false, 'connect' => true],
    ],

    // register the differents documents namespace
    'doctrine.odm.mongodb.documents' => [
        [
            'type' => 'annotation',
            'path' => [
                'src/App/Documents',
            ],
            'namespace' => 'App\Documents',
            'alias'     => 'docs',
        ],
    ],

    'doctrine.odm.mongodb.proxies_dir'             => VAR_PATH . '/cache/DoctrineMongodbProxies',
    'doctrine.odm.mongodb.proxies_namespace'       => 'Doctrine\ODM\MongoDB\Proxies',
    'doctrine.odm.mongodb.auto_generate_proxies'   => true,
    'doctrine.odm.mongodb.hydrators_dir'           => VAR_PATH . '/cache/DoctrineMongodbHydrators',
    'doctrine.odm.mongodb.hydrators_namespace'     => 'Doctrine\ODM\MongoDB\Hydrators',
    'doctrine.odm.mongodb.auto_generate_hydrators' => true,
    'doctrine.odm.mongodb.metadata_cache'          => new \Doctrine\Common\Cache\ArrayCache(),
    'doctrine.odm.mongodb.logger_callable'         => $app->protect(function($query) {
        // log your query
    }),
];
