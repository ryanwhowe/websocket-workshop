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

View this example at http://localhost:8081/ (if you want to see the HTTP logs for any reason
you can do so by running `docker-compose logs -f http_1`)

Open the browser console; you should see some instructions and info about the script we're using.
View this file in `lesson-1/public/script.js` (all the lessons will have this file)

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
  console.log('Procedure caller says: I got an answer with arguements: ', arguments);
}, function(){
  console.log('Procedure caller says: Oh no, calling went wrong. I got these arguments: ', arguments);
});
```

Once again try running these scripts whilst watching the logs. Try registering the same procedure twice
or calling a procedure that doesn't exist. What arguments can you send to a procedure?

## 2. WAMP Authentication

In this section we will cover:



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


