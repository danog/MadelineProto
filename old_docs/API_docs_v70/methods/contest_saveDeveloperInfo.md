---
title: contest.saveDeveloperInfo
description: Save developer info for telegram contest
---
## Method: contest.saveDeveloperInfo  
[Back to methods index](index.md)


Save developer info for telegram contest

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|vk\_id|[int](../types/int.md) | Yes|VK user ID|
|name|[string](../types/string.md) | Yes|Name|
|phone\_number|[string](../types/string.md) | Yes|Phone number|
|age|[int](../types/int.md) | Yes|Age|
|city|[string](../types/string.md) | Yes|City|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->contest->saveDeveloperInfo(['vk_id' => int, 'name' => 'string', 'phone_number' => 'string', 'age' => int, 'city' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

