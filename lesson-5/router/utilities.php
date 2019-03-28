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
