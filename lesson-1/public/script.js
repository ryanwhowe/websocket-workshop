console.log("Hi, welcome to the tutorial. We're not going to get bogged down in interface today - learning in command line, code and console will teach us more about the principles and we'll see how to hook this into an application later.");

var connection = new autobahn.Connection({
	url: "ws://localhost:8001/ws",
	realm: "phpyorkshire"
});

var session;

connection.onopen = function (openedSession, details) {
	console.log('Opened', details);
	session = openedSession;
	console.log("Type 'session.' in your terminal to see what actions are available on the session object. Check publish, subscribe, register and call!");
};

connection.onclose = function (reason, details) {
	console.log('Closed', reason, details);
};

connection.open();

/*
	E.g

 session.subscribe('test', function(){ console.log('Got a message, args were: ', arguments); })

 session.publish('test', ['abc'], {}, {exclude_me: false});

 */
