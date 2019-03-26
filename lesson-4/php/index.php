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
 *  Hit it from a browser or with `curl http://localhost:8082/hello/yorkshire`
 */
$app->get('/hello/{name}', function (Request $request, Response $response, $args){
	return $response->getBody()->write("Hello, ".$args['name']."\n");
});

$app->get('/auth', function (Request $request, Response $response, $args){
	$port = $request->getUri()->getPort();
	// Port must be internal, prevents external snooping for tokens
	// Empty port will mean port 80 or 443 (i.e. defaults) so don't expose those
	// You can do better security with keys or IP blocks
	if ($port!==80 && !empty($port)){
		return $response->withStatus(403)->getBody()->write("Forbidden");
	}

	$name = $request->getQueryParam('name');

	/**
	 * @var SleekDB $users_db
	 */
	$users_db = $this->sleek->users;

	$users = $users_db->where('name', '=', $name)->limit(1)->fetch();

	if (empty($users)){
		return $response->withStatus(404)->write('Not found');
	}

	return $response->getBody()->write($users[0]['token']);
});

/* This route lets you add a user. The easiest way is using curl:
 *   curl -X POST -d '{"name": "my user"}' -H 'content-type: application/json' localhost:8082/user
 * You will receive a token in response
 */
$app->post('/user', function (Request $request, Response $response){
	$name = $request->getParsedBodyParam('name');

	if (empty($name)){
		return $response->withStatus(400)->withJson(['error' => "You must submit a 'name' field to create a user"]);
	}
	/**
	 * @var SleekDB $users_db
	 */
	$users_db = $this->sleek->users;

	$users = $users_db->where('name', '=', $name)->fetch();

	if ($users){
		return $response->withStatus(400)->withJson(['error' => "The name '$name' is already taken"]);
	}

	$token = random_bytes(6);
	$token = bin2hex($token);

	$users_db->insert(['name' => $name, 'token' => $token]);

	return $response->withJson(['token' => $token]);
});

$app->run();
