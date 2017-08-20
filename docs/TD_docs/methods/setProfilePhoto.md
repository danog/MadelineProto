---
title: setProfilePhoto
description: Uploads new profile photo for logged in user. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. If something changes, updateUser will be sent
---
## Method: setProfilePhoto  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Uploads new profile photo for logged in user. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. If something changes, updateUser will be sent

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|photo\_path|[string](../types/string.md) | Yes|Path to new profile photo|


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

$Ok = $MadelineProto->setProfilePhoto(['photo_path' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - setProfilePhoto
* params - `{"photo_path": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/setProfilePhoto`

Parameters:

photo_path - Json encoded string




Or, if you're into Lua:

```
Ok = setProfilePhoto({photo_path='string', })
```

