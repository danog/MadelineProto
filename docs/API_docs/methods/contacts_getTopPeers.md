---
title: contacts_getTopPeers
description: contacts_getTopPeers parameters, return type and example
---
## Method: contacts\_getTopPeers  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|correspondents|[Bool](../types/Bool.md) | Optional|
|bots\_pm|[Bool](../types/Bool.md) | Optional|
|bots\_inline|[Bool](../types/Bool.md) | Optional|
|groups|[Bool](../types/Bool.md) | Optional|
|channels|[Bool](../types/Bool.md) | Optional|
|offset|[int](../types/int.md) | Required|
|limit|[int](../types/int.md) | Required|
|hash|[int](../types/int.md) | Required|


### Return type: [contacts\_TopPeers](../types/contacts_TopPeers.md)

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

$contacts_TopPeers = $MadelineProto->contacts_getTopPeers(['correspondents' => Bool, 'bots_pm' => Bool, 'bots_inline' => Bool, 'groups' => Bool, 'channels' => Bool, 'offset' => int, 'limit' => int, 'hash' => int, ]);
```