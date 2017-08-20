---
title: messageVenue
description: Message with information about venue
---
## Constructor: messageVenue  
[Back to constructors index](index.md)



Message with information about venue

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|venue|[venue](../types/venue.md) | Yes|Message content|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageVenue = ['_' => 'messageVenue', 'venue' => venue];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageVenue", "venue": venue}
```


Or, if you're into Lua:  


```
messageVenue={_='messageVenue', venue=venue}

```


