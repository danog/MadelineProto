---
title: phone.setCallRating
description: phone.setCallRating parameters, return type and example
---
## Method: phone.setCallRating  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|
|rating|[int](../types/int.md) | Yes|
|comment|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CALL_PEER_INVALID|The provided call peer object is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->phone->setCallRating(['peer' => InputPhoneCall, 'rating' => int, 'comment' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.setCallRating`

Parameters:

peer - Json encoded InputPhoneCall

rating - Json encoded int

comment - Json encoded string




Or, if you're into Lua:

```
Updates = phone.setCallRating({peer=InputPhoneCall, rating=int, comment='string', })
```

