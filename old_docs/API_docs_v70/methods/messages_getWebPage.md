---
title: messages.getWebPage
description: Get webpage preview
---
## Method: messages.getWebPage  
[Back to methods index](index.md)


Get webpage preview

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|url|[string](../types/string.md) | Yes|URL|
|hash|[int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [WebPage](../types/WebPage.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$WebPage = $MadelineProto->messages->getWebPage(['url' => 'string', 'hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getWebPage`

Parameters:

url - Json encoded string

hash - Json encoded int




Or, if you're into Lua:

```
WebPage = messages.getWebPage({url='string', hash=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|WC_CONVERT_URL_INVALID|WC convert URL invalid|


