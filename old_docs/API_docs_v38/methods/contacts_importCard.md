---
title: contacts.importCard
description: Import card as contact
---
## Method: contacts.importCard  
[Back to methods index](index.md)


Import card as contact

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|export\_card|Array of [int](../types/int.md) | Yes|The card|


### Return type: [User](../types/User.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$User = $MadelineProto->contacts->importCard(['export_card' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.importCard`

Parameters:

export_card - Json encoded  array of int




Or, if you're into Lua:

```
User = contacts.importCard({export_card={int}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|EXPORT_CARD_INVALID|Provided card is invalid|
|NEED_MEMBER_INVALID|The provided member is invalid|


