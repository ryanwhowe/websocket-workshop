<?php

use Thruway\Authentication\ClientWampCraAuthenticator;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Message\ChallengeMessage;

/**
 * @param array $cmd_args
 * @param callable $on_open
 * @throws Exception
 */
function start_connection(array $cmd_args, callable $on_open) :void{
	[$url, $realm, $user, $password] = array_slice($cmd_args, 1);

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

	$connection->on('open', $on_open);

	$connection->on('close', function (){
		terminal_log("Connection closed, will keep trying to reconnect");
	});

	$connection->open();
}
