---
title: contacts.exportCard
description: Export contact as card
---
## Method: contacts.exportCard  
[Back to methods index](index.md)


Export contact as card

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|


### Return type: [Vector\_of\_int](../types/int.md)

### Can bots use this method: **NO**


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

$Vector_of_int = $MadelineProto->contacts->exportCard();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.exportCard`

Parameters:




Or, if you're into Lua:

```
Vector_of_int = contacts.exportCard({})
```

