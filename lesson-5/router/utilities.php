<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\NullLogger;
use Thruway\ClientSession;
use Thruway\Logging\Logger;

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

function http_post(string $url, array $data){
	$client = new Client();

	try {
		$response = $client->post($url, ['form_params' => $data]);
	}
	catch (RequestException $e){
		$response = $e->getResponse();
		throw new Exception("Unexpected response status: {$response->getStatusCode()}\n\t{$response->getBody()}");
	}

	$text = (string) $response->getBody();

	return trim($text);
}

function register(ClientSession $session, string $name, callable $func){
	$session->register($name, $func)->then(function () use ($name){
		terminal_log("I registered procedure '$name'");
	});
}

function subscribe(ClientSession $session, string $topic, callable $func){
	$session->subscribe($topic, $func)->then(function () use ($topic){
		terminal_log("I subscribed to topic '$topic'");
	});
}

date_default_timezone_set(getenv('TZ'));

Logger::set(new NullLogger());
