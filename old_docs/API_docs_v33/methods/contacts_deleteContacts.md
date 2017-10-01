---
title: contacts.deleteContacts
description: contacts.deleteContacts parameters, return type and example
---
## Method: contacts.deleteContacts  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|Array of [InputUser](../types/InputUser.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|NEED_MEMBER_INVALID|The provided member is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


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

$Bool = $MadelineProto->contacts->deleteContacts(['id' => [InputUser], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.deleteContacts`

Parameters:

id - Json encoded  array of InputUser




Or, if you're into Lua:

```
Bool = contacts.deleteContacts({id={InputUser}, })
```

