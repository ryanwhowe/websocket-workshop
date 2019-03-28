<?php
use Thruway\ClientSession;

/**
 * @param string $name
 * @return array [string $token, string $role]
 * @throws Exception
 */
function token_from_user(string $name){
	switch ($name){
		case 'edmund':
			return ['langley', 'king'];
		case 'edward':
			return ['norwich', 'prince'];
	}

	$url = "http://app_4/auth?".http_build_query(['name' => $name]);

	$token = http_get($url);
	$token = trim($token);

	if ($token){
		return [$token, 'serf'];
	}

	throw new Exception("No user found with name '$name'");
}

function register_auth(ClientSession $session){
	$name = 'phpyork.auth';
	$session->register($name, function ($args){
		$realm = array_shift($args);
		$authid = array_shift($args);
		$details = array_shift($args);
		$session_id = $details->session;

		terminal_log("Received auth request for user '$authid' on realm '$realm'. Crossbar session was '$session_id'");

		try {
			list($token, $role) = token_from_user($authid);
		}
		catch (Exception $e) {
			terminal_log("Error: {$e->getMessage()}");

			return [
				'role' => 'banished',
				'secret' => '',
				'disclose' => true,
			];
		}

		terminal_log("\tReturning token '$token'");

		return [
			'role' => $role,
			'secret' => $token,
		];
	})->then(function () use ($name){
		terminal_log("I registered procedure '$name'");
	});
}

function register_permissions(ClientSession $session){
	$name = 'phpyork.permissions';
	$session->register($name, function ($args){
		$details = array_shift($args);
		$uri = array_shift($args);
		$action = array_shift($args);

		terminal_log("User {$details->authid} ({$details->session}) wants to $action on endpoint: $uri");

		if ($action==='publish' && strpos($uri, "phpyork.chat")===0){
			return ['allow' => true, 'disclose' => true, 'cache' => true];
		}

		if ($action==='subscribe' && strpos($uri, "phpyork.chat")===0){
			return ['allow' => true, 'cache' => true];
		}

		return false;
	})->then(function () use ($name){
		terminal_log("I registered procedure '$name'");
	});
}
