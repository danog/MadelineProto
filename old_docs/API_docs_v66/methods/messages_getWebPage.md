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
|url|[CLICK ME string](../types/string.md) | Yes|URL|
|hash|[CLICK ME int](../types/int.md) | Yes|0 or $result['hash']|


### Return type: [WebPage](../types/WebPage.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|WC_CONVERT_URL_INVALID|WC convert URL invalid|


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

