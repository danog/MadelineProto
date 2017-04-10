---
title: cancelDownloadFile
description: Stops file downloading. If file already downloaded do nothing.
---
## Method: cancelDownloadFile  
[Back to methods index](index.md)


Stops file downloading. If file already downloaded do nothing.

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|file\_id|[int](../types/int.md) | Yes|Identifier of file to cancel download|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->cancelDownloadFile(['file_id' => int, ]);
```

Or, if you're into Lua:

```
Ok = cancelDownloadFile({file_id=int, })
```

