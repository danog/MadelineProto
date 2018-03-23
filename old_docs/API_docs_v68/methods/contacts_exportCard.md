---
title: contacts.exportCard
description: Export contact as card
---
## Method: contacts.exportCard  
[Back to methods index](index.md)


Export contact as card



### Return type: [Vector\_of\_int](../types/int.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_int = $MadelineProto->contacts->exportCard();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.exportCard`

Parameters:




Or, if you're into Lua:

```
Vector_of_int = contacts.exportCard({})
```

