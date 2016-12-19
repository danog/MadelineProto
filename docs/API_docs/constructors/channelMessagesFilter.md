## Constructor: channelMessagesFilter  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|exclude\_new\_messages|[Bool](../types/Bool.md) | Optional|
|ranges|Array of [MessageRange](../types/MessageRange.md) | Required|


### Type: [ChannelMessagesFilter](../types/ChannelMessagesFilter.md)

### Example:


```
$channelMessagesFilter = ['exclude_new_messages' => Bool, 'ranges' => [MessageRange], ];
```