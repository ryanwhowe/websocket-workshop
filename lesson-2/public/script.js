console.log("Welcome to lesson 2");

var alreet = (function (){
	var session, connection;
	const url = "ws://localhost:8002/ws";
	var realm = 'phpyorkshire';

	function setSession(openedSession){
		session = openedSession;
	}

	function pub(name, args){
		name = name || "test";
		args = args || ['abc', 1];
		session.publish(name, args, {}, {exclude_me: false}).then(function (){
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

	function setRealm(realmName){
		realm = realmName;
	}

	function connectAgain(){
		if (!connection || !connection.isConnected){
			return connect(true);
		}

		connection.close();
		setTimeout(connectAgain, 100);
	}

	function connect(again){
		if (connection && connection.isConnected){
			console.log("Already connected, use .connectAgain method to close and reconnect");
			return;
		}

		connection = new autobahn.Connection({
			url: url,
			realm: realm
		});

		connection.onopen = function (openedSession){
			setSession(openedSession);
			console.log("Websocket connection open to realm "+realm+". Call methods on the 'alreet' object to continue");
		};

		connection.onclose = function (reason, details){
			console.log("The connection was closed. Depending on what's been called this may have\n"+
				"been intentional, or might mean something has gone wrong. When it happens we get\n"+
				"a 'reason' and another 'details' object sent to the callback function provided\n"+
				"so we can see what happened. Here's the reason (details after):", reason, details);
		};

		console.log("Connecting to websocket server"+(again ? ' again' : '')+":");
		connection.open();
	}

	return {
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
	}
})();

alreet.connect();
