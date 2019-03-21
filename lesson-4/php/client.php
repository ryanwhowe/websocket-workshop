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
	$date = date('Y-m-d H:i:s').'.'.explode('.', round(microtime(true), 2))[1];

	echo "[$date] $msg\n";
}

Logger::set(new NullLogger());

define('USER', 'alan');
define('PASSWORD', 'definitelysecret');

$onChallenge = function (ClientSession $session, $method, ChallengeMessage $msg){
	$user = USER;
	$password = PASSWORD;
	terminal_log("Responding to challenge as user '$user' with password '$password'");
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
	"authid" => USER,
]);

$connection->on('open', function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");

	$name = 'add';
	$session->register($name, function ($args){
		terminal_log("Procedure says: Received parameters '".implode(', ', $args)."'");

		$answer = array_sum($args);
		terminal_log("\tSending the answer '$answer'...");

		return $answer;
	})->then(function () use ($name){
		terminal_log("Procedure says: I registered procedure '$name'");
	});

	$topic = 'test';
	$session->subscribe($topic, function ($args) use ($session, $topic){
		terminal_log("Subscriber says: Received message '".implode(' ', $args)."'");
		terminal_log("\tSending one back...");
		$session->publish($topic, ['I am a robot'], null, ['acknowledge' => true])->then(function (){
			terminal_log("Publisher says: Sent, did you get it?");
		});
	})->then(function () use ($topic){
		terminal_log("Subscriber says: I subscribed to topic '$topic'");
	});
});

$connection->on('close', function(){
	terminal_log("Connection closed, will keep trying to reconnect");
});

$connection->open();
