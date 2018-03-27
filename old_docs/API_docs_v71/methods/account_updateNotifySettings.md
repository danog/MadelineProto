---
title: account.updateNotifySettings
description: Change notification settings
---
## Method: account.updateNotifySettings  
[Back to methods index](index.md)


Change notification settings

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputNotifyPeer](../types/InputNotifyPeer.md) | Yes|The peers to which the notification settings should be applied|
|settings|[InputPeerNotifySettings](../types/InputPeerNotifySettings.md) | Yes|Notification settings|


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

$Bool = $MadelineProto->account->updateNotifySettings(['peer' => InputNotifyPeer, 'settings' => InputPeerNotifySettings, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.updateNotifySettings`

Parameters:

peer - Json encoded InputNotifyPeer

settings - Json encoded InputPeerNotifySettings




Or, if you're into Lua:

```
Bool = account.updateNotifySettings({peer=InputNotifyPeer, settings=InputPeerNotifySettings, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


