---
title: contacts.resetTopPeerRating
description: Reset top peer rating for a certain category/peer
---
## Method: contacts.resetTopPeerRating  
[Back to methods index](index.md)


Reset top peer rating for a certain category/peer

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|category|[TopPeerCategory](../types/TopPeerCategory.md) | Yes|The category |
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The peer|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->contacts->resetTopPeerRating(['category' => TopPeerCategory, 'peer' => InputPeer, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.resetTopPeerRating`

Parameters:

category - Json encoded TopPeerCategory

peer - Json encoded InputPeer




Or, if you're into Lua:

```
Bool = contacts.resetTopPeerRating({category=TopPeerCategory, peer=InputPeer, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


