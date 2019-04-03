<?php
/**
 * Copy of the authenticator with a few changes
 */

use Thruway\ClientSession;

require __DIR__.'/../vendor/autoload.php';

define('LOG_NAME', 'Pusher');

require 'utilities.php';
require 'connection.php';

$loop = \React\EventLoop\Factory::create();

$loop->addPeriodicTimer(30, function (){
	terminal_log("Still running, next update in 5 minutes...");
});

start_connection($argv, function (ClientSession $session, $transport, $details) use ($loop){
	terminal_log("Connection opened with role '{$details->authrole}'");

	$context = new \React\ZMQ\Context($loop);
	$pull = $context->getSocket(ZMQ::SOCKET_PULL);

	$bind_addr = "tcp://0.0.0.0:".getenv('ZMQ_PUSH_PORT');

	terminal_log("Listening on $bind_addr");

	$pull->bind($bind_addr);

	// When we receive a message we then relay it out to users
	$pull->on('message', function ($message) use ($session){
		terminal_log("Received message '$message'; will now broadcast it");

		$session->publish('phpyork.broadcast', [$message]);
	});
}, $loop);
