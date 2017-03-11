---
title: deleteProfilePhoto
description: Deletes profile photo. If something changes, updateUser will be sent
---
## Method: deleteProfilePhoto  
[Back to methods index](index.md)


Deletes profile photo. If something changes, updateUser will be sent

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|profile\_photo\_id|[long](../types/long.md) | Yes|Identifier of profile photo to delete|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Ok = $MadelineProto->deleteProfilePhoto(['profile_photo_id' => long, ]);
```

Or, if you're into Lua:

```
Ok = deleteProfilePhoto({profile_photo_id=long, })
```

