# Exceptions

```php
try {
    $MadelineProto->get_dialogs();
} catch (\danog\MadelineProto\RPCErrorException $e) {
    if ($e->rpc === 'BOT_METHOD_INVALID') {
        \danog\MadelineProto\Logger::log("Bots can't execute this method!");
    } else {
        $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'An error occurred while calling get_dialogs: '.$e]);
    }
}
```

MadelineProto can throw lots of different exceptions.  

* [List of exception types](#list-of-exception-types)
* [Pretty TL trace](#pretty-tl-trace)
* [Getting the TL trace](#getting-the-tl-trace)


## List of exception types

* \danog\MadelineProto\Exception - Default exception, thrown when a php error occures and in a lot of other cases

* \danog\MadelineProto\RPCErrorException - Thrown when an RPC error occurres (an error received via the MTProto API): **note** that the error message of this exception is localized in English, and may vary: to fetch the original API error message use `$e->rpc`.

* \danog\MadelineProto\TL\Exception - Thrown on TL serialization/deserialization errors

* \danog\MadelineProto\NothingInTheSocketException - Thrown if no data can be read from the TCP socket

* \danog\MadelineProto\PTSException - Thrown if the PTS is unrecoverably corrupted

* \danog\MadelineProto\SecurityException - Thrown on security problems (invalid params during generation of auth key or similar)

* \danog\MadelineProto\TL\Conversion\Exception - Thrown if some param/object can't be converted to/from bot API/TD/TD-CLI format (this includes markdown/html parsing)


## Pretty TL trace
Every exception features a custom stack trace called `pretty TL trace`, that makes finding bugs **really** easy:

```php
php > $MadelineProto->messages->sendMessage(['peer' => '@dd', 'message' => 'hi']);

Uncaught \danog\MadelineProto\Exception: This peer is not present in the internal peer database in /home/pwrtelegram/cleanMadeline/src/danog/MadelineProto/MTProtoTools/PeerHandler.php:330
Revision: 63823fc3cc5070bd8a1ebe91e60e1fd583a2f37f

TL Trace (YOU ABSOLUTELY MUST READ THE TEXT BELOW):

PeerHandler.php(327):   get_info("dd",false)
TL.php(339):            get_info("dd")
['peer']
While serializing:      messages.sendMessage
CallHandler.php(79):    serialize_method("messages.sendMessage",{"peer":"@dd","message":"hi"})
APIFactory.php(142):    method_call("messages.sendMessage",{"peer":"@dd","message":"hi"},{"datacenter":4})
php shell code(1):      __call("sendMessage",[{"peer":"@dd","message":"hi"}])
  thrown in /home/pwrtelegram/cleanMadeline/src/danog/MadelineProto/MTProtoTools/PeerHandler.php on line 330
php >
```

Explanation:  

`Uncaught \danog\MadelineProto\Exception`: an exception of type \danog\MadelineProto\Exception was thrown and not caught using a `catch` block, like showed in the first example of this page.

`This peer is not present in the internal peer database`: this error means you have tried sending a message to a peer that does not exist or is not present in MadelineProto's internal peer database: in fact, `@dd` is not a valid telegram username. 

`in /home/pwrtelegram/cleanMadeline/src/danog/MadelineProto/MTProtoTools/PeerHandler.php:330`: this indicates the line where this error was thrown

`Revision: 63823fc3cc5070bd8a1ebe91e60e1fd583a2f37f`: this indicates the MadelineProto version: **always** include this code when opening github issues or reporting errors in the group.

```
PeerHandler.php(327):   get_info("dd",false)
TL.php(339):            get_info("dd")
['peer']
While serializing:      messages.sendMessage
CallHandler.php(79):    serialize_method("messages.sendMessage",{"peer":"@dd","message":"hi"})
APIFactory.php(142):    method_call("messages.sendMessage",{"peer":"@dd","message":"hi"},{"datacenter":4})
php shell code(1):      __call("sendMessage",[{"peer":"@dd","message":"hi"}])
```

This part is supposed to be read from bottom to top, the most important parts are:

`While serializing:      messages.sendMessage`: this means the error was thrown while serializing the method call for messages->sendMessage

`['peer']`: this means the error was thrown while trying to serialize the `peer` parameter, so **you should fix that part of your code**:
```php
$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => 'hi']);
```

## Getting the TL trace 

To get the whole TL trace as string, cast the exception object to string:

```php
try {
    //
} catch (\danog\MadelineProto\Exception $e) {
    $estring = (string) $e;
    $estring2 = 'This also works: '.$e;
    $estring3 = "So does this: $e";
    // use $estring to report the error using sendMessage or log
}
```

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/SELF.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/FLOOD_WAIT.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>