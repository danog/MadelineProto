---
title: contest.saveDeveloperInfo
description: contest.saveDeveloperInfo parameters, return type and example
---
## Method: contest.saveDeveloperInfo  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|vk\_id|[CLICK ME int](../types/int.md) | Yes|
|name|[CLICK ME string](../types/string.md) | Yes|
|phone\_number|[CLICK ME string](../types/string.md) | Yes|
|age|[CLICK ME int](../types/int.md) | Yes|
|city|[CLICK ME string](../types/string.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

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

