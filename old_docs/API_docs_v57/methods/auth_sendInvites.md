---
title: auth.sendInvites
description: Invite friends to telegram!
---
## Method: auth.sendInvites  
[Back to methods index](index.md)


Invite friends to telegram!

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|phone\_numbers|Array of [string](../types/string.md) | Yes|Phone numbers to invite|
|message|[string](../types/string.md) | Yes|The message to send|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->auth->sendInvites(['phone_numbers' => ['string', 'string'], 'message' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/auth.sendInvites`

Parameters:

phone_numbers - Json encoded  array of string

message - Json encoded string




Or, if you're into Lua:

```
Bool = auth.sendInvites({phone_numbers={'string'}, message='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Bool](../types/Bool.md) will be returned instead.


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_EMPTY|The provided message is empty|


