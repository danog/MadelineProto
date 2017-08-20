---
title: draftMessage
description: Contains information about draft of a message
---
## Constructor: draftMessage  
[Back to constructors index](index.md)



Contains information about draft of a message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|reply\_to\_message\_id|[long](../types/long.md) | Yes|Identifier of a message to reply to or 0|
|input\_message\_text|[InputMessageContent](../types/InputMessageContent.md) | Yes|Content of a draft message, always should be of a type inputMessageText|



### Type: [DraftMessage](../types/DraftMessage.md)


### Example:

```
$draftMessage = ['_' => 'draftMessage', 'reply_to_message_id' => long, 'input_message_text' => InputMessageContent];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "draftMessage", "reply_to_message_id": long, "input_message_text": InputMessageContent}
```


Or, if you're into Lua:  


```
draftMessage={_='draftMessage', reply_to_message_id=long, input_message_text=InputMessageContent}

```


