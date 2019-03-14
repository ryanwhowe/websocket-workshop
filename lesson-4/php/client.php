<?php

use Thruway\Authentication\ClientWampCraAuthenticator;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Message\ChallengeMessage;

require __DIR__.'/../vendor/autoload.php';

$user = 'steve';
$password = 'slightlymoresecret';

$onChallenge = function (ClientSession $session, $method, ChallengeMessage $msg) use ($user, $password){
	echo "Responding to challenge as user '$user' with password '$password'\n";
	if ("wampcra"!==$method){
		return false;
	}

	$cra = new ClientWampCraAuthenticator($user, $password);

	return $cra->getAuthenticateFromChallenge($msg)->getSignature();
};

$connection = new Connection([
	"realm" => 'lancashire',
	"url" => 'ws://localhost:8003/ws',
	"authmethods" => ["wampcra"],
	"onChallenge" => $onChallenge,
	"authid" => $user,
]);

$connection->getClient()->setAttemptRetry(false);
$connection->on('open', function (ClientSession $session){
	echo "Connection opened\n";
});

$connection->open();
