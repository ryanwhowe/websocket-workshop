<?php

use SleekDB\SleekDB;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__.'/../vendor/autoload.php';

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

/* This route is just to test the web server works
 *  Hit it from a browser or with `curl http://localhost:8016/lesson-6/yorkshire`
 */
$app->get('/lesson-6/{name}', function (Request $request, Response $response, $args){
	return $response->getBody()->write("Hello, ".$args['name']."\n");
});

/* This route lets you broadcast to all users. The easiest way is using curl:
 *   curl -X POST -d '{"password": "abc123", "message": "Test broadcast"}' -H 'content-type: application/json' localhost:8016/broadcast
 * All users signed in will receive the message
 */
$app->post('/broadcast', function (Request $request, Response $response){

	$admin_password = $request->getParsedBodyParam('password');
	$message = $request->getParsedBodyParam('message');

	if (!$admin_password || !$message){
		return $response->withStatus(400)->write("Must supply a password in the request body");
	}

	if ($admin_password!==getenv('ADMIN_PASSWORD')){
		return $response->withStatus(403)->write("Wrong password");
	}

	$port = getenv('ZMQ_PUSH_PORT');
	$dsn = "tcp://crossbar_6a:$port";
	try {
	$context = new ZMQContext();
	$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'persist socket');
	$socket->connect($dsn);
	$socket->send($message, ZMQ::MODE_DONTWAIT);
	}
	catch (Exception $e) {
		return $response->withStatus(500)->write("ZMQ Error ($dsn): {$e->getMessage()}");
	}

	return $response->withStatus(200)->write("Wrote to $dsn");
});

$app->run();
