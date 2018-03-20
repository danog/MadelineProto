# Creating a client

To store information about an account session and avoid re-logging in, serialization must be done.

A MadelineProto session is automatically serialized every `$MadelineProto->settings['serialization']['serialization_interval']` seconds (by default 30 seconds), and on shutdown. If the scripts shutsdown normally (without ctrl+c or fatal errors/exceptions), the session will also be serialized automatically.

To create the session and set the serialization destination file, do the following:
```php
$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings); // The session will be serialized to session.madeline
```

Do the same to load a serialized session:
```php
$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);  // The session will be loaded from session.madeline
```  


To change the session file after starting MadelineProto, do the following:
```php
$MadelineProto->session = 'newsession.madeline';
```




<form action="https://docs.madelineproto.xyz/docs/LOGIN.html"><input type="submit" value="Next section" /></form>