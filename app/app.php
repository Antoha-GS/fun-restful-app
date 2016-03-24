<?php
/**
 * @var \Silex\Application $app
 */

use App\Controllers\PostController;
use Components\FileUploader;
use Neutron\Silex\Provider\MongoDBODMServiceProvider;
use Silex\Application;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

// converting errors to exceptions
ErrorHandler::register();
ExceptionHandler::register($app['debug']);

// handling CORS preflight request
$app->before(function (Request $request) {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->setStatusCode(Response::HTTP_OK);
        return $response->send();
    }
    return null;
}, Application::EARLY_EVENT);

// handling CORS respons with right headers
$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,DELETE,OPTIONS');
});

// error handler
$app->error(function (\Exception $e, $code) use ($app) {
    $app['monolog']->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

    if ($app['debug']) {
        return null;
    }

    if ($e instanceof HttpException) {
        $message = $e->getMessage();
    } else {
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = Response::$statusTexts[$code];
    }

    return new JsonResponse(['message' => $message], $code);
});

// register providers and services
$app->register(new MonologServiceProvider(), $app['config.monolog']);
$app->register(new MongoDBODMServiceProvider(), $app['config.doctrine']);
$app->register(new ServiceControllerServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());

$app['services.file_uploader_factory'] = $app->protect(function (array $config) {
    return new FileUploader(
        $config['root_path'],
        $config['path'],
        isset($config['file_name_generator']) ? $config['file_name_generator'] : null
    );
});

$app['controllers.post'] = $app->share(function (Application $app) {
    return new PostController($app);
});

include APP_PATH . '/routing.php';

return $app;