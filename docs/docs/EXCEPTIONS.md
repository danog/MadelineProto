# Exceptions

```
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

Every exception features a custom stack trace called `pretty TL trace`, that makes finding bugs **really** easy.

```
```

* \danog\MadelineProto\Exception - Default exception, thrown when a php error occures and in a lot of other cases

* \danog\MadelineProto\RPCErrorException - Thrown when an RPC error occurres (an error received via the mtproto API)

* \danog\MadelineProto\TL\Exception - Thrown on TL serialization/deserialization errors

* \danog\MadelineProto\NothingInTheSocketException - Thrown if no data can be read from the TCP socket

* \danog\MadelineProto\PTSException - Thrown if the PTS is unrecoverably corrupted

* \danog\MadelineProto\SecurityException - Thrown on security problems (invalid params during generation of auth key or similar)

* \danog\MadelineProto\TL\Conversion\Exception - Thrown if some param/object can't be converted to/from bot API/TD/TD-CLI format (this includes markdown/html parsing)

