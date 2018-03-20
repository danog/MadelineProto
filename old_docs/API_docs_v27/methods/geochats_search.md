---
title: geochats.search
description: geochats.search parameters, return type and example
---
## Method: geochats.search  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[CLICK ME InputGeoChat](../types/InputGeoChat.md) | Yes|
|q|[CLICK ME string](../types/string.md) | Yes|
|filter|[CLICK ME MessagesFilter](../types/MessagesFilter.md) | Yes|
|min\_date|[CLICK ME int](../types/int.md) | Yes|
|max\_date|[CLICK ME int](../types/int.md) | Yes|
|offset|[CLICK ME int](../types/int.md) | Yes|
|max\_id|[CLICK ME int](../types/int.md) | Yes|
|limit|[CLICK ME int](../types/int.md) | Yes|


### Return type: [geochats\_Messages](../types/geochats_Messages.md)

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

$geochats_Messages = $MadelineProto->geochats->search(['peer' => InputGeoChat, 'q' => 'string', 'filter' => MessagesFilter, 'min_date' => int, 'max_date' => int, 'offset' => int, 'max_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.search
* params - `{"peer": InputGeoChat, "q": "string", "filter": MessagesFilter, "min_date": int, "max_date": int, "offset": int, "max_id": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.search`

Parameters:

peer - Json encoded InputGeoChat

q - Json encoded string

filter - Json encoded MessagesFilter

min_date - Json encoded int

max_date - Json encoded int

offset - Json encoded int

max_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
geochats_Messages = geochats.search({peer=InputGeoChat, q='string', filter=MessagesFilter, min_date=int, max_date=int, offset=int, max_id=int, limit=int, })
```

