---
title: users.getUsers
description: users.getUsers parameters, return type and example
---
## Method: users.getUsers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [InputUser](../types/InputUser.md) | Yes|


### Return type: [Vector\_of\_User](../types/User.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|NEED_MEMBER_INVALID|The provided member is invalid|
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|


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

$Vector_of_User = $MadelineProto->users->getUsers(['id' => [InputUser], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - users.getUsers
* params - `{"id": [InputUser], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/users.getUsers`

Parameters:

id - Json encoded  array of InputUser




Or, if you're into Lua:

```
Vector_of_User = users.getUsers({id={InputUser}, })
```

