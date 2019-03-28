<?php
use Psr\Log\NullLogger;
use Thruway\Logging\Logger;

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

	// Error submission is bad, but in your own applications you
	// will be using Guzzle or similar, right?
	$response = @file_get_contents($url, false, $context);

	/**
	 * @var array $http_response_header
	 */
	$status_line = $http_response_header[0];

	preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);

	$status = $match[1];

	if ($status!=="200"){
		throw new Exception("Unexpected response status: {$status_line}\n\t$response");
	}

	return trim($response);
}

Logger::set(new NullLogger());
