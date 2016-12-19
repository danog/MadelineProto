## Constructor: updates\_channelDifference  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|final|[Bool](../types/Bool.md) | Optional|
|pts|[int](../types/int.md) | Required|
|timeout|[int](../types/int.md) | Optional|
|new\_messages|Array of [Message](../types/Message.md) | Required|
|other\_updates|Array of [Update](../types/Update.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|users|Array of [User](../types/User.md) | Required|


### Type: [updates\_ChannelDifference](../types/updates\_ChannelDifference.md)

### Example:


```
$updates_channelDifference = ['final' => Bool, 'pts' => int, 'timeout' => int, 'new_messages' => [Message], 'other_updates' => [Update], 'chats' => [Chat], 'users' => [User], ];
```