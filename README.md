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
bin/run-with-logs 1
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
Crossbar config in `lesson-2/.crossbar/config.json`

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

## 3. Websockets in PHP

In this section we will cover:



## 4. Dynamic authentication with workers

In this section we will cover:



## 5. Caching and listening

In this section we will cover:



## 6. Notifications with message queues

In this section we will cover:



## 7. Experiments with WebRTC

In this section we will cover:


