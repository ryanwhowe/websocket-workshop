<?php

use SleekDB\SleekDB;

require __DIR__.'/../vendor/autoload.php';

function terminal_log(string $msg){
	$date = date('Y-m-d H:i:s').'.'.explode('.', round(microtime(true), 2))[1];

	echo "[$date] $msg\n";
}

function store_message(string $json){
	$data = json_decode($json);

	if (!$data){
		throw new Exception("The JSON could not be parsed or was empty");
	}

	$thread = $data->thread;
	$user = $data->user;
	$message = $data->message;

	if (!$user || !$thread || !$message){
		throw new Exception("Messages are expected to contain keys for 'thread', 'user' and 'message'");
	}

	$sleek = sleek_setup();

	/**
	 * @var SleekDB $threads_db
	 */
	$threads_db = $sleek['threads'];

	$threads = $threads_db->where('user', '=', $user)->where('permalink', '=', $thread)->limit(1)->fetch();

	if (!$threads){
		throw new Exception("The user '$user' does not have access to post messages to thread '$thread'");
	}

	/**
	 * @var SleekDB $messages_db
	 */
	$messages_db = $sleek['messages'];

	$messages_db->insert(['user' => $user, 'thread' => $thread, 'message' => $message, 'date' => date('Y-m-d H:i:s')]);
}

/**
 * @return SleekDB[]
 * @throws Exception
 */
function sleek_setup(){
	$tables = ['users', 'threads', 'messages'];
	$db = [];
	foreach ($tables as $table){
		$db[$table] = SleekDB::store($table, __DIR__.'/../db');
	}

	return $db;
}

date_default_timezone_set(getenv('TZ'));

$loop = \React\EventLoop\Factory::create();

$loop->addTimer(1, function (){
	terminal_log("Loop was started, will report that it is still running every 5 minutes");
});

$loop->addPeriodicTimer(300, function (){
	terminal_log("Still running, next update in 5 minutes...");
});

$port = getenv('ZMQ_PORT');
terminal_log("Will bind to port: $port");

$context = new \React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind("tcp://0.0.0.0:$port");

$pull->on('message', function ($json){
	terminal_log("Got a JSON message: $json");

	try {
		store_message($json);
		terminal_log("Saved message to database");
	}
	catch (Exception $e){
		terminal_log("Storage error: {$e->getMessage()}");
	}
});

$loop->run();
