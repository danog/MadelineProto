---
title: deleteSavedAnimation
description: Removes animation from the list of saved animations
---
## Method: deleteSavedAnimation  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Removes animation from the list of saved animations

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|animation|[InputFile](../types/InputFile.md) | Yes|Animation file to delete|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
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

$Ok = $MadelineProto->deleteSavedAnimation(['animation' => InputFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - deleteSavedAnimation
* params - `{"animation": InputFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/deleteSavedAnimation`

Parameters:

animation - Json encoded InputFile




Or, if you're into Lua:

```
Ok = deleteSavedAnimation({animation=InputFile, })
```

