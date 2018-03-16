---
title: messages.getWebPage
description: messages.getWebPage parameters, return type and example
---
## Method: messages.getWebPage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|hash|[int](../types/int.md) | Yes|


### Return type: [WebPage](../types/WebPage.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|WC_CONVERT_URL_INVALID|WC convert URL invalid|


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

$WebPage = $MadelineProto->messages->getWebPage(['url' => 'string', 'hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getWebPage`

Parameters:

url - Json encoded string

hash - Json encoded int




Or, if you're into Lua:

```
WebPage = messages.getWebPage({url='string', hash=int, })
```

