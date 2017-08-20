---
title: finishFileGeneration
description: Finishes file generation
---
## Method: finishFileGeneration  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Finishes file generation

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|generation\_id|[long](../types/long.md) | Yes|Identifier of the generation process|


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

$Ok = $MadelineProto->finishFileGeneration(['generation_id' => long, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - finishFileGeneration
* params - `{"generation_id": long, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/finishFileGeneration`

Parameters:

generation_id - Json encoded long




Or, if you're into Lua:

```
Ok = finishFileGeneration({generation_id=long, })
```

