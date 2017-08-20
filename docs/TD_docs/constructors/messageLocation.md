---
title: messageLocation
description: Message with location
---
## Constructor: messageLocation  
[Back to constructors index](index.md)



Message with location

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|location|[location](../types/location.md) | Yes|Message content|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageLocation = ['_' => 'messageLocation', 'location' => location];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageLocation", "location": location}
```


Or, if you're into Lua:  


```
messageLocation={_='messageLocation', location=location}

```


