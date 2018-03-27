---
title: messages.getInlineBotResults
description: Call inline bot
---
## Method: messages.getInlineBotResults  
[Back to methods index](index.md)


Call inline bot

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|bot|[Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Optional|The bot to call|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat where to call the bot|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|The current location|
|query|[string](../types/string.md) | Yes|The query to send to the bot|
|offset|[string](../types/string.md) | Yes|The offset to send to the bot|


### Return type: [messages\_BotResults](../types/messages_BotResults.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_BotResults = $MadelineProto->messages->getInlineBotResults(['bot' => InputUser, 'peer' => InputPeer, 'geo_point' => InputGeoPoint, 'query' => 'string', 'offset' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getInlineBotResults`

Parameters:

bot - Json encoded InputUser

peer - Json encoded InputPeer

geo_point - Json encoded InputGeoPoint

query - Json encoded string

offset - Json encoded string




Or, if you're into Lua:

```
messages_BotResults = messages.getInlineBotResults({bot=InputUser, peer=InputPeer, geo_point=InputGeoPoint, query='string', offset='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_INLINE_DISABLED|This bot can't be used in inline mode|
|BOT_INVALID|This is not a valid bot|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|Timeout|A timeout occurred while fetching data from the bot|


