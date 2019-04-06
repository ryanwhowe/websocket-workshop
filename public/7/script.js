console.log("Welcome to lesson 7.\n" +
	"If you are Alice you'll join Bob's chat - start by calling Alice.findBob() where the only argument is Bob's IP\n" +
	"If you are Bob you'll create a chat; get your connection string then call Bob.startChatWithAlice() where the only argument is that string");

var connection = function(url, callback){
	var session, connection;

	function connect(url, callback){
		const config = {
			url: url,
			realm: 'yorkshire',
			max_retries: 0
		};

		connection = new autobahn.Connection(config);

		connection.onopen = function (openedSession, details){
			setSession(openedSession);

			if (typeof callback==='function'){
				callback(openedSession, details);
			}
		};

		connection.onclose = function (reason, details){
			console.log("The connection didn't work; check the containers are up", reason, details);
		};

		connection.open();
	}

	function setSession(openedSession){
		session = openedSession;
	}

	function getSession(){
		return session;
	}

	connect(url, callback);

	return {
		getSession: getSession
	};
};

var Alice = (function(){
	var singleton, connection;

	function findBob(bobsIp){
		connection = connection('ws://'+bobsIp+':8007/ws', function(session){
			session.subscribe('create', function(args){
				console.log("Bob's connection string follows. Enter this into the join chat window, then come back and use 'Alice.respondToBob()' with the connection string that your browser subsequently generates: ", args[0]);
			}).then(function (){
				console.log('Subscribed to "create", waiting for Bob to message');
			}, function (error){
				console.log("Couldn't subscribe to 'create' to get Bob's message: "+error.args[0]);
			});
		});
	}

	function respondToBob(alicesICEString){
		if (!connection){
			throw "Must have already called findBob(bobsIp)";
		}

		session.publish('create', [alicesICEString], {}, {acknowledge: true}).then(function (){
			console.log("Sent Alice's connection string to Bob");
		}, function (error){
			console.log("Couldn't send Alice's connection string to Bob: "+error.args[0]);
		});
	}

	singleton = {
		findBob: findBob,
		respondToBob: respondToBob
	};

	return singleton;
})();

var Bob = (function(){
	var singleton;

	function startChatWithAlice(bobsICEString){
		connection = connection('ws://localhost:8007/ws', function(session){
			session.publish('create', [bobsICEString], {}, {acknowledge: true}).then(function (){
				console.log("Sent Bob's connection string to Alice. Click continue in the window and wait for Alice's connection string to be sent");
			}, function (error){
				console.log("Couldn't send Bob's connection string to Alice: "+error.args[0]);
			});

			session.subscribe('respond', function(args){
				console.log("Alice's connection string follows. Enter this into the partner response window, then click continue: ", args[0]);
			}).then(function (){
				console.log('Subscribed to "respond", waiting for Alice to message');
			}, function (error){
				console.log("Couldn't subscribe to 'respond' to get Alice's message: "+error.args[0]);
			});
		});
	}

	singleton = {
		startChatWithAlice: startChatWithAlice
	};

	return singleton;
})();
