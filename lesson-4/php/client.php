<?php

use Psr\Log\NullLogger;
use Thruway\Authentication\ClientWampCraAuthenticator;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Logging\Logger;
use Thruway\Message\ChallengeMessage;

require __DIR__.'/../vendor/autoload.php';

Logger::set(new NullLogger());

$user = 'alan';
$password = 'definitelysecret';

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
$connection->on('open', function (ClientSession $session, $details){
	echo "Connection opened with role {$details->authrole}\n";

	$topic = 'test';
	$session->subscribe($topic, function ($args) use ($session, $topic){
		echo "Subscriber says: Received message '".implode(' ', $args)."'\n";
		echo "\tSending one back...\n";
		$session->publish($topic, ['I am a robot'], null, ['acknowledge' => true])->then(function (){
			echo "Publisher says: Sent, did you get it?\n";
		});
	})->then(function () use ($topic){
		echo "Subscriber says: I subscribed to topic '$topic'\n";
	});
});

$connection->open();
