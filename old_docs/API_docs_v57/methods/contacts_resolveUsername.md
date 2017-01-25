---
title: contacts.resolveUsername
description: contacts.resolveUsername parameters, return type and example
---
## Method: contacts.resolveUsername  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|username|[string](../types/string.md) | Required|


### Return type: [contacts\_ResolvedPeer](../types/contacts_ResolvedPeer.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$contacts_ResolvedPeer = $MadelineProto->contacts->resolveUsername(['username' => string, ]);
```