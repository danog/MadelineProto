---
title: get_dialogs
description: get_dialogs parameters, return type and example
---
## Method: get_dialogs  

Gets full list of dialogs

### Return type: Array of [Peer objects](API_docs/types/Peer.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Peers = $MadelineProto->get_dialogs();
```

Or, if you're into Lua:

```
Peers = get_dialogs(true)
```

