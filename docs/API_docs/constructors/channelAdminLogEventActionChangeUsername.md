---
title: channelAdminLogEventActionChangeUsername
description: channelAdminLogEventActionChangeUsername attributes, type and example
---
## Constructor: channelAdminLogEventActionChangeUsername  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|prev\_value|[string](../types/string.md) | Yes|
|new\_value|[string](../types/string.md) | Yes|



### Type: [ChannelAdminLogEventAction](../types/ChannelAdminLogEventAction.md)


### Example:

```
$channelAdminLogEventActionChangeUsername = ['_' => 'channelAdminLogEventActionChangeUsername', 'prev_value' => 'string', 'new_value' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelAdminLogEventActionChangeUsername", "prev_value": "string", "new_value": "string"}
```


Or, if you're into Lua:  


```
channelAdminLogEventActionChangeUsername={_='channelAdminLogEventActionChangeUsername', prev_value='string', new_value='string'}

```


