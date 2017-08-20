---
title: inputMessageVenue
description: Message with information about venue
---
## Constructor: inputMessageVenue  
[Back to constructors index](index.md)



Message with information about venue

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|venue|[venue](../types/venue.md) | Yes|Venue to send|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageVenue = ['_' => 'inputMessageVenue', 'venue' => venue];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageVenue", "venue": venue}
```


Or, if you're into Lua:  


```
inputMessageVenue={_='inputMessageVenue', venue=venue}

```


