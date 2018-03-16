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


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_EMPTY|The provided message is empty|


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

$Bool = $MadelineProto->auth->sendInvites(['phone_numbers' => ['string'], 'message' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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


