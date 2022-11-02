<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\NullLogger;
use Thruway\Authentication\ClientWampCraAuthenticator;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Logging\Logger;
use Thruway\Message\ChallengeMessage;

require __DIR__.'/../vendor/autoload.php';

function terminal_log(string $msg){
	echo "(Authenticator) $msg ";
}

function http_get(string $url){
	$client = new Client();

	try {
		$response = $client->get($url);
	}
	catch (RequestException $e) {
		$response = $e->getResponse();
		throw new Exception("Unexpected response status: {$response->getStatusCode()}\n\t{$response->getBody()}");
	}

	$text = (string) $response->getBody();

	return trim($text);
}

Logger::set(new NullLogger());

// You need to add a line here to grab command line creds

$on_challenge = function (ClientSession $session, $method, ChallengeMessage $msg) use ($user, $password){
	if ("wampcra"!==$method){
		return false;
	}

	$cra = new ClientWampCraAuthenticator($user, $password);

	return $cra->getAuthenticateFromChallenge($msg)->getSignature();
};

// Below here will get removed once you start working on this file

$n = 1;
$wait = 5;
while (true){
	sleep($wait);
	$time = $wait*$n;
	echo "Still here... bored. Been waiting $time seconds now";
	$n++;
}
