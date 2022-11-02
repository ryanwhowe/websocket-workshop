console.log("Welcome to lesson 2");

const wsWorkshop = (function (){
	let session, connection;
	const url = "ws://localhost:8002/ws";

	let config = {
		realm: 'ws-workshop',
		authmethods: ['anonymous']
	};

	function setSession(openedSession){
		session = openedSession;
	}

	function pub(name, args){
		name = name || "test";
		args = args || ['abc', 1];
		session.publish(name, args, {}, {exclude_me: false, acknowledge: true}).then(function (){
			console.log('Publisher says: Yes, published to '+name+'!');
		}, function (){
			console.log('Publisher says: Oh no, publishing to '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function sub(name, func){
		name = name || "test";
		func = func || function (){
			console.log('Subscriber says: Got a message, args were: ', arguments);
		};
		session.subscribe(name, func).then(function (){
			console.log('Subscriber says: Yes, subscribed to '+name+'!');
		}, function (){
			console.log('Subscriber says: Oh no, subscribing to '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function call(name, args){
		name = name || "test";
		args = args || ['abc', 1];
		session.call(name, args).then(function (){
			console.log('Procedure caller says: I got an answer to '+name+' with arguments: ', arguments);
		}, function (){
			console.log('Procedure caller says: Oh no, calling '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function reg(name, func){
		name = name || "test";
		func = func || function (){
			console.log('Call provider says: someone called me with arguments:', arguments);
			console.log('Call provider says: given I had nothing to contribute, I returned the number 1');
			return 1;
		};
		session.register(name, func).then(function (){
			console.log('Call provider says: Yes, registered procedure '+name+'!');
		}, function (){
			console.log('Call provider says: Oh no, registering '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function setRealm(realm){
		if (!realm){
			throw "You need to pass a valid string as the 'realm' parameter";
		}

		config.realm = realm;

		return singleton;
	}

	function setConfig(userConfig){
		if (!userConfig.realm){
			throw "You need to pass a valid string as the 'realm' field";
		}
		if (!userConfig.authmethods || userConfig.authmethods.length<1){
			throw "You need to pass a valid array of strings as the 'auth' field";
		}
		userConfig.authmethods.map(function (method){
			if (['ticket', 'wampcra'].indexOf(method)> -1){
				if (!userConfig.authid){
					throw "You must specify a truthy value as the 'authid' field";
				}
				if (!userConfig.onchallenge || typeof userConfig.onchallenge!=='function'){
					throw "You must specify a callback function(session, method, extra) as the 'onchallenge' field";
				}
			}
		});

		config = userConfig;

		return singleton;
	}

	function connectAgain(){
		if (!connection || !connection.isConnected){
			connect(true);
			return;
		}

		connection.close();
		setTimeout(connectAgain, 100);
	}

	function connect(again){
		if (connection && connection.isConnected){
			console.log("Already connected, use .connectAgain method to close and reconnect");
			return;
		}

		const realm = config.realm;
		const auth = config.authmethods;

		config.url = url;
		connection = new autobahn.Connection(config);

		connection.onopen = function (openedSession, details){
			setSession(openedSession);
			console.log("Websocket connection open to realm "+realm+" as role: '"+details.authrole+"'. Call methods on the 'wsWorkshop' object to continue");
		};

		connection.onclose = function (reason, details){
			console.log("The connection was closed. Depending on what's been called this may have\n"+
				"been intentional, or might mean something has gone wrong. When it happens we get\n"+
				"a 'reason' and another 'details' object sent to the callback function provided\n"+
				"so we can see what happened. Here's the reason (details after):", reason, details);
		};

		console.log("Connecting to websocket server"+(again ? ' again' : '')+" (realm is "+realm+", using auth "+JSON.stringify(auth)+"):");
		connection.open();
	}

	const singleton = {
		setConfig: setConfig,
		setRealm: setRealm,
		pub: pub,
		publish: pub,
		sub: sub,
		subscribe: sub,
		call: call,
		reg: reg,
		register: reg,
		connect: connect,
		connectAgain: connectAgain
	};

	return singleton;
})();

wsWorkshop.connect();
