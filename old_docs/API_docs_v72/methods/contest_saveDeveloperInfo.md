---
title: contest.saveDeveloperInfo
description: contest.saveDeveloperInfo parameters, return type and example
---
## Method: contest.saveDeveloperInfo  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|vk\_id|[int](../types/int.md) | Yes|
|name|[string](../types/string.md) | Yes|
|phone\_number|[string](../types/string.md) | Yes|
|age|[int](../types/int.md) | Yes|
|city|[string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->contest->saveDeveloperInfo(['vk_id' => int, 'name' => 'string', 'phone_number' => 'string', 'age' => int, 'city' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contest.saveDeveloperInfo
* params - `{"vk_id": int, "name": "string", "phone_number": "string", "age": int, "city": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contest.saveDeveloperInfo`

Parameters:

vk_id - Json encoded int

name - Json encoded string

phone_number - Json encoded string

age - Json encoded int

city - Json encoded string




Or, if you're into Lua:

```
Bool = contest.saveDeveloperInfo({vk_id=int, name='string', phone_number='string', age=int, city='string', })
```

