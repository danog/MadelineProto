---
title: messages.getWebPagePreview
description: Get webpage preview
---
## Method: messages.getWebPagePreview  
[Back to methods index](index.md)


Get webpage preview

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message|[string](../types/string.md) | Yes|Extract preview from this message|


### Return type: [MessageMedia](../types/MessageMedia.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$MessageMedia = $MadelineProto->messages->getWebPagePreview(['message' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getWebPagePreview`

Parameters:

message - Json encoded string




Or, if you're into Lua:

```
MessageMedia = messages.getWebPagePreview({message='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [MessageMedia](../types/MessageMedia.md) will be returned instead.


