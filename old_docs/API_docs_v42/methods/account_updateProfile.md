---
title: account.updateProfile
description: account.updateProfile parameters, return type and example
---
## Method: account.updateProfile  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|


### Return type: [User](../types/User.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|ABOUT_TOO_LONG|The provided bio is too long|
|FIRSTNAME_INVALID|The first name is invalid|


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

$User = $MadelineProto->account->updateProfile(['first_name' => 'string', 'last_name' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updateProfile`

Parameters:

first_name - Json encoded string

last_name - Json encoded string




Or, if you're into Lua:

```
User = account.updateProfile({first_name='string', last_name='string', })
```

