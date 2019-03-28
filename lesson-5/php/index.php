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
	$tables = ['users', 'threads'];
	$db = [];
	foreach ($tables as $table){
		$db[$table] = SleekDB::store($table, __DIR__.'/../db');
	}

	return (object) $db;
};

/* This route is just to test the web server works
 *  Hit it from a browser or with `curl http://localhost:8015/lesson-5/yorkshire`
 */
$app->get('/lesson-5/{name}', function (Request $request, Response $response, $args){
	return $response->getBody()->write("Hello, ".$args['name']."\n");
});

/* This route lets you add a user. The easiest way is using curl:
 *   curl -X POST -d '{"name": "my user", "title": ""}' -H 'content-type: application/json' localhost:8015/thread
 * You will receive a password & token in response
 */
$app->post('/thread', function (Request $request, Response $response){
	$name = $request->getParsedBodyParam('name');
	$title = $request->getParsedBodyParam('title');

	$errors = [];
	if (empty($name)){
		$errors['name'] = "You must submit a 'name' field matching a user to create a thread";
	}
	if (empty($title)){
		$errors['title'] = "You must submit a 'title' field to create a thread";
	}
	if (!empty($errors)){
		return $response->withStatus(400)->withJson($errors);
	}
	/**
	 * @var SleekDB $users_db
	 */
	$users_db = $this->sleek->users;

	$users = $users_db->where('name', '=', $name)->fetch();

	if (!$users){
		return $response->withStatus(404)->withJson(['error' => "The user with name '$name' does not exist"]);
	}

	/**
	 * @var SleekDB $threads_db
	 */
	$threads_db = $this->sleek->threads;

	$permalink = trim($title);
	$permalink = strtolower($permalink);
	$permalink = preg_replace('|([^a-z0-9-])|', '-', $permalink);
	$threads = $threads_db->where('permalink', '=', $permalink)->where('user', '=', $name)->fetch();

	if ($threads){
		return $response->withStatus(400)->withJson(['title' => "The thread permalink ($permalink) must be unique per user"]);
	}

	$threads_db->insert(['user' => $name, 'title' => $title, 'permalink' => $permalink])['_id'];

	return $response->withJson(['uri' => "phpyork.chat.$permalink"]);
});

/*
 * This will be called by the authenticator but will not be accessible to curl due
 * to the test check below
 */
$app->get('/access', function (Request $request, Response $response, $args){
	$port = $request->getUri()->getPort();
	// Port must be internal, prevents external snooping for tokens
	// Empty port will mean port 80 or 443 (i.e. defaults) so don't expose those
	// You can do better security with keys or IP blocks
	if ($port!==80 && !empty($port)){
		return $response->withStatus(403)->getBody()->write("Forbidden");
	}

	$permalink = $request->getQueryParam('thread');
	$user = $request->getQueryParam('user');

	if (!$user || !$permalink){
		return $response->withStatus(400)->write("You must enter a 'thread' and 'name' query string");
	}

	/**
	 * @var SleekDB $threads_db
	 */
	$threads_db = $this->sleek->threads;

	$threads = $threads_db->where('user', '=', $user)->where('permalink', '=', $permalink)->limit(1)->fetch();

	if (!$threads){
		return $response->withStatus(401)->write("No match for supplied user and thread");
	}

	return $response->write($threads[0]['permalink']);
});

$app->run();
