---
title: messages.getInlineBotResults
description: messages.getInlineBotResults parameters, return type and example
---
## Method: messages.getInlineBotResults  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|bot|[Username, chat ID or InputUser](../types/InputUser.md) | Optional|
|peer|[Username, chat ID or InputPeer](../types/InputPeer.md) | Optional|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Optional|
|query|[string](../types/string.md) | Yes|
|offset|[string](../types/string.md) | Yes|


### Return type: [messages\_BotResults](../types/messages_BotResults.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_INLINE_DISABLED|This bot can't be used in inline mode|
|BOT_INVALID|This is not a valid bot|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|Timeout|A timeout occurred while fetching data from the bot|


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

$messages_BotResults = $MadelineProto->messages->getInlineBotResults(['bot' => InputUser, 'peer' => InputPeer, 'geo_point' => InputGeoPoint, 'query' => 'string', 'offset' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



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

