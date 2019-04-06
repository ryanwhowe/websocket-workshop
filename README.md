# PHP Yorkshire 2019 - Real Time Workshop

## Session overview

0. Introduction & real time web principles
1. The Crossbar router and WAMP
2. WAMP Authentication
3. Websockets in PHP
4. Dynamic authentication with workers
5. Caching and listening
6. Notifications with message queues
7. Experiments with WebRTC

## Pre-installation requirements

* git
* docker
* docker-compose (for convenience you might alias this to `dc` in your shell)
* Firefox or Chromium (any derivative)
* tail (seriously surprised if you're missing this)
* IDE or similar (you'll be reading this README a lot so markdown highlighting will help!)

_Pro-tip: I discovered long ago you can make it easy to add helper scripts to any environment
by adding "./bin" to your `$PATH` variable. If you do that then the `bin/run-*` commands in this
tutorial just become `run-*` saving you FOUR whole characters. Priceless_

## 0. Introduction & real time web principles

In this section we will cover:

* What defines a real time application
* What are the advantages of a real time application
* What technologies are available to achieve this

This part will be done on the slideshow, so sit back, relax and learn!

## 1. The Crossbar router and WAMP

In this section we will cover:

* The purpose of the "router" in web socket applications
* The protocol for websocket applications, WAMP
* Pub-sub and RPC

We'll start some practical work after a bit of intro on the slides; but first try running:

```
docker-compose build
```

From the root of this project (or if not, specify the path to the project's `docker-compose.yml` file anyway).
This will build some containers for you which will be pretty useful for the next bit!

### Lesson 1 Practical - Exploring a WAMP system

Start containers and see logs from crossbar by running:

```
bin/run-crossbar 1
```

The container is running crossbar with debug logs enabled. This is not recommended for production (on
even a moderately used system you can fairly quickly fill your disk or cause your file system to fall
over) but gives us a good insight into what's happening as we test the system.

View this example at http://localhost:8081/1/ (if you want to see the HTTP logs for any reason
you can do so by running `docker-compose logs -f http`)

Open the browser console; you should see some instructions and info about the script we're using.
View this file in `public/1/script.js` (all the lessons will have this file)

#### Pub-sub

First we can try some pub-sub commands typed into the console:

```
session.subscribe('test', function(){ console.log('Subscriber says: Got a message, args were: ', arguments); })
```

This subscribes to a topic named "test" and will respond by logging all the provided callback arguments to
the browser console. You'll see it returns a promise; you could use this to ensure that your subscription
has been acknowledged by the server.

Of course to actually test it we'll need to publish a message. Given we're not (yet)
connected to one-another's chat servers 

```
session.publish('test', ['abc'], {}, {exclude_me: false});
```

There's a bit more needed to get the basic working here. The first parameter again is the topic name,
which again is just a simple string. The next parameter is an array (list) of messages to send. Your
messages can include arrays or objects but they'll be reduced to contents which can be transported in
serialized form.

You can subscribe to any topic even if nobody will ever subscribe to it, and you can publish to topics
that nobody is subscribed to without errors (though if you publish to a topic and nobody is there to 
receive it, have you really published? (yes because the logs say so))

Try the same processes again whilst watching the crossbar logs; you'll see some more info about what is
going on under the hood - namely that crossbar has its own clients publishing and subscribing to its own
events for the purpose of it's internal operations (and logging) which is pretty cool.

#### Remote Procedure Call (RPC)

The same principles as for pub-sub pretty much apply on RPC. The difference with RPC is that for a call
to work it must have been registered. If you call a procedure that isn't registered (or the client who
registered has disconnected) then the call will fail and your code should handle this. Similarly if you
try to register a procedure which is _already_ registered the process will fail.

You can register a procedure like:

```
session.register("test", function(){
  console.log('Call provider says: someone called me with arguments:', arguments);
  console.log('Call provider says: given I had nothing to contribute, I returned the number 1');
  return 1;
}).then(function(){
  console.log('Call provider says: Yes, registered my procedure!');
}, function(){
  console.log('Call provider says: Oh no, registering went wrong. I got these arguments: ', arguments);
});
```

This looks a lot heavier than what we did for pub-sub, but pub-sub has the same callbacks - you're just
not going to get errors in pub-sub at this stage, but you very much can do with RPC.

Let's call that function:

```
session.call("test", [1, 'abc']).then(function(){
  console.log('Procedure caller says: I got an answer with arguments: ', arguments);
}, function(){
  console.log('Procedure caller says: Oh no, calling went wrong. I got these arguments: ', arguments);
});
```

Once again try running these scripts whilst watching the logs. Try registering the same procedure twice
or calling a procedure that doesn't exist. What arguments can you send to a procedure?

## 2. WAMP Authentication

In this section we will cover:

* Why you want authentication in WAMP
* Crossbar authentication types
* Static vs dynamic authentication

### Lesson 2 Practical - Realms & Permissions

For this exercise visit http://localhost:8081/2/

The previous commands were verbose so they've been included in a helper object. This is PHP
Yorkshire so you can call commands on the object using `alreet.pub()`, `alreet.sub()` etc.

We now also have the option to change the realm to which we are connected. Call `alreet.setRealm()`
with a string argument and then `alreet.connectAgain()` to close the connection and connect again
with a new realm (or just modify the script in `public/2/script.js` and refresh the page.

If you try this with anything other than "yorkshire" you'll get an error. Let's look at the
Crossbar config in `lesson-2/router/.crossbar/config.json`

_Our next bit will be on the screen_

### Lesson 2 Practical - Adding Permissions

Look in `.crossbar/config.json` and find the `realms` field of the first worker (it will have `type`
set to "router"). We are going to add another realm to show how realms/users are split.

```
{
  "name": "lancashire",
  "roles": [
    {
      "name": "workman",
      "permissions": [
        {
          "uri": "*",
          "allow": {
            "call": true,
            "register": false,
            "publish": false,
            "subscribe": true
          }
        }
      ]
    },
    {
      "name": "foreman",
      "permissions": [
        {
          "uri": "*",
          "allow": {
            "call": false,
            "register": true,
            "publish": true,
            "subscribe": false
          }
        }
      ]
    }
  ]
}
```

To allow sign-in to this realm add the following as a new field in the `auth` key of
the object at `transporsts[0].paths.ws`. The authenticator types are defined by their field names,
as only one of each type of authenticator is allowed.

```
"ticket": {
  "type": "static",
  "principals": {
    "barry": {
      "ticket": "notsosecret",
      "role": "workman"
    },
    "steve": {
      "ticket": "slightlymoresecret",
      "role": "foreman"
    }
  }
}
```

Any time we make changes to `config.json` (and we'll be doing that a fair bit) we need to restart
our crossbar servers. The easiest way is to restart the whole docker compose setup with 
`docker-compose restart` but we can name a service if  we want: `docker-compose restart crossbar_2`

Now refresh the webpage and run the following code in the console.

```
alreet.setConfig({
  realm:'lancashire',
  authmethods: ['ticket'],
  onchallenge: function(session, method, extra){
    return 'notsosecret';
  },
  authid: 'barry'
}).connectAgain();
```

The console message should show that you're no longer a guest - you're now a workman. Try out the pub-sub and
RPC methods we've already used as a guest and you'll see your permissions are now more restrictive. Open a new
tab, and with the above code, change the `authid` to "steve" and return value of the `onchallenge` function to
"slightlymoresecret" and try again (FYI it's not more secret, Steve is just naive).

What's the problem with this authentication? Check the logs for the crossbar service: `bin/run-with-logs 2`
You'll see that when an auth attempt is made the password is sent in plain text to the server. This will not
seem like a problem with static authenticators, but in reality you'll tend to use dynamic ones and so will not
want your user's authentication keys in plain text over a websocket connection (TLS mitigates this somewhat)

We can secure this by making the following changes: ticket->wampcra, principals->users, ticket->secret

* Change the field name inside the path declaration from `ticket` to `wampcra`
* Modify the array field name from `principals` to `users`
* Within each entity (larry and steve) modify their `ticket` to `secret` - now these actually become secrets

```
alreet.setConfig({
  realm:'lancashire',
  authmethods: ['wampcra'],
  onchallenge: function(session, method, extra){
    return autobahn.auth_cra.sign('slightlymoresecret', extra.challenge);
  },
  authid: 'steve'
}).connectAgain();
```

_We'll now discuss a few more authentication principles to finish_

## 3. Websockets in PHP

In this section we will cover:

* What's actually under the hood of websockets
* How can PHP be used to create persistent applications
* Making websocket connections in PHP

### Lesson 3 Practical - Talking to ourselves

To learn how websockets work in PHP we can create a client which responds to actions we can perform via
the browser. The first step is that we need to get around PHP's normal mode of operation in which a
script begins execution at the top, continues to the bottom and then exits.

Have a look at `lesson-3/php/client.php`. This contains a very simple mechanism used as the underlying
mechanism for lower level languages to continue running as a process - a never-ending while loop. To avoid
simply flooding the log file with prints the `sleep()` method is called to pause the process for a certain
number of seconds. If we wanted to run faster we could remove the sleep and echo statements and we'd just
have a PHP process running and doing nothing except counting up very fast in the while loop.

Because the process is running inside a docker container we can view it using docker's log follow command:

```
docker-compose logs -f php_3
```

There is also a shortcut at `bin/run-php` which takes a single argument, the lesson number, and on running
will restart the container then subsequently follow it's logs. Exit the log follow with `Ctrl+C`

As fun as the loop is we need some content. Let's make a websocket connection. For this we're going to use
a stack of PHP libraries that implement various parts of the process:

* React PHP is a non-blocking event & socket library for PHP
* Ratchet is a PHP WebSocket library allowing creation of clients and servers speaking over sockets
* Thruway is basically Autobahn but in PHP with a similar API. It uses React & Ratchet under the hood

Add some code to implement a websocket connection. You'll see close parity between this and the JavaScript
written in previous lessons so we may move a bit faster.

We can start by saving some time with our alias declarations:

```
use Psr\Log\NullLogger;
use Thruway\Authentication\ClientWampCraAuthenticator;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Logging\Logger;
use Thruway\Message\ChallengeMessage;
```

We can also remove the existing `while (true)` loop before we add more to our client.

Also for some reason the native Logger used by Thruway outputs debug logs to the container which can be
handy but also obscures what we're actually doing.

```
Logger::set(new NullLogger());
```

We have a new user this time:

```
define('USER', 'alan');
define('PASSWORD', 'definitelysecret');
```

Why not use the `.crossbar/config.json` file to see if you can work out their permissions?

```
$on_challenge = function (ClientSession $session, $method, ChallengeMessage $msg){
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
    "onChallenge" => $on_challenge,
    "authid" => USER,
]);

$connection->open();
```

_Note: the PHP containers bind to your system network so they can must the WebSocket servers
at the same port bound publicly, rather than the internal port on the container_

If you run this now (`bin/run-php 3`) you should see the logs from the WampCRA authentication and then
nothing else. Like with JS we need to add handlers to open and close events, and in the same way we
receive a session object which we can use to carry out WAMP operations.

Add this _before_ the connection open command.

```
$connection->on('open', function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");
});

$connection->on('close', function(){
	terminal_log("Connection closed, will keep trying to reconnect");
});
```

If you restart and view logs again you should see the connection open. It's worth mentioning that whilst
we removed the rather blunt `while (true)` loop there's still an event loop happening. When Thruway starts
a connection it uses the React PHP Loop factory to create a loop that the connection runs inside of.

This is how the library can be "non-blocking" - we can publish a message whilst also waiting to respond
to a call whilst at the same time handling incoming subscriptions. The event loop takes care of that for us.

Let's listen to what our JavaScript user is saying:

```
$topic = 'test';
$session->subscribe($topic, function ($args) use ($topic){
    terminal_log("Subscriber says: Received message '".implode(' ', $args)."'");
})->then(function () use ($topic){
    terminal_log("Subscriber says: I subscribed to topic '$topic'");
});
```

Now once again restart the PHP container, check it's subscribed and head back to the browser.

Visit `http://localhost:8081/3/` and open the console. The connection should already be established and
there's some new helper functions so run `alreet.pub()` in the console. Check the PHP logs and you should
see your message has been received by the PHP client.

This conversation might be one-sided if we can only actively talk from one side. Let's make the bot respond. 
Add `$session` inside the `use ()` block, and then add this inside the callback passed to the
`subscribe()` method:

```
terminal_log("\tSending one back...");
$session->publish($topic, ['I am a robot'], null, ['acknowledge' => true])->then(function (){
    terminal_log("Publisher says: Sent, did you get it?");
});
```

If we restart the PHP container and publish again from the browser we should see a message get sent
back to us from the bot. If we wanted this could form the basis of an interactive chatbot - all that
would remain is front end interface and back end intelligence - easy!

Just to demonstrate all the features we can also have the PHP client register a procedure inside the
on open handler:

```
$name = 'add';
$session->register($name, function ($args){
    terminal_log("Procedure says: Received parameters '".implode(', ', $args)."'");

    $answer = array_sum($args);
    terminal_log("\tSending the answer '$answer'...");

    return $answer;
})->then(function () use ($name){
    terminal_log("Procedure says: I registered procedure '$name'");
});
```

On the browser console run `alreet.call()` and the answer to our fiendish maths problem should be
returned to us by the bot.

## 4. Dynamic authentication with workers

In this section we will cover:

* Using workers in Crossbar
* How to communicate with a dynamic authenticator
* Granular action permissions per role

_First some basics on how custom authenticators work_

### Lesson 4 Practical - Registering a custom authenticator

Adding a custom authenticator involves a few changes to the configuration file. Find it at
`lesson-4/router/.crossbar/config.json`. The first change is to add a worker. Our "router"
object is the only worker in the workers array at present, so add a comma at the end and then
add this extra worker object at the end. The type of guest means this isn't a central crossbar
service, but crossbar will act to keep the service running for us.

```
{
  "type": "guest",
  "executable": "/usr/bin/env",
  "arguments": [
    "php",
    "../authenticator.php",
    "ws://127.0.0.1:9001",
    "yorkshire",
    "authenticator",
    "authenticator-kZ%3g@JR1oXb"
  ]
}
```

The arguments after the script name will become clear once we look at the other required items
and what's required in the PHP file itself. Currently the websocket router exposes a port accessible
via web, but this worker will want to connect internally via TCP, meaning we can set up a new transport.
At present again there is only one object in the transports array, so add this afterwards.

```
{
  "type": "websocket",
  "endpoint": {
    "type": "tcp",
    "port": 9001,
    "interface": "127.0.0.1"
  },
  "auth": {
    "wampcra": {
      "type": "static",
      "users": {
        "authenticator": {
          "secret": "authenticator-kZ%3g@JR1oXb",
          "role": "authenticator"
        }
      }
    }
  }
}
```

We set up a static authentication permission and you'll see the same random "secret" is here and also
in the worker definition we already added. Crossbar applies the same security so if we don't want
just anybody with access to this TCP port from connecting we add a WAMPCRA authentication step

The next step is for our existing web transport to be told to use dynamic authentication for those
authenticating via WAMPCRA. So replace the whole of the existing `auth` object with the following:

```
"auth": {
    "wampcra": {
      "type": "dynamic",
      "authenticator": "phpyork.auth"
    }
  }
```

The worker will act just like a regular client and register a procedure, specified above as "phpyork.auth"
This means the worker itself needs a role, which we can add now. We'll go back to the "yorkshire" realm
for this next step.

```
"roles": [
    {
      "name": "authenticator",
      "permissions": [
        {
          "uri": "phpyork.auth",
          "allow": {
            "register": true
          },
          "disclose": {
            "caller": true
          },
          "cache": true
        }
      ]
    }
]
```

We can delete the "guest" role from our yorkshire realm and instead add these (the role names
might make sense for British history buffs later...)

```
{
  "name": "king",
  "permissions": [
    {
      "uri": "phpyork.",
      "match": "prefix",
      "allow": {
        "call": false,
        "register": false,
        "publish": true,
        "subscribe": true
      },
      "disclose": {
        "publisher": true
      },
      "cache": true
    }
  ]
},
{
  "name": "banished",
  "permissions": []
}
```

You'll see the latter role has an odd feature which is that it has no permissions. This
is deliberate and allows us to ensure an unkown user cannot get a role with permissions
even should they find a hole in the WAMPCRA authentication process.

We now have everything set up to start up our crossbar server. Run `bin/run-crossbar 4`
to restart the server and check the logs. If we visit `http://localhost:8081/4` we can
see that it's not possible to connect because no authenticator is yet subscribed:

```
alreet.setAuth('whatever', 'wont-work').connect()
```

Let's build an authenticator in PHP. Firstly we need to grab the arguments we passed to
the worker via the command line. Add this above the WAMPCRA function that is already copied
in from lesson 3:

```
list ($url, $realm, $user, $password) = array_slice($argv, 1);
```

We can also copy in the basic connection block:

```
$connection = new Connection([
	"realm" => $realm,
	"url" => $url,
	"authmethods" => ["wampcra"],
	"onChallenge" => $on_challenge,
	"authid" => $user,
]);
```

Along with the connection open and close event listeners:

```
$connection->on('open', function (ClientSession $session, $details){
	terminal_log("Connection opened with role {$details->authrole}");
});

$connection->on('close', function (){
    terminal_log("Connection closed, will keep trying to reconnect");
});

$connection->open();
```

Now we have the basic client similar to lesson 3. Inside the connection we can register a procedure
that matches the name we supplied in the authentication config:

```
$name = 'phpyork.auth';
$session->register($name, function (){
    return [
        'role' => 'king',
        'secret' => 'abc',
    ];
})->then(function () use ($name){
    terminal_log("I registered procedure '$name'");
});
```

Now we can try connecting with any user name and the secret "abc". Restart with `bin/run-crossbar 4`:

```
alreet.setAuth('whatever', 'abc').connect()
```

We should now be connected to the server, but that wasn't very dynamic. We can do more with this by
checking the arguments passed when the auth procedure is called:

```
$session->register($name, function ($args){
    $realm = array_shift($args);
    $authid = array_shift($args);
    $details = array_shift($args);
    $session_id = $details->session;

    terminal_log("Received auth request for user '$authid' on realm '$realm'. Crossbar session was '$session_id'");
    ...
```

We can restart and run here to see the data provided. The session ID will be used more later but for now is useful
to link with the internal crossbar logs. If we add a function to the global scope nearer the top of the file
we can then use this to supply authentication info:

```
function token_from_user(string $name){
	switch ($name){
		case 'edmund':
			return ['langley', 'king'];
	}

	throw new Exception("No user found with name '$name'");
}
```

Then also inside the authenticator (after the call to `terminal_log`) replace the static `return` with:

```
try {
    list($token, $role) = token_from_user($authid);
}
catch (Exception $e) {
    terminal_log("Error: {$e->getMessage()}");

    return [
        'role' => 'banished',
        'secret' => '',
        'disclose' => true,
    ];
}

terminal_log("\tReturning token '$token'");

return [
    'role' => $role,
    'secret' => $token,
];
```

Once more we can restart ('bin/run-crossbar 4`) and then in the browser run:

```
alreet.setAuth('edmund', 'langley').connect()
```

We now find we're connected as Edmund of Langley, first king of the house of York.

### Lesson 4 Practical - Handling granular permissions

In this example our permissions were still fixed by the router configuration, which again
is less likely to work in a real world example. We can also have our authenticator register
a procedure to determine user permissions.

This first requires a modification of the `config.json` file again. Rather than adding a new
permission to our authenticator role we can just widen the scope a little. Change:

`"uri": "phpyork.auth"` to `"uri": "phpyork."` and then on the line below add a new field:

```
"match": "prefix",
```

This allows the authenticator to register any procedure in the namespace, which will be useful later

Then following the "king" role we can add another role:

```
{
  "name": "prince",
  "authorizer": "phpyork.permissions",
  "disclose": {
    "caller": true,
    "publisher": true
  }
}
```

Where you can see we once again declare an RPC URI but this time as a new key `authorizer`.
In order to handle the prince role we want to add Edmund's son, Edward of Norwich, to our users
function (inside the `switch` statement):

```
case 'edward':
    return ['norwich', 'prince'];
```

Now inside the on connection function of the authenticator script we can add another register
prodecure step, this time for permissions. Rather than build it bit by bit we're going to copy
in the whole block and can then talk about it more

```
$name = 'phpyork.permissions';
$session->register($name, function ($args){
    $details = array_shift($args);
    $uri = array_shift($args);
    $action = array_shift($args);

    terminal_log("User {$details->authid} ({$details->session}) wants to $action on endpoint: $uri");

    if ($action==='publish' && strpos($uri, "phpyork.chat")===0){
        return ['allow' => true, 'disclose' => true, 'cache' => true];
    }

    if ($action==='subscribe' && strpos($uri, "phpyork.chat")===0){
        return ['allow' => true, 'cache' => true];
    }

    return false;
})->then(function () use ($name){
    terminal_log("I registered procedure '$name'");
});
```

Now, after restarting with `bin/run-crossbar 4`, we can go to the browser and try out our new role:

```
alreet.setAuth('edward', 'norwich').connect()
alreet.sub()
```

The role should connect but then be unable to subscribe to our default "test" topic. However if
we follow the rules we've set for ourselves in the above permissions procedure and do:

```
alreet.sub(null, 'phpyork.chat')
```

Then it will work for us correctly.

### Lesson 4 Practical - Integration with existing auth mechanisms

Most likely your application will already have an authentication system and you will
want to connect this to your websocket system. To test this we've built a simple app
using the Slim framework which can be seen in `lesson-4/app/index.php`

The app has the following endpoints set up:

* `curl http://localhost:8014/lesson-4/yorkshire` will print a test output to check the container works
* `curl -X POST -d '{"name": "my user"}' -H 'content-type: application/json' localhost:8014/user`
   will add a new user with the specified name (names must be unique) and output both a password
   and a token. 
   
In the browser now we can call the method `login(user, password)` with our chosen username and the password
from above. However that's not much use unless our authenticator can also talk to the app.
   
The yorkshire realm in `config.json` will need another new role so we can split the app from hardcoded
roles if needed later.

```
{
  "name": "serf",
  "authorizer": "phpyork.permissions",
  "disclose": {
    "caller": true,
    "publisher": true
  }
}
```

Then before the Exception throw from the `token_from_user()` method we can make a call

```
$url = "http://app_4/auth?".http_build_query(['name' => $name]);

$token = http_get($url);
$token = trim($token);

if ($token){
    return [$token, 'serf'];
}
```

Now we're ready. Restart crossbar (`bin/run-crossbar 4`) and then in the browser call
`login(user, password)` filling in those two paramters with the user you created and the password
which was returned. You should see a log that it was successful If you open your browser network tab
you can also see the token which has been returned. Now your user has a shared secret with the crossbar
service without disclosing their password to the WAMP router or their token having to go across
a websocket connection (and ignore the fact the local connection is HTTP - the issues of localhost!)

Run `alreet.connect()` and you should see yourself signed in as a lowly serf who, because of lazy permission
programming on my part, has all the same permissions as a prince. Obviously given the above code you'd now
be able to change this and rightfully restore royal privilege.

## 5. Caching and listening

In this section we will cover:

* Storing messages sent via pub-sub
* Allowing users to see who's online

With the application connected to an authentication system we can now imagine a deeper integration,
where actions taken on the websocket system need reflecting in the application.

### Lesson 5 Practical - Permissions via API calls

A second PHP app has been set up connecting to the same databases. All previous calls can still be
made to the endpoint on port 8014, the new calls here are made to 8015.

Set up a "thread" on the application:

```
curl -X POST -d '{"name": "my user", "title": "some thread"}' -H 'content-type: application/json' localhost:8015/thread
```

The thread permission model in the application is simple - a thread records indicates that a named user
has access to a named thread. Check `db/threads/data` to see the JSON files created after running this request.

Because we are going to start storing messages we should also ensure a user has access to publish or 
subscribe to a thread. This can be done with an HTTP call to our application similar to authentication.

In the `register_permissions` method replace the current logic (after the log line) with this:

```
if (in_array($action, ['call', 'register'])){
    return false;
}

$thread = str_replace('phpyork.chat.', '', $uri);
if (!$thread){
    terminal_log("No thread name found");
    return false;
}

$url = "http://app_5/access?".http_build_query(['thread' => $thread, 'user' => $user]);

try {
    http_get($url);
}
catch (Exception $e) {
    terminal_log("Error: {$e->getMessage()}");

    return false;
}

return ['allow' => true, 'disclose' => true, 'cache' => true];
```

Once in place, crossbar restarted, and with appropriate threads created, the login and connect process
can be carried out in the browser as follows:

```
login(user, password)
const thread = 'phpyork.chat.thread-permalink'
alreet.connect().sub(thread).pub(thread, 'Hello to you');
```

### Lesson 5 Practical - Recording messages to our app

Crossbar provides "Meta events" in the form of topics to subscribe to and procedures to call.

Using one of these we can act as a "listener" on user chats and record these to our app.

Add the following inside the `start_connection` callback:

```
subscribe($session, 'wamp.subscription.on_create', function($args) use ($session){
    $subscriber_session_id = array_shift($args);

    if ($subscriber_session_id==$session->getSessionId()){
        // Avoids us catching any subscriptions we do ourselves
        return;
    }

    $details = array_shift($args);
    $topic = $details->uri;
    $topic_id = $details->id;
    terminal_log("A user ($subscriber_session_id) subscribed to a topic: '{$topic}' ({$topic_id})");
});
```

The authenticator also needs a permission added to `config.json` to do this - to save updating these
for subsequent actions we can use a prefix match:

```
{
  "uri": "wamp.subscription.",
  "match": "prefix",
  "allow": {
    "subscribe": true,
    "call": true
  },
  "cache": true
},
```

Test this in a browser and when a correct subscription (based on permissions) is made the log line should appear.

Once this works we can add the following after the log line in order to listen to individual chat messages:

```
subscribe($session, $topic, function($args, $kwargs, $details) use ($topic){
    terminal_log("We snooped on a message from '{$details->publisher_authid}' to topic '$topic' that said: '{$args[0]}'");
});
```

And again we need permission to do this:

```
{
  "uri": "phpyork.chat.",
  "match": "prefix",
  "allow": {
    "subscribe": true
  },
  "cache": true
}
```

Now when users subscribe to a topic (for the first time in a run of the router) a log line will appear
_and_ when any message is published another log line will appear. To round this off we can make an API
call to our application with the user's message, saving it to a database. Add this after the log line
in our "listening" subscription:

```
$thread = str_replace('phpyork.chat.', '', $topic);

$url = "http://app_5/message";
try {
    http_post($url, [
        'user' => $details->publisher_authid,
        'thread' => $thread,
        'message' => $args[0],
    ]);
}
catch (Exception $e){
    terminal_log("Error: {$e->getMessage()}");
    return;
}

terminal_log("Saved message via HTTP");
```

Now when users publish a message we should see our API get called, and can see saved messages in `db/messages/data`
as individual JSON files, recorded with the correct user name and thread name. In the app code for this
the same permissions have been used, though given the restrictions on who can publish via websockets and the
app port block this actually should not be necessary.

### Lesson 5 practical - Listing active users

When users are using a real time application to talk, collaborate or game with other users a key feature
is knowing who is online. Using publish times or sending keep alive publish messages might be one way
to handle this, but our router can actually provide tools to carry this out in a more robust fashion.

As well as publishing a message the first time any user subscribes to a topic, we can listen for every
time any user subscribes to a topic:

```
subscribe($session, 'wamp.subscription.on_subscribe', function ($args) use ($session){
    $subscriber_session_id = array_shift($args);
    if ($subscriber_session_id==$session->getSessionId()){
        // Avoids us catching any subscriptions we do ourselves
        return;
    }

    $topic_id = array_shift($args);

    terminal_log("A user ($subscriber_session_id) subscribed to a topic: ($topic_id)");
});
```

We run into a problem here though: the subscription listener only gives us IDs for the session and topic.
We have seen these IDs before and sometimes output them in log messages, but now we need to store
what they relate to in order to be useful.

This is where the Redis key-value database can be used as a fast effective cache for these lookups. In
order to use Redis effectively we need to add some storage of values to a number of existing places
in the application.

Add this to `register_auth()` just before the final `return` statement:

```
redis_set("session-$session_id", $authid);
```

Add this into our "on_create" listener (after the log line)

```
redis_set("topic-$topic_id", $topic);
redis_set_array($topic, []);
```

Then finally replace the log line in our "on_subscribe" listener with:

```
$topic = redis_get("topic-$topic_id");

$user = redis_get("session-$subscriber_session_id");

terminal_log("A user $user ($subscriber_session_id) subscribed to a topic: '{$topic}' ($topic_id)");

redis_add_to_array($topic ?? '', $user);
```

_NB: The simple snake case Redis methods are in `storage.php` as easy ways to use Redis without 
delving too far into its operation. A better way to handle this would be a single class which could
more effectively handle connections, but be careful about assuming a single object created once
will work everywhere in an application which may live days or weeks rather than half a second._

Now that we have a list of subscribers we can let users call this. We'll keep it simple at
this stage and let any user call it, rather than restricting to thread access. The authenticator
will need to register this procedure:

```
register($session, 'phpyork.subscribers', function($args){
    $topic = $args[0];
    if (!$topic){
        terminal_log("No topic supplied to check users");
        return '[]';
    }

    if (strpos($topic, "phpyork.chat.")!==0){
        $topic = "phpyork.chat.$topic";
    }

    $users = redis_get($topic);

    terminal_log("Returning list of users for URI '$topic': $users");

    return $users;
});
```

To allow our browser clients to call it we can modify the `register_permissions()`
function by swapping the block:

```
if (in_array($action, ['call', 'register'])){
    return false;
}
```

With this:

```
if ($action==='register'){
    return false;
}

if ($action==='call'){
    return $uri==='phpyork.subscribers';
}
```

We can now call this in a browser. Try connecting, subscribing to a topic (make sure it's
successful) and then run this in the browser console:

```
alreet.call('your-thread-name')
```

If it works the output should be a JSON encoded list of user names. To debug the Redis store
you can check out the Redis web UI at `http://localhost:5001/redis:6379/0/keys/`

Finally we need to handle user presence if they sign off. This requires two more WAMP meta
integrations. The following subscription gives the topic ID of a topic which has been
unsubscribed.

```
subscribe($session, 'wamp.subscription.on_unsubscribe', function ($args) use ($session){
    $topic_id = $args[1];

    terminal_log("A user unsubscribed from topic $topic_id");
});
```

For some reason this doesn't indicate which user unsubscribed. We can find this out using
a call to an endpoint which lists topic subscriber IDs:

```
call($session, 'wamp.subscription.list_subscribers', [$topic_id], function (CallResult $result) use ($session, $topic_id){
    $sessions = $result->getResultMessage()->getArguments();

    $session_ids = array_shift($sessions);
    $topic_users = [];
    foreach ($session_ids as $session_id){
        $user = redis_get("session-$session_id");
        if ($user){
            $topic_users[] = $user;
        }
    }

    $topic = redis_get("topic-$topic_id");

    terminal_log("Updating subscribers for $topic to: ".implode(', ', $topic_users));

    redis_set_array($topic, $topic_users);
});
```

This now has the effect of storing the subscriber list every time a user disconnects. You may notice
that if the topic ID key search was reversed then this procedure could be used instead of caching
the list in Redis, but caching more effectively demonstrates how the data might be used as well as reducing
load on the WAMP cache in favour of a more scalable caching service like Redis.

## 6. Notifications with message queues

In this section we will cover:

* Ensuring we don't block websockets with API calls
* Allowing other applications to communicate with websocket users

_Introduction to ZMQ and React Socket connections_

### Lesson 6 Practical - Creating a ZMQ listener

A file is ready at `lesson-6/zmq/zmq.php` to work on for this next part. To restart the ZMQ service
and watch its logs execute `bin/run-zmq`.

In case we've forgotten from lesson 3 we need an event loop to run a persistent script. What we might
have missed during all the crossbar worker examples is that Thruway's WAMP connection actually
implements its own internal loop when you create the connection and tell it to connect.

As well as the loop we can add a one-off action to let us know the loop started OK and then a regular
signal so we have something in logs to indicate it is still working. In a real system this would be
a great place to send a metric to Datadog or AWS Cloudwatch that could alarm if missing.

```
$loop = \React\EventLoop\Factory::create();

$loop->addTimer(1, function (){
	terminal_log("Loop was started, will report that it is still running every 5 minutes");
});

$loop->addPeriodicTimer(300, function (){
	terminal_log("Still running, next update in 5 minutes...");
});

$loop->run();
```

A loop on its own doesn't do much good. Setting up ZMQ is very easy using React - note that this
also only works because we built a container using the ZMQ library from PECL. It's been well
looked after in the transition to PHP 7 though can sometimes lag a bit behind new versions in beta.

Add all the following code _before_ the $loop-run() command or you'll get odd behaviour.

```
$port = getenv('ZMQ_PORT');
terminal_log("Will bind to port: $port");

$context = new \React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind("tcp://0.0.0.0:$port");
```

Note that we bind on all interfaces - you might not do this in circumstances where your machine
has a known IP and you are taking completely internal connections, but in a docker network or a
private VPC on a cloud provider this is sensible as you might not know your machine IP.

Also remember that this ZMQ service is on the "app" side of our architecture, so it could even live
inside the repository and share a server with your app code if you don't use containers or microsservices
in production.

Now we have a bind address we need to do something with an incoming message.

```
$pull->on('message', function ($message){
	terminal_log("Got a message: $message");
});
```

At this point our ZMQ service is set up but nothing is sending it any details. This is
an easy fix because our listening code from lesson 5 has been neatly refactored already
into `lesson-6/router/listener.php`

Check the `store_message()` function which carries out the HTTP call. We no longer want to
block the listener when carrying out this call so we replace the whole inside of that message
with the following code.

```
$port = getenv('ZMQ_PORT');
$dsn = "tcp://zmq_6:$port";
try {
    $context = new ZMQContext();
    $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'persist socket');
    $socket->connect($dsn);
    $socket->send(json_encode($send_data), ZMQ::MODE_DONTWAIT);

    terminal_log("Sent message to be saved via ZMQ (:$port)");
}
catch (Exception $e) {
    terminal_log("Error sending via ZMQ (:$port): {$e->getMessage()}");
}
```

Hopefully you can see similarities between this and the previous code for the ZMQ service as part of
the app. The difference is that whilst that opens a pull socket here we open a push socket.

The 'persist socket' parameter can be any string but using this string describes what the parameter
does more accurately. By setting the string it gives the socket a name and keeps it in use for future
connection attempts that use the same string name.

Now after restarting (`bin/run-crossbar 6`) we can carry out the process of:

* A user signs in on browser
* A user subscribes to a thread
* A user publishes a message

That we got used to testing in lesson 5. If you watch the logs for the ZMQ app listener 
(`docker-compose logs -f zmq_6`) then you should see the message arrive from the crossbar listener.

All we need now is to save it; we're assuming this service has access to the same storage as the 
HTTP app written in Slim, and for convenience the data saving logic from there has been copied over
even though the ZMQ script doesn't use Slim itself.

Copy the following inside the `on('message'` callback after the log:

```
try {
    store_message($message);
    terminal_log("Saved message to database");
}
catch (Exception $e){
    terminal_log("Storage error: {$e->getMessage()}");
}
```

Restart the ZMQ service (`bin/run-zmq`) again, send another message (no need to restart crossbar or
have the user connect again) and watch it get saved to the database. Now we can store messages
without blocking the crossbar listener.

### Aside

Try stopping the ZMQ listener (`docker-compose stop zmq_6`). Now send a message and watch the
crossbar listener report that it has saved via ZMQ. You know it hasn't because the listener was down.
Then try starting the listener (`bin/run-zmq`) and watch as it receives the message sent whilst
it was down.

What's happening here?

### Lesson 6 Practical - Messaging into websockets using ZMQ

Services outside of websockets may want to communicate with users in a websocket system. In a chat
system it might be the case people can live chat but also send emails which get parsed and
messaged to users. Or user actions on one part of a web route might need to relay to a control
panel or dashboard.

Having an application make a websocket connection as a one off to send a message defeats the
point of persistent clients and complicates code bases - now your whole app needs to talk websockets.
Instead the ZMQ concept we used to avoid blocking internally in a crossbar worker can be used.

In `lesson-6/app/index.php` is a route concept for a broadcast message - a user authenticates and
can message every user currently signed in on the crossbar server. The route already has the logic
for the password check to avoid unnecessary heavy lifting. Below that (but above the return response)
add the following code which should be quite familiar:

```
$port = getenv('ZMQ_PUSH_PORT');
$dsn = "tcp://crossbar_6:$port";
try {
    $context = new ZMQContext();
    $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'persist socket');
    $socket->connect($dsn);
    $socket->send($message, ZMQ::MODE_DONTWAIT);
}
catch (Exception $e) {
    return $response->withStatus(500)->write("ZMQ Error ($dsn): {$e->getMessage()}");
}
```

Other than handling an exception differently (we're now in web again remember) the rest of this looks
identical to how we pushed from crossbar's listener.

To test the route at least doesn't crash (should return a 204 response) run the following:

```
curl -I -X POST -d '{"password": "abc123", "message": "Test broadcast"}' -H 'content-type: application/json' localhost:8016/broadcast
```

The password is an environment variable in `docker-compose.yaml` if you want to change it.

To listen for these messages we could use our existing worker but that worker is already doing enough
different things and really if we had time we'd split out the listening functionality from the authentication
and permissions anyway. So the steps we use here to make the "broadcaster" could be copied and used
to extract that functionality, or create workers for many other purposes

Firstly near the bottom of `lesson-6/router/.crossbar/config.json` we add another worker:

```
{
  "type": "guest",
  "executable": "/usr/bin/env",
  "arguments": [
    "php",
    "../broadcaster.php",
    "ws://127.0.0.1:9001",
    "yorkshire",
    "broadcaster",
    "broadcaster-0shVV4XQ#Fm#"
  ]
}
```

As with the authenticator we add a WAMPCRA key with the user name, static secret and role:

```
"broadcaster": {
  "secret": "broadcaster-0shVV4XQ#Fm#",
  "role": "broadcaster"
}
```

Above in the "yorkshire" realm we can add the new role as well. We haven't got to publishing
just yet but to save time later this role has publish permission to a named topic already.

```
{
  "name": "broadcaster",
  "permissions": [
    {
      "uri": "phpyork.broadcast",
      "allow": {
        "publish": true
      },
      "cache": true
    }
  ]
}
```

The `lesson-6/router/broadcaster.php` file already has some boilerplate. Note that we're
retaining control of the `$loop` variable and passing it into the WAMP connection,
combining what we saw with the standalone ZMQ service and existing WAMP connections.

Inside the connection callback add the following, again familiar from when we wrote the ZMQ
service above.

```
$context = new \React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);

$bind_addr = "tcp://0.0.0.0:".getenv('ZMQ_PUSH_PORT');

terminal_log("Listening on $bind_addr");

$pull->bind($bind_addr);
```

Once again we listen on all interfaces. The ZMQ ports are being passed in via `docker/zmq.env`
if for any reason you wish to change them.

At present this is listening but doing nothing so to test it we can add:

```
// When we receive a message we then relay it out to users
$pull->on('message', function ($message) use ($session){
    terminal_log("Received message '$message'");
});
```

Now restart the crossbar service (`bin/run-crossbar 6`) and execute the cURL request above; all
being well we should see the message appear in the logs of our crossbar container.

Once we have the message received the next part is simple - publish it out to a topic:

```
$session->publish('phpyork.broadcast', [$message]);
```

We're now able to broadcast and with just a few small changes our users can receive them as well.
Firstly assuming we're still testing with users who gain their permisisons from `register_permissions()`
in `lesson-6/router/auth.php` we can add the following at line 83 (just below where we allow them
to call "phpyork.subscribers" from lesson 5):

```
if ($action==='subscribe' && $uri==='phpyork.broadcast'){
    return true;
}
```

Finally we could just run the subscription in console but for the real effect we can modify
`public/6/script.js` to add this inside the `connection.onopen` callback (should be around
line 126) so that users always get our broadcasts no matter what else they are doing:

```
console.log("Will auto-subscribe to broadcast messages");
sub('phpyork.broadcast');
```

Now run `alreet.connect()` to create the subscription (it will show in the console log) and then
execute the cURL command above to send the message to be published - if everything works this should
show up in the user's browser console.

## 7. Experiments with WebRTC

In this section we will cover:

* Principles of WebRTC
* Basic connection establishment
* How websockets can be used to facilitate WebRTC connections
* Tools & pitfalls for WebRTC development

### Lesson 7 Practical - Serverless WebRTC

Visit `http://localhost:8081/7/` in a browser. The instructions for how to continue are on that page.
You will need to pair up, choose a character and follow the instructions to try and establish a 
"serverless" WebRTC connection. In actual fact we are using a server because during the workshop it's
hard to know how to accurately move a long and complex string between two devices!
