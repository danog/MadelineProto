---
title: contacts.importCard
description: contacts.importCard parameters, return type and example
---
## Method: contacts.importCard  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|export\_card|Array of [int](../types/int.md) | Yes|


### Return type: [User](../types/User.md)

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

$User = $MadelineProto->contacts->importCard(['export_card' => [int], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - contacts.importCard
* params - `{"export_card": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.importCard`

Parameters:

export_card - Json encoded  array of int




Or, if you're into Lua:

```
User = contacts.importCard({export_card={int}, })
```

