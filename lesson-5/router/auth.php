<?php
use Thruway\ClientSession;

/**
 * @param string $name
 * @return array [string $token, string $role]
 * @throws Exception
 */
function token_from_user(string $name){
	switch ($name){
		case 'alice':
			return ['changeme', 'type1'];
		case 'bob':
			return ['password123', 'type2'];
	}

	$url = "http://app_4/auth?".http_build_query(['name' => $name]);

	$token = http_get($url);

	if ($token){
		return [$token, 'type3'];
	}

	throw new Exception("No user found with name '$name'");
}

function register_auth(ClientSession $session){
	$name = 'workshop.auth';
	register($session, $name, function ($args){
		$realm = array_shift($args);
		$authid = array_shift($args);
		$details = array_shift($args);
		$session_id = $details->session;

		terminal_log("Received auth request for user '$authid' on realm '$realm'. Crossbar session was '$session_id'");

		try {
			[$token, $role] = token_from_user($authid);
		}
		catch (Exception $e) {
			terminal_log("Error: {$e->getMessage()}");

			return [
				'role' => 'blocked',
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
	$name = 'workshop.permissions';
	register($session, $name, function ($args){
		$details = array_shift($args);
		$uri = array_shift($args);
		$action = array_shift($args);

		terminal_log("User {$details->authid} ({$details->session}) wants to $action on endpoint: $uri");

		if ($action==='publish' && strpos($uri, "workshop.chat")===0){
			return ['allow' => true, 'disclose' => true, 'cache' => true];
		}

		if ($action==='subscribe' && strpos($uri, "workshop.chat")===0){
			return ['allow' => true, 'cache' => true];
		}

		return false;
	});
}
