console.log("Hi, welcome to the tutorial (lesson 1). We're not going to get bogged down in interface today;\n" +
	"learning in command line, code and console will teach us more about the principles and we'll\n" +
	"see how to hook this into an application later. Our first script will create a connection\n" +
	"object and then expose the session for us to play around with");

var url = "ws://localhost:8001/ws";
var connection = new autobahn.Connection({
	url: url,
	realm: "phpyorkshire"
});

var session;

connection.onopen = function (openedSession, details) {
	console.log("The websocket connection is open. At this point we get two parameters passed\n" +
		"into a callback which we supply. The first is a session, which will do all our other activity.\n" +
		"The second is an object of 'details' which looks like this:", details);
	session = openedSession;
	console.log("We've got a global variable called 'session' and have just allocated the\n" +
		"session provided by the callback to this variable. Type 'session.' in your terminal\n" +
		"to see what actions are available on the session object. Check publish, subscribe, register and call!");
};

connection.onclose = function (reason, details) {
	console.log("The connection was closed. Depending on what's been called this may have\n" +
		"been intentional, or might mean something has gone wrong. When it happens we get\n" +
		"a 'reason' and another 'details' object sent to the callback function provided\n" +
		"so we can see what happened. Here's the reason (details after):", reason, details);
};

console.log("Our websocket connection callbacks are created, so now we try to connect:");
connection.open();
