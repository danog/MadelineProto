---
title: account.getNotifySettings
description: Get notification settings
---
## Method: account.getNotifySettings  
[Back to methods index](index.md)


Get notification settings

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputNotifyPeer](../types/InputNotifyPeer.md) | Yes|Notification source |


### Return type: [PeerNotifySettings](../types/PeerNotifySettings.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$PeerNotifySettings = $MadelineProto->account->getNotifySettings(['peer' => InputNotifyPeer, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getNotifySettings`

Parameters:

peer - Json encoded InputNotifyPeer




Or, if you're into Lua:

```
PeerNotifySettings = account.getNotifySettings({peer=InputNotifyPeer, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


