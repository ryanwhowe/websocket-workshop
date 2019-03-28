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

	if ($token){
		return [$token, 'serf'];
	}

	throw new Exception("No user found with name '$name'");
}

function register_auth(ClientSession $session){
	$name = 'phpyork.auth';
	register($session, $name, function ($args){
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
	});
}

function register_permissions(ClientSession $session){
	$name = 'phpyork.permissions';
	register($session, $name, function ($args){
		$details = array_shift($args);
		$uri = array_shift($args);
		$action = array_shift($args);

		$user = $details->authid;
		terminal_log("User {$user} ({$details->session}) wants to $action on endpoint: $uri");

		if (in_array($action, ['call', 'register'])){
			return false;
		}

		$thread = str_replace('phpyork.chat.', '', $uri);
		if (!$thread){
			terminal_log("No thread name found");
			return false;
		}

		$url = "http://app_5/access?".http_build_query(['thread' => $thread, 'user' => $user]);

		try {
			http_get($url);
		}
		catch (Exception $e) {
			terminal_log("Error: {$e->getMessage()}");

			return false;
		}

		return ['allow' => true, 'disclose' => true, 'cache' => true];
	});
}
