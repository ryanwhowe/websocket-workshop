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

date_default_timezone_set(getenv('TZ'));

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
 *  Hit it from a browser or with `curl http://localhost:8014/lesson-4/yorkshire`
 */
$app->get('/lesson-4/{name}', function (Request $request, Response $response, $args){
	return $response->getBody()->write("Hello, ".$args['name']."\n");
});

/* This route lets you add a user. The easiest way is using curl:
 *   curl -X POST -d '{"name": "my user"}' -H 'content-type: application/json' localhost:8014/user
 * You will receive a password & token in response
 */
$app->post('/user', function (Request $request, Response $response){
	$name = $request->getParsedBodyParam('name');

	if (empty($name)){
		return $response->withStatus(400)->withJson(['name' => "You must submit a 'name' field to create a user"]);
	}
	/**
	 * @var SleekDB $users_db
	 */
	$users_db = $this->sleek->users;

	$users = $users_db->where('name', '=', $name)->fetch();

	if ($users){
		return $response->withStatus(400)->withJson(['name' => "The name '$name' is already taken"]);
	}

	$token = make_secret();
	$password = make_secret();

	$users_db->insert(['name' => $name, 'password' => $password, 'token' => $token]);

	return $response->withJson(['password' => $password, 'token' => $token]);
});

/* This route will be used by the front end to "sign in" a user and return a token
 *   curl -X POST -d '{"name": "my user", "password": "****"}' -H 'content-type: application/json' localhost:8014/login
 * You will receive a token
 */
$app->post('/login', function (Request $request, Response $response, $args){
	$name = $request->getParsedBodyParam('name');
	$password = $request->getParsedBodyParam('password');

	/**
	 * @var SleekDB $users_db
	 */
	$users_db = $this->sleek->users;

	$users = $users_db->where('name', '=', $name)->limit(1)->fetch();

	$response = cors_response($response);
	if (empty($users)){
		return $response->withStatus(404)->withJson(['error' => "The user with name '$name' does not exist"]);
	}

	if ($password!=$users[0]['password']){
		return $response->withStatus(403)->withJson(['error' => "Wrong password"]);
	}

	return $response->withJson(['token' => $users[0]['token']]);
});

$app->options('/login', function (Request $request, Response $response, $args){
	return cors_response($response);
});

/*
 * This will be called by the authenticator but will not be accessible to curl due
 * to the test check below
 */
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

	return $response->write($users[0]['token']);
});

$app->run();
