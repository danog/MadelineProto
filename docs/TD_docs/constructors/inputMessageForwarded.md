---
title: inputMessageForwarded
description: Forwarded message
---
## Constructor: inputMessageForwarded  
[Back to constructors index](index.md)



Forwarded message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|from\_chat\_id|[long](../types/long.md) | Yes|Chat identifier of the message to forward|
|message\_id|[long](../types/long.md) | Yes|Identifier of the message to forward|
|in\_game\_share|[Bool](../types/Bool.md) | Yes|Pass true to share a game message within a launched game, for Game messages only|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageForwarded = ['_' => 'inputMessageForwarded', 'from_chat_id' => long, 'message_id' => long, 'in_game_share' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageForwarded", "from_chat_id": long, "message_id": long, "in_game_share": Bool}
```


Or, if you're into Lua:  


```
inputMessageForwarded={_='inputMessageForwarded', from_chat_id=long, message_id=long, in_game_share=Bool}

```


