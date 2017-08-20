---
title: addSavedAnimation
description: Manually adds new animation to the list of saved animations. New animation is added to the beginning of the list. If the animation is already in the list, at first it is removed from the list. Only non-secret video animations with MIME type "video/mp4" can be added to the list
---
## Method: addSavedAnimation  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Manually adds new animation to the list of saved animations. New animation is added to the beginning of the list. If the animation is already in the list, at first it is removed from the list. Only non-secret video animations with MIME type "video/mp4" can be added to the list

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|animation|[InputFile](../types/InputFile.md) | Yes|Animation file to add. Only known to server animations (i. e. successfully sent via message) can be added to the list|


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

$Ok = $MadelineProto->addSavedAnimation(['animation' => InputFile, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - addSavedAnimation
* params - `{"animation": InputFile, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/addSavedAnimation`

Parameters:

animation - Json encoded InputFile




Or, if you're into Lua:

```
Ok = addSavedAnimation({animation=InputFile, })
```

