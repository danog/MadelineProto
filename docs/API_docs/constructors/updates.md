## Constructor: updates  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|updates|Array of [Update](../types/Update.md) | Required|
|users|Array of [User](../types/User.md) | Required|
|chats|Array of [Chat](../types/Chat.md) | Required|
|date|[int](../types/int.md) | Required|
|seq|[int](../types/int.md) | Required|


### Type: [Updates](../types/Updates.md)

### Example:


```
$updates = ['updates' => [Update], 'users' => [User], 'chats' => [Chat], 'date' => int, 'seq' => int, ];
```