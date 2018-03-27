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
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The peer to report|
|reason|[ReportReason](../types/ReportReason.md) | Yes|Why are you reporting this peer|


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

$Bool = $MadelineProto->account->reportPeer(['peer' => InputPeer, 'reason' => ReportReason, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.reportPeer`

Parameters:

peer - Json encoded InputPeer

reason - Json encoded ReportReason




Or, if you're into Lua:

```
Bool = account.reportPeer({peer=InputPeer, reason=ReportReason, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


