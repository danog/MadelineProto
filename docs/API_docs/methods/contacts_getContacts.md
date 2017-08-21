---
title: contacts.getContacts
description: contacts.getContacts parameters, return type and example
---
## Method: contacts.getContacts  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|


### Return type: [contacts\_Contacts](../types/contacts_Contacts.md)

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

$contacts_Contacts = $MadelineProto->contacts->getContacts(['hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.getContacts
* params - `{"hash": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getContacts`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
contacts_Contacts = contacts.getContacts({hash=int, })
```

