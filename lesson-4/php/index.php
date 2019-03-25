<?php

use Psr\Http\Message\RequestInterface;
use Slim\App;
use Slim\Http\Response;

require __DIR__.'/../vendor/autoload.php';

$app = new App();

$app->get('/hello/{name}', function (RequestInterface $request, Response $response, $args) {
	return $response->getBody()->write("Hello, " . $args['name']);
});

$app->run();
