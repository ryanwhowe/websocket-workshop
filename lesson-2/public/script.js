console.log("Welcome to lesson 2");

var url = "ws://localhost:8002/ws";
var connection = new autobahn.Connection({
	url: url,
	realm: "phpyorkshire"
});

var session;

connection.onopen = function (openedSession, details) {
	session = openedSession;
	console.log("Websocket connection open and 'session' variable is available for use");
};

connection.onclose = function (reason, details) {
	console.log("The connection was closed. Depending on what's been called this may have\n" +
		"been intentional, or might mean something has gone wrong. When it happens we get\n" +
		"a 'reason' and another 'details' object sent to the callback function provided\n" +
		"so we can see what happened. Here's the reason (details after):", reason, details);
};

console.log("Connecting to websocket server:");
connection.open();
