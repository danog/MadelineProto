---
title: messages.reorderPinnedDialogs
description: Reorder pinned dialogs
---
## Method: messages.reorderPinnedDialogs  
[Back to methods index](index.md)


Reorder pinned dialogs

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|force|[Bool](../types/Bool.md) | Optional|Force reordering|
|order|Array of [Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Yes|New order|


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

$Bool = $MadelineProto->messages->reorderPinnedDialogs(['force' => Bool, 'order' => [InputPeer, InputPeer], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.reorderPinnedDialogs`

Parameters:

force - Json encoded Bool

order - Json encoded  array of InputPeer




Or, if you're into Lua:

```
Bool = messages.reorderPinnedDialogs({force=Bool, order={InputPeer}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


