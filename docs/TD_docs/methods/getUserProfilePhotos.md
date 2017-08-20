---
title: getUserProfilePhotos
description: Returns profile photos of the user. Result of this query can't be invalidated, so it must be used with care
---
## Method: getUserProfilePhotos  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Returns profile photos of the user. Result of this query can't be invalidated, so it must be used with care

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|offset|[int](../types/int.md) | Yes|Photos to skip, must be non-negative|
|limit|[int](../types/int.md) | Yes|Maximum number of photos to be returned, can't be greater than 100|


### Return type: [UserProfilePhotos](../types/UserProfilePhotos.md)

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

$UserProfilePhotos = $MadelineProto->getUserProfilePhotos(['user_id' => int, 'offset' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - getUserProfilePhotos
* params - `{"user_id": int, "offset": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/getUserProfilePhotos`

Parameters:

user_id - Json encoded int

offset - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
UserProfilePhotos = getUserProfilePhotos({user_id=int, offset=int, limit=int, })
```

