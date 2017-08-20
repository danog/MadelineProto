---
title: toggleGroupEditors
description: Gives or revokes all members of the group editor rights. Needs creator privileges in the group
---
## Method: toggleGroupEditors  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Gives or revokes all members of the group editor rights. Needs creator privileges in the group

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|group\_id|[int](../types/int.md) | Yes|Identifier of the group|
|anyone\_can\_edit|[Bool](../types/Bool.md) | Yes|New value of anyone_can_edit|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
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

$Ok = $MadelineProto->toggleGroupEditors(['group_id' => int, 'anyone_can_edit' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - toggleGroupEditors
* params - `{"group_id": int, "anyone_can_edit": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/toggleGroupEditors`

Parameters:

group_id - Json encoded int

anyone_can_edit - Json encoded Bool




Or, if you're into Lua:

```
Ok = toggleGroupEditors({group_id=int, anyone_can_edit=Bool, })
```

