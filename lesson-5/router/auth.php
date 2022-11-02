<?php
use Thruway\ClientSession;

/**
 * @return array{string, string}
 * @throws Exception
 */
function token_from_user(string $name) :array{
	switch ($name){
		case 'alice':
			return ['changeme', 'basic-user'];
		case 'bob':
			return ['password123', 'permissions-user'];
	}

	$url = "http://app_4/auth?".http_build_query(['name' => $name]);

	$token = http_get($url);

	if ($token){
		return [$token, 'app-user'];
	}

	throw new Exception("No user found with name '$name'");
}

function register_auth(ClientSession $session) :void{
	$name = 'workshop.auth';
	register($session, $name, function (array $args) :array{
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
	register($session, $name, function (array $args){
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
