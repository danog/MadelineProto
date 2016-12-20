---
title: contacts_resetTopPeerRating
description: contacts_resetTopPeerRating parameters, return type and example
---
## Method: contacts\_resetTopPeerRating  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|category|[TopPeerCategory](../types/TopPeerCategory.md) | Required|
|peer|[InputPeer](../types/InputPeer.md) | Required|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->contacts_resetTopPeerRating(['category' => TopPeerCategory, 'peer' => InputPeer, ]);
```