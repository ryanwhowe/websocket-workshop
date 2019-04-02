<?php
/**
 * Cleaned up the work from lesson 5 to make it easier to track what's
 * happening and what's being changed
 */

use Thruway\ClientSession;

require __DIR__.'/../vendor/autoload.php';

require 'utilities.php';
require 'connection.php';
require 'auth.php';
require 'storage.php';
require 'listener.php';

start_connection($argv, function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");

	register_auth($session);
	register_permissions($session);

	subscribe_subs_create($session);

	subscribe_subs_sub($session);

	subscribe_subs_unsub($session);

	register_get_subscribers($session);
});
