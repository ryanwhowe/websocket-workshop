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
    },
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
to restart the server and check the logs.

Perms role

```
{
  "uri": "phpyork.permissions",
  "allow": {
    "register": true
  },
  "disclose": {
    "caller": true
  },
  "cache": true
}
```

Prince role

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

Serf role

```
{
  "name": "serf",
  "authorizer": "phpyork.permissions",
  "disclose": {
    "caller": true,
    "publisher": true
  }
},
```

### Lesson 4 Practical - Handling granular permissions

### Lesson 4 Practical - Integration with existing auth mechanisms

## 5. Caching and listening

In this section we will cover:



## 6. Notifications with message queues

In this section we will cover:



## 7. Experiments with WebRTC

In this section we will cover:


