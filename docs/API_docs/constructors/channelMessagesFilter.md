## Constructor: channelMessagesFilter  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|exclude\_new\_messages|[Bool](../types/Bool.md) | Optional|
|ranges|Array of [MessageRange](../types/MessageRange.md) | Required|


### Type: [ChannelMessagesFilter](../types/ChannelMessagesFilter.md)

### Example:


```
$channelMessagesFilter = ['_' => channelMessagesFilter', 'exclude_new_messages' => true, 'ranges' => [Vector t], ];
```