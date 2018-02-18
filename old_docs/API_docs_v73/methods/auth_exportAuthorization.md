---
title: auth.exportAuthorization
description: auth.exportAuthorization parameters, return type and example
---
## Method: auth.exportAuthorization  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|dc\_id|[int](../types/int.md) | Yes|


### Return type: [auth\_ExportedAuthorization](../types/auth_ExportedAuthorization.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|DC_ID_INVALID|The provided DC ID is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$auth_ExportedAuthorization = $MadelineProto->auth->exportAuthorization(['dc_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - auth.exportAuthorization
* params - `{"dc_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.exportAuthorization`

Parameters:

dc_id - Json encoded int




Or, if you're into Lua:

```
auth_ExportedAuthorization = auth.exportAuthorization({dc_id=int, })
```

