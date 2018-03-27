---
title: contacts.getTopPeers
description: Get most used chats
---
## Method: contacts.getTopPeers  
[Back to methods index](index.md)


Get most used chats

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|correspondents|[Bool](../types/Bool.md) | Optional|Fetch users?|
|bots\_pm|[Bool](../types/Bool.md) | Optional|Fetch bots?|
|bots\_inline|[Bool](../types/Bool.md) | Optional|Fetch inline bots?|
|phone\_calls|[Bool](../types/Bool.md) | Optional|Fetch phone calls?|
|groups|[Bool](../types/Bool.md) | Optional|Fetch groups?|
|channels|[Bool](../types/Bool.md) | Optional|Fetch channels and supergroups?|
|offset|[int](../types/int.md) | Yes|Initially 0, then `$offset += $contacts_TopPeers['categories']['count'];`|
|limit|[int](../types/int.md) | Yes|How many results to fetch|
|hash|[int](../types/int.md) | Yes|$MadelineProto->gen_vector_hash(previously fetched peer ids);|


### Return type: [contacts\_TopPeers](../types/contacts_TopPeers.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$contacts_TopPeers = $MadelineProto->contacts->getTopPeers(['correspondents' => Bool, 'bots_pm' => Bool, 'bots_inline' => Bool, 'phone_calls' => Bool, 'groups' => Bool, 'channels' => Bool, 'offset' => int, 'limit' => int, 'hash' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.getTopPeers`

Parameters:

correspondents - Json encoded Bool

bots_pm - Json encoded Bool

bots_inline - Json encoded Bool

phone_calls - Json encoded Bool

groups - Json encoded Bool

channels - Json encoded Bool

offset - Json encoded int

limit - Json encoded int

hash - Json encoded int




Or, if you're into Lua:

```
contacts_TopPeers = contacts.getTopPeers({correspondents=Bool, bots_pm=Bool, bots_inline=Bool, phone_calls=Bool, groups=Bool, channels=Bool, offset=int, limit=int, hash=int, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|TYPES_EMPTY|The types field is empty|


