---
title: contacts.getSuggested
description: contacts.getSuggested parameters, return type and example
---
## Method: contacts.getSuggested  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|limit|[CLICK ME int](../types/int.md) | Yes|


### Return type: [contacts\_Suggested](../types/contacts_Suggested.md)

### Can bots use this method: **YES**


### MadelineProto Example:


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

$contacts_Suggested = $MadelineProto->contacts->getSuggested(['limit' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.getSuggested
* params - `{"limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getSuggested`

Parameters:

limit - Json encoded int




Or, if you're into Lua:

```
contacts_Suggested = contacts.getSuggested({limit=int, })
```

