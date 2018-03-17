---
title: account.reportPeer
description: Report for spam
---
## Method: account.reportPeer  
[Back to methods index](index.md)


Report for spam

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID or InputPeer](../types/InputPeer.md) | Optional|The peer to report|
|reason|[ReportReason](../types/ReportReason.md) | Yes|Why are you reporting this peer|


### Return type: [Bool](../types/Bool.md)

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

$Bool = $MadelineProto->account->reportPeer(['peer' => InputPeer, 'reason' => ReportReason, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.reportPeer`

Parameters:

peer - Json encoded InputPeer

reason - Json encoded ReportReason




Or, if you're into Lua:

```
Bool = account.reportPeer({peer=InputPeer, reason=ReportReason, })
```

