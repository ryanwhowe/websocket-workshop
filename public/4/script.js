console.log("Welcome to lesson 4");

var alreet = (function (){
	var session, connection;
	// @todo change this to 8004 for the lesson
	const url = "ws://localhost:8005/ws";

	var token;
	const config = {
		realm: 'yorkshire',
		authmethods: ['wampcra'],
		onchallenge: function(session, method, extra){
			return autobahn.auth_cra.sign(token, extra.challenge);
		},
		max_retries: 0
	};

	function setSession(openedSession){
		session = openedSession;
	}

	function pub(args, name){
		name = name || "test";
		args = args || ['abc', 1];
		session.publish(name, args, {}, {exclude_me: true, acknowledge: true}).then(function (){
			console.log('Publisher says: Yes, published to '+name+'!');
		}, function (){
			console.log('Publisher says: Oh no, publishing to '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function sub(func, name){
		name = name || "test";
		func = func || function (args){
			console.log('Subscriber says: Got a message, it said: '+args.join(' '));
		};
		session.subscribe(name, func).then(function (){
			console.log('Subscriber says: Yes, subscribed to '+name+'!');
		}, function (){
			console.log('Subscriber says: Oh no, subscribing to '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function call(args, name){
		name = name || "add";
		args = args || [1, 2];
		console.log(name, args);
		session.call(name, args).then(function (res){
			console.log('Procedure caller says: I got an answer to '+name+' which was: ', res);
		}, function (){
			console.log('Procedure caller says: Oh no, calling '+name+' went wrong. I got these arguments: ', arguments);
		});
	}

	function reg(func, name){
		name = name || "subtract";
		func = func || function (args){
			console.log('Call provider says: someone called me with parameters:', args);
			return args.reduce(function(partial_sum, a){
				return partial_sum - 1 * a;
			});
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

	function setAuth(setUser, setToken){
		config.authid = setUser;
		token = setToken;

		return singleton;
	}

	function connect(callback){
		if (connection && connection.isConnected){
			connection.close();
			setTimeout(function(){
				connect(callback);
			}, 100);

			return;
		}

		const realm = config.realm;
		const auth = config.authmethods;

		config.url = url;
		connection = new autobahn.Connection(config);

		connection.onopen = function (openedSession, details){
			setSession(openedSession);
			console.log("Websocket connection open to realm "+realm+" as role: '"+details.authrole+"'. Call methods on the 'alreet' object to continue");

			if (typeof callback==='function'){
				callback(openedSession, details);
			}
		};

		connection.onclose = function (reason, details){
			console.log("The connection was closed. Depending on what's been called this may have\n"+
				"been intentional, or might mean something has gone wrong. When it happens we get\n"+
				"a 'reason' and another 'details' object sent to the callback function provided\n"+
				"so we can see what happened. Here's the reason (details after):", reason, details);
		};

		console.log("Connecting to websocket server (realm is "+realm+", using auth "+JSON.stringify(auth)+"):");
		connection.open();
	}

	function getSession(){
		return session;
	}

	const singleton = {
		setAuth: setAuth,
		setRealm: setRealm,
		pub: pub,
		publish: pub,
		sub: sub,
		subscribe: sub,
		call: call,
		reg: reg,
		register: reg,
		connect: connect,
		getSession: getSession
	};

	return singleton;
})();

console.log("This time we're not opening the connection automatically; run `alreet.setAuth(user, token).connect()` to try connecting");
