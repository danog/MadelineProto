---
title: account.getNotifySettings
description: account.getNotifySettings parameters, return type and example
---
## Method: account.getNotifySettings  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputNotifyPeer](../types/InputNotifyPeer.md) | Yes|


### Return type: [PeerNotifySettings](../types/PeerNotifySettings.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$PeerNotifySettings = $MadelineProto->account->getNotifySettings(['peer' => InputNotifyPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getNotifySettings`

Parameters:

peer - Json encoded InputNotifyPeer




Or, if you're into Lua:

```
PeerNotifySettings = account.getNotifySettings({peer=InputNotifyPeer, })
```

