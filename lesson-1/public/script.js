console.log("Hi, welcome to the tutorial. We're not going to get bogged down in interface today" +
	" - learning in command line, code and console will teach us more about the principles and we'll" +
	" see how to hook this into an application later.");

var connection = new autobahn.Connection({
	url: "ws://localhost:8001/ws",
	realm: "phpyorkshire"
});

var session;

connection.onopen = function (openedSession, details) {
	console.log("The websocket connection is open. At this point we get two parameters passed" +
		"into a callback which we supply. The first is a session, which will do all our other activity." +
		"The second is an object of 'details' which looks like this:", details);
	session = openedSession;
	console.log("We've got a global variable called 'session' and have just allocated the" +
		" session provided by the callback to this variable. Type 'session.' in your terminal" +
		" to see what actions are available on the session object. Check publish, subscribe, register and call!");
};

connection.onclose = function (reason, details) {
	console.log("The connection was closed. Depending on what's been called this may have " +
		"been intentional, or might mean something has gone wrong. When it happens we get" +
		" a 'reason' and another 'details' object sent to the callback function provided" +
		" so we can see what happened. Here's the reason (details after):", reason, details);
};

connection.open();

/*
	E.g

 session.subscribe('test', function(){ console.log('Got a message, args were: ', arguments); })

 session.publish('test', ['abc'], {}, {exclude_me: false});

 */
