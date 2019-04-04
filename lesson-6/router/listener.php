<?php
use Thruway\CallResult;
use Thruway\ClientSession;

function subscribe_subs_create(ClientSession $session){
	subscribe($session, 'wamp.subscription.on_create', function ($args) use ($session){
		$subscriber_session_id = array_shift($args);

		if ($subscriber_session_id==$session->getSessionId()){
			// Avoids us catching any subscriptions we do ourselves
			return;
		}

		$details = array_shift($args);
		$topic = $details->uri;
		$topic_id = $details->id;

		// Added this as during this lesson users will subscribe to
		// other named threads
		if (strpos($topic, 'phpyork.chat.')!==0){
			return;
		}

		$user = redis_get("session-$subscriber_session_id");

		terminal_log("A user $user ($subscriber_session_id) caused a topic to be created: '{$topic}' ({$topic_id})");

		redis_set("topic-$topic_id", $topic);
		redis_set_array($topic, []);

		subscribe_user_topic($session, $topic);
	});
}

function subscribe_user_topic(ClientSession $session, string $topic){
	subscribe($session, $topic, function ($args, $kwargs, $details) use ($topic){
		$message = $args[0];
		$user = $details->publisher_authid;
		terminal_log("We snooped on a message from '{$user}' to topic '$topic' that said: '{$message}'");

		$thread = str_replace('phpyork.chat.', '', $topic);
		$send_data = [
			'user' => $details->publisher_authid,
			'thread' => $thread,
			'message' => $args[0],
		];

		store_message($send_data);
	});
}

function store_message(array $send_data){
	$url = "http://app_5/message";
	try {
		http_post($url, $send_data);

		terminal_log("Saved message via HTTP");
	}
	catch (Exception $e) {
		terminal_log("Error saving via HTTP: {$e->getMessage()}");
	}
}

function subscribe_subs_sub(ClientSession $session){
	subscribe($session, 'wamp.subscription.on_subscribe', function ($args) use ($session){
		$subscriber_session_id = array_shift($args);
		if ($subscriber_session_id==$session->getSessionId()){
			// Avoids us catching any subscriptions we do ourselves
			return;
		}

		$topic_id = array_shift($args);
		$topic = redis_get("topic-$topic_id");

		// Helps when subscriptions to other threads occur
		// but also in general no point continuing here if no $topic
		if (!$topic){
			return;
		}

		$user = redis_get("session-$subscriber_session_id");

		terminal_log("A user $user ($subscriber_session_id) subscribed to a topic: '{$topic}' ($topic_id)");

		redis_add_to_array($topic ?? '', $user);
	});
}

function subscribe_subs_unsub(ClientSession $session){
	subscribe($session, 'wamp.subscription.on_unsubscribe', function ($args) use ($session){
		$topic_id = $args[1];

		terminal_log("A user unsubscribed from topic $topic_id");

		call_list_subscribers($session, $topic_id);
	});
}

function call_list_subscribers(ClientSession $session, string $topic_id){
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
}

function register_get_subscribers(ClientSession $session){
	register($session, 'phpyork.subscribers', function($args){
		$topic = $args[0];
		if (!$topic){
			terminal_log("No topic supplied to check users");
			return '[]';
		}

		if (strpos($topic, "phpyork.chat.")!==0){
			$topic = "phpyork.chat.$topic";
		}

		$users = redis_get($topic);

		terminal_log("Returning list of users for URI '$topic': $users");

		return $users;
	});
}
