---
title: account.getWallPapers
description: account.getWallPapers parameters, return type and example
---
## Method: account.getWallPapers  
[Back to methods index](index.md)




### Return type: [Vector\_of\_WallPaper](../types/WallPaper.md)

### Can bots use this method: **NO**


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

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

