console.log("Welcome to lesson 5");

var wsWorkshop = (function (){
	var session, connection;
	const url = "ws://localhost:8005/ws";

	var token;
	const config = {
		realm: 'ws-workshop',
		authmethods: ['wampcra'],
		onchallenge: function (session, method, extra){
			return autobahn.auth_cra.sign(token, extra.challenge);
		},
		max_retries: 0
	};

	function setSession(openedSession){
		session = openedSession;
	}

	function checkThread(thread){
		if (!thread){
			throw "You must enter a thread name as first parameter";
		}
		const prefix = "workshop.chat.";
		if (thread.indexOf(prefix)!==0){
			thread = prefix+thread;
		}
		return thread;
	}

	function pub(thread, message){

		thread = checkThread(thread);

		if (!message){
			throw "You must enter a message as second parameter";
		}

		session.publish(thread, [message], {}, {acknowledge: true}).then(function (){
			console.log('Publisher says: Yes, published to '+thread+'!');
		}, function (error){
			console.log('Publisher says: Oh no, publishing to '+thread+' went wrong: '+error.args[0]);
		});

		return singleton;
	}

	function sub(thread){
		thread = checkThread(thread);

		var func = function (args){
				console.log('Subscriber says: Got a message, it said: '+args.join(' '));
			};
		session.subscribe(thread, func).then(function (){
			console.log('Subscriber says: Yes, subscribed to '+thread+'!');
		}, function (error){
			console.log('Subscriber says: Oh no, subscribing to '+thread+' went wrong: '+error.args[0]);
		});

		return singleton;
	}

	function call(thread){
		if (!thread){
			throw "You must enter a thread name to check users for";
		}

		session.call('workshop.subscribers', [thread]).then(function (res){
			console.log('Procedure caller says: I got an answer to '+name+' which was: ', res);
		}, function (){
			console.log('Procedure caller says: Oh no, calling '+name+' went wrong. I got these arguments: ', arguments);
		});

		return singleton;
	}

	function reg(func, name){
		name = name || "subtract";
		func = func || function (args){
				console.log('Call provider says: someone called me with parameters:', args);
				return args.reduce(function (partial_sum, a){
					return partial_sum-1 * a;
				});
			};
		session.register(name, func).then(function (){
			console.log('Call provider says: Yes, registered procedure '+name+'!');
		}, function (){
			console.log('Call provider says: Oh no, registering '+name+' went wrong. I got these arguments: ', arguments);
		});

		return singleton;
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
			setTimeout(function (){
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
			console.log("Websocket connection open to realm "+realm+" as role: '"+details.authrole+"'. Call methods on the 'wsWorkshop' object to continue");

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

		return singleton;
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

function login(user, password){
	var url = 'http://localhost:8014/login';

	var headers = new Headers();
	headers.append('Content-Type','application/json');

	var options = {
		method: 'POST',
		headers: headers,
		cors: 'no-cors',
		body: JSON.stringify({name: user, password: password})
	};

	fetch(url, options).then(function(response) {
		response.json().then(function(data){
			if (data.error){
				console.error(data.error)
				return;
			}

			console.log('Login successful, setting token on websocket');

			wsWorkshop.setAuth(user, data.token);
		});
	}).catch(function(error){
		console.error('Login error:', error);
	});
}

console.log("We will start by logging in using a user and password created from the App in lesson 4.\n" +
	"Use the 'login(user, password)' method, or if you know the token run wsWorkshop.setAuth(user, token)\n" +
	"Either way once done run wsWorkshop.connect()");
