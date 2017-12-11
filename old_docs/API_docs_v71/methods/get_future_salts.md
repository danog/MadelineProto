---
title: get_future_salts
description: get_future_salts parameters, return type and example
---
## Method: get\_future\_salts  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|num|[int](../types/int.md) | Yes|


### Return type: [FutureSalts](../types/FutureSalts.md)

### Can bots use this method: **YES**


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

$FutureSalts = $MadelineProto->get_future_salts(['num' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - get_future_salts
* params - `{"num": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/get_future_salts`

Parameters:

num - Json encoded int




Or, if you're into Lua:

```
FutureSalts = get_future_salts({num=int, })
```

