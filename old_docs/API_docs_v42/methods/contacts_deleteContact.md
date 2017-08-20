---
title: contacts.deleteContact
description: contacts.deleteContact parameters, return type and example
---
## Method: contacts.deleteContact  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[InputUser](../types/InputUser.md) | Yes|


### Return type: [contacts\_Link](../types/contacts_Link.md)

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

$contacts_Link = $MadelineProto->contacts->deleteContact(['id' => InputUser, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.deleteContact
* params - `{"id": InputUser, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.deleteContact`

Parameters:

id - Json encoded InputUser




Or, if you're into Lua:

```
contacts_Link = contacts.deleteContact({id=InputUser, })
```

