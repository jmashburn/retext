<?php

return array(
	# Retext Application Routes
	'/retext' 							=> 'Retext\Handler\RetextHtmlHandler',

	'/retext/code'						=> 'Retext\Code\Handler\CodeHtmlHandler',

	# API Routes
	# Retext Code API Endpoints
	"/api/retext/code"					=> "Retext\Code\Handler\CodeHandler",
	"/api/retext/code/(disable|enable)"	=> "Retext\Code\Handler\CodeHandler",
	"/api/retext/code/:alpha"			=> "Retext\Code\Handler\CodeHandler",


	"/retext/message" 					=> "Retext\Message\Handler\MessageHtmlHandler",
	# Retext Message API Endpoints
	"/api/retext/message"					=> "Retext\Message\Handler\MessageHandler",
	"/api/retext/message/(disable|enable)"	=> "Retext\Message\Handler\MessageHandler",
	"/api/retext/message/:alpha"			=> "Retext\Message\Handler\MessageHandler",



	# Twillio Specific
	"/api/retext/twilio" 				=> "Retext\Twilio\Handler\TwilioHandler"
);
