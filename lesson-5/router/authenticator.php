<?php
/**
 * We won't actually use this in lesson 4; it's here to crib stuff off if
 * the work for lesson 3 breaks... I reserve the right to delete it in a future
 * commit, but those who know git always have a way!
 */

use Psr\Log\NullLogger;
use Thruway\Authentication\ClientWampCraAuthenticator;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Logging\Logger;
use Thruway\Message\ChallengeMessage;

require __DIR__.'/../vendor/autoload.php';

function terminal_log(string $msg){
	echo "(Authenticator) $msg";
}

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

	throw new Exception("No user found with name '$name'");
}

Logger::set(new NullLogger());

list ($url, $realm, $user, $password) = array_slice($argv, 1);

$on_challenge = function (ClientSession $session, $method, ChallengeMessage $msg) use ($user, $password){
	if ("wampcra"!==$method){
		return false;
	}

	$cra = new ClientWampCraAuthenticator($user, $password);

	return $cra->getAuthenticateFromChallenge($msg)->getSignature();
};

$connection = new Connection([
	"realm" => $realm,
	"url" => $url,
	"authmethods" => ["wampcra"],
	"onChallenge" => $on_challenge,
	"authid" => $user,
]);

$connection->on('open', function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");

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
		catch (Exception $e){
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
			'secret' => $token
		];
	})->then(function () use ($name){
		terminal_log("I registered procedure '$name'");
	});

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
});

$connection->on('close', function(){
	terminal_log("Connection closed, will keep trying to reconnect");
});

$connection->open();
