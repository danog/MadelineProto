---
title: account.getWallPapers
description: Returns a list of available wallpapers.
---
## Method: account.getWallPapers  
[Back to methods index](index.md)


Returns a list of available wallpapers.



### Return type: [Vector\_of\_WallPaper](../types/WallPaper.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Vector_of_WallPaper = $MadelineProto->account->getWallPapers();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getWallPapers`

Parameters:




Or, if you're into Lua:

```
Vector_of_WallPaper = account.getWallPapers({})
```

