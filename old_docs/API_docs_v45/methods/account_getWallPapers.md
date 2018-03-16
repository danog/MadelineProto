---
title: account.getWallPapers
description: Returns a list of available wallpapers.
---
## Method: account.getWallPapers  
[Back to methods index](index.md)


Returns a list of available wallpapers.

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|


### Return type: [Vector\_of\_WallPaper](../types/WallPaper.md)

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

$Vector_of_WallPaper = $MadelineProto->account->getWallPapers();
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getWallPapers`

Parameters:




Or, if you're into Lua:

```
Vector_of_WallPaper = account.getWallPapers({})
```

