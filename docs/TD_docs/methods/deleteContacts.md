---
title: deleteContacts
description: Deletes users from contacts list
---
## Method: deleteContacts  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Deletes users from contacts list

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|user\_ids|Array of [int](../types/int.md) | Yes|Identifiers of users to be deleted|


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

$Ok = $MadelineProto->deleteContacts(['user_ids' => [int], ]);
```

Or, if you're into Lua:

```
Ok = deleteContacts({user_ids={int}, })
```

