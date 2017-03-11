---
title: channelMessagesFilter
description: channelMessagesFilter attributes, type and example
---
## Constructor: channelMessagesFilter  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|exclude\_new\_messages|[Bool](../types/Bool.md) | Optional|
|ranges|Array of [MessageRange](../types/MessageRange.md) | Yes|



### Type: [ChannelMessagesFilter](../types/ChannelMessagesFilter.md)


### Example:

```
$channelMessagesFilter = ['_' => 'channelMessagesFilter', 'exclude_new_messages' => true, 'ranges' => [MessageRange], ];
```  

Or, if you're into Lua:  


```
channelMessagesFilter={_='channelMessagesFilter', exclude_new_messages=true, ranges={MessageRange}, }

```


