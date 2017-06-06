---
title: updateStickerSet
description: Installs/uninstalls or enables/archives sticker set. Official sticker set can't be uninstalled, but it can be archived
---
## Method: updateStickerSet  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Installs/uninstalls or enables/archives sticker set. Official sticker set can't be uninstalled, but it can be archived

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|set\_id|[long](../types/long.md) | Yes|Identifier of the sticker set|
|is\_installed|[Bool](../types/Bool.md) | Yes|New value of is_installed|
|is\_archived|[Bool](../types/Bool.md) | Yes|New value of is_archived|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->updateStickerSet(['set_id' => long, 'is_installed' => Bool, 'is_archived' => Bool, ]);
```

Or, if you're into Lua:

```
Ok = updateStickerSet({set_id=long, is_installed=Bool, is_archived=Bool, })
```

