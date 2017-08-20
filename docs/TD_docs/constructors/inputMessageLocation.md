---
title: inputMessageLocation
description: Message with location
---
## Constructor: inputMessageLocation  
[Back to constructors index](index.md)



Message with location

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|location|[location](../types/location.md) | Yes|Location to send|



### Type: [InputMessageContent](../types/InputMessageContent.md)


### Example:

```
$inputMessageLocation = ['_' => 'inputMessageLocation', 'location' => location];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageLocation", "location": location}
```


Or, if you're into Lua:  


```
inputMessageLocation={_='inputMessageLocation', location=location}

```


