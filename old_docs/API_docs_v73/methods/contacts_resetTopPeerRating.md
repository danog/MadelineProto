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
|peer|[InputPeer](../types/InputPeer.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
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

