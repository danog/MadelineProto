---
title: users.getUsers
description: Get info about users
---
## Method: users.getUsers  
[Back to methods index](index.md)


Get info about users

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|The ids of the users|


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

