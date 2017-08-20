---
title: importContacts
description: Adds new contacts/edits existing contacts, contacts user identifiers are ignored. Returns list of corresponding users in the same order as input contacts. If contact doesn't registered in Telegram, user with id == 0 will be returned
---
## Method: importContacts  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Adds new contacts/edits existing contacts, contacts user identifiers are ignored. Returns list of corresponding users in the same order as input contacts. If contact doesn't registered in Telegram, user with id == 0 will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|contacts|Array of [contact](../types/contact.md) | Yes|List of contacts to import/edit|


### Return type: [Users](../types/Users.md)

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

$Users = $MadelineProto->importContacts(['contacts' => [contact], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - importContacts
* params - `{"contacts": [contact], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/importContacts`

Parameters:

contacts - Json encoded  array of contact




Or, if you're into Lua:

```
Users = importContacts({contacts={contact}, })
```

