---
title: contacts.importCard
description: contacts.importCard parameters, return type and example
---
## Method: contacts.importCard  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|export\_card|Array of [int](../types/int.md) | Yes|


### Return type: [User](../types/User.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|EXPORT_CARD_INVALID|Provided card is invalid|
|NEED_MEMBER_INVALID|The provided member is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$User = $MadelineProto->contacts->importCard(['export_card' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.importCard`

Parameters:

export_card - Json encoded  array of int




Or, if you're into Lua:

```
User = contacts.importCard({export_card={int}, })
```

