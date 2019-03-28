<?php
/**
 * All the work from lesson 4 with a few extras added to get us up to speed for session 5
 * Have moved a few items into functions to keep stuff clean
 */

use Thruway\ClientSession;

require __DIR__.'/../vendor/autoload.php';

require 'utilities.php';
require 'connection.php';
require 'auth.php';

start_connection($argv, function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");

	register_auth($session);
	register_permissions($session);

	subscribe($session, 'wamp.subscription.on_create', function($args) use ($session){
		$subscriber_session_id = array_shift($args);

		if ($subscriber_session_id==$session->getSessionId()){
			// Avoids us catching any subscriptions we do ourselves
			return;
		}

		$details = array_shift($args);
		$topic = $details->uri;
		terminal_log("A user ($subscriber_session_id) subscribed to a topic: '{$topic}'");

		subscribe($session, $topic, function($args, $kwargs, $details) use ($topic){
			terminal_log("We snooped on a message from '{$details->publisher_authid}' to topic '$topic' that said: '{$args[0]}'");
		});
	});
});
