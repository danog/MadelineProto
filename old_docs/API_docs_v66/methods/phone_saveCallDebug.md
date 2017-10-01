---
title: phone.saveCallDebug
description: phone.saveCallDebug parameters, return type and example
---
## Method: phone.saveCallDebug  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPhoneCall](../types/InputPhoneCall.md) | Yes|
|debug|[DataJSON](../types/DataJSON.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CALL_PEER_INVALID|The provided call peer object is invalid|
|DATA_JSON_INVALID|The provided JSON data is invalid|


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

$Bool = $MadelineProto->phone->saveCallDebug(['peer' => InputPhoneCall, 'debug' => DataJSON, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/phone.saveCallDebug`

Parameters:

peer - Json encoded InputPhoneCall

debug - Json encoded DataJSON




Or, if you're into Lua:

```
Bool = phone.saveCallDebug({peer=InputPhoneCall, debug=DataJSON, })
```

