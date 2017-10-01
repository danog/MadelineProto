---
title: contacts.resetTopPeerRating
description: contacts.resetTopPeerRating parameters, return type and example
---
## Method: contacts.resetTopPeerRating  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|category|[TopPeerCategory](../types/TopPeerCategory.md) | Yes|
|peer|[InputPeer](../types/InputPeer.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$Bool = $MadelineProto->contacts->resetTopPeerRating(['category' => TopPeerCategory, 'peer' => InputPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.resetTopPeerRating`

Parameters:

category - Json encoded TopPeerCategory

peer - Json encoded InputPeer




Or, if you're into Lua:

```
Bool = contacts.resetTopPeerRating({category=TopPeerCategory, peer=InputPeer, })
```

