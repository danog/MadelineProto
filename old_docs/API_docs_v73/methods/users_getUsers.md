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
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|
|MEMBER_NO_LOCATION|An internal failure occurred while fetching user info (couldn't find location)|
|NEED_MEMBER_INVALID|The provided member is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

