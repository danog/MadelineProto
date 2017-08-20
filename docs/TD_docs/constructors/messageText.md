---
title: messageText
description: Text message
---
## Constructor: messageText  
[Back to constructors index](index.md)



Text message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|text|[string](../types/string.md) | Yes|Text of the message|
|entities|Array of [MessageEntity](../constructors/MessageEntity.md) | Yes|Entities contained in the text|
|web\_page|[webPage](../types/webPage.md) | Yes|Preview of a web page mentioned in the text, nullable|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageText = ['_' => 'messageText', 'text' => 'string', 'entities' => [MessageEntity], 'web_page' => webPage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageText", "text": "string", "entities": [MessageEntity], "web_page": webPage}
```


Or, if you're into Lua:  


```
messageText={_='messageText', text='string', entities={MessageEntity}, web_page=webPage}

```


