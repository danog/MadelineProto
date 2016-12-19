## Constructor: updateChatParticipantAdmin  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|chat\_id|[int](../types/int.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|is\_admin|[Bool](../types/Bool.md) | Required|
|version|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatParticipantAdmin = ['_' => updateChatParticipantAdmin', 'chat_id' => int, 'user_id' => int, 'is_admin' => Bool, 'version' => int, ];
```