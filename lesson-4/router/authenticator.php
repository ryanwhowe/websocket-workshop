<?php

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

function http_get(string $url, array $headers = []){
	$context = stream_context_create([
		"http" => [
			"method" => 'GET',
			"header" => implode("\n", $headers),
			"ignore_errors" => true,
		],
	]);

	$response = file_get_contents($url, false, $context);

	/**
	 * @var array $http_response_header
	 */
	$status_line = $http_response_header[0];

	preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);

	$status = $match[1];

	if ($status!=="200"){
		throw new Exception("Unexpected response status: {$status_line}\n".$response);
	}

	return $response;
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
