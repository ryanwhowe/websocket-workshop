<?php

use SleekDB\SleekDB;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__.'/../vendor/autoload.php';

function make_secret(){
	$secret = random_bytes(6);

	return bin2hex($secret);
}

function cors_response(Response $response){
	return $response->withHeader('Access-Control-Allow-Origin', '*')
		->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
		->withHeader('Access-Control-Allow-Headers', 'Content-Type');
}

$config = [
	'settings' => [
		'displayErrorDetails' => true,
		'logger' => [
			'name' => 'slim-app',
			'level' => 200,
			'path' => __DIR__.'/logs/app.log',
		],
	],
];
$app = new App($config);

$container = $app->getContainer();
$container['sleek'] = function ($container){
	$users = SleekDB::store('users', __DIR__.'/../db');

	return (object)['users' => $users];
};

/* This route is just to test the web server works
 *  Hit it from a browser or with `curl http://localhost:8015/lesson-5/yorkshire`
 */
$app->get('/lesson-5/{name}', function (Request $request, Response $response, $args){
	return $response->getBody()->write("Hello, ".$args['name']."\n");
});

$app->run();
