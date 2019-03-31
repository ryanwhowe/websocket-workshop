<?php
/**
 * All the work from lesson 4 with a few extras added to get us up to speed for session 5
 * Have moved a few items into functions to keep stuff clean
 */

use Thruway\CallResult;
use Thruway\ClientSession;

require __DIR__.'/../vendor/autoload.php';

require 'utilities.php';
require 'connection.php';
require 'auth.php';
require 'storage.php';

start_connection($argv, function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");

	register_auth($session);
	register_permissions($session);

	subscribe($session, 'wamp.subscription.on_create', function ($args) use ($session){
		$subscriber_session_id = array_shift($args);

		if ($subscriber_session_id==$session->getSessionId()){
			// Avoids us catching any subscriptions we do ourselves
			return;
		}

		$details = array_shift($args);
		$topic = $details->uri;
		$topic_id = $details->id;

		$user = redis_get("session-$subscriber_session_id");

		terminal_log("A user $user ($subscriber_session_id) caused a topic to be created: '{$topic}' ({$topic_id})");

		redis_set("topic-$topic_id", $topic);

		subscribe($session, $topic, function ($args, $kwargs, $details) use ($topic){
			$message = $args[0];
			$user = $details->publisher_authid;
			terminal_log("We snooped on a message from '{$user}' to topic '$topic' that said: '{$message}'");

			$thread = str_replace('phpyork.chat.', '', $topic);
			$url = "http://app_5/message";
			try {
				http_post($url, [
					'user' => $details->publisher_authid,
					'thread' => $thread,
					'message' => $args[0],
				]);
			}
			catch (Exception $e) {
				terminal_log("Error: {$e->getMessage()}");

				return;
			}

			terminal_log("Saved message via HTTP");
		});
	});

	subscribe($session, 'wamp.subscription.on_subscribe', function ($args) use ($session){
		$subscriber_session_id = array_shift($args);
		if ($subscriber_session_id==$session->getSessionId()){
			// Avoids us catching any subscriptions we do ourselves
			return;
		}

		$topic_id = array_shift($args);
		$topic = redis_get("topic-$topic_id");

		$user = redis_get("session-$subscriber_session_id");

		terminal_log("A user $user ($subscriber_session_id) subscribed to a topic: '{$topic}' ($topic_id)");

		redis_add_to_array($topic ?? '', $user);
	});

	subscribe($session, 'wamp.subscription.on_unsubscribe', function ($args) use ($session){
		$topic_id = $args[1];

		terminal_log("A user unsubscribed from topic $topic_id");

		call($session, 'wamp.subscription.list_subscribers', [$topic_id], function (CallResult $result) use ($session, $topic_id){
			$sessions = $result->getResultMessage()->getArguments();

			$session_ids = array_shift($sessions);
			$topic_users = [];
			foreach ($session_ids as $session_id){
				$user = redis_get("session-$session_id");
				if ($user){
					$topic_users[] = $user;
				}
			}

			$topic = redis_get("topic-$topic_id");

			terminal_log("Updating subscribers for $topic to: ".implode(', ', $topic_users));

			redis_set_array($topic, $topic_users);
		});
	});
});
