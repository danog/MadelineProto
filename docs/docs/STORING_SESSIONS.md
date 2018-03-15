# Storing sessions

To store information about an account session, serialization must be done.

An istance of MadelineProto is automatically serialized every `$settings['serialization']['serialization_interval']` seconds (by default 30 seconds), and on shutdown. 

To set the serialization destination file, do the following:

When creating a new session:
```
$MadelineProto = new \danog\MadelineProto\API($settings);
$MadelineProto->session = 'session.madeline'; // The session will be serialized to session.madeline
$MadelineProto->serialize(); // Force first serialization
```

To load a serialized session:
```  
$MadelineProto = new \danog\MadelineProto\API('session.madeline');
```  

To load a serialized session, replacing settings on deserialization:
```
$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
```

If the scripts shutsdown normally (without ctrl+c or fatal errors/exceptions), the session will be serialized automatically.


