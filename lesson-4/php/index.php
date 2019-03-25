<?php

use SleekDB\SleekDB;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__.'/../vendor/autoload.php';

$config = [
	'settings' => [
		'displayErrorDetails' => true,
		'logger' => [
			'name' => 'slim-app',
			'level' => 'debug',
			'path' => __DIR__ . '/logs/app.log',
		],
	],
];
$app = new App($config);

$container = $app->getContainer();
$container['sleek'] = function($container){
	$users = SleekDB::store('users', __DIR__.'/db');

	return (object) ['users' => $users];
};

$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
	return $response->getBody()->write("Hello, " . $args['name']);
});

$app->get('/auth/{user}', function (Request $request, Response $response, $args) {


	return $response->getBody()->write("Hello, " . $args['name']);
});

$app->post('/auth/user', function(Request $request, Response $response){
	$name = $request->getParsedBodyParam('name');

	if (empty($name)){
		$response->withStatus(400)->withJson(['error' => "You must submit a 'name' field to create a user"]);
	}

	$token = random_bytes(6);
	$token = bin2hex($token);

	$this->sleek->users->insert(['name' => $name, 'token' => $token]);

	return $response->withJson(['token' => $token]);
});

$app->run();
