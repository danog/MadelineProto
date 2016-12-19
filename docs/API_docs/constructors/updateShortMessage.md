## Constructor: updateShortMessage  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|out|[Bool](../types/Bool.md) | Optional|
|mentioned|[Bool](../types/Bool.md) | Optional|
|media\_unread|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|message|[string](../types/string.md) | Required|
|pts|[int](../types/int.md) | Required|
|pts\_count|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|fwd\_from|[MessageFwdHeader](../types/MessageFwdHeader.md) | Optional|
|via\_bot\_id|[int](../types/int.md) | Optional|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortMessage = ['_' => updateShortMessage', 'out' => true, 'mentioned' => true, 'media_unread' => true, 'silent' => true, 'id' => int, 'user_id' => int, 'message' => string, 'pts' => int, 'pts_count' => int, 'date' => int, 'fwd_from' => MessageFwdHeader, 'via_bot_id' => int, 'reply_to_msg_id' => int, 'entities' => [Vector t], ];
```