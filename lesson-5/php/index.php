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

	$user_id = $users[0]['_id'];
	$threads = $threads_db->where('title', '=', $title)->where('user', '=', $user_id)->fetch();

	if ($threads){
		return $response->withStatus(400)->withJson(['title' => "The thread title must be unique per user"]);
	}

	$thread_id = $threads_db->insert(['user' => $user_id, 'title' => $title])['_id'];

	return $response->withJson(['thread_id' => $thread_id]);
});

$app->run();
