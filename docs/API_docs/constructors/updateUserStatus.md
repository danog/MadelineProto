## Constructor: updateUserStatus  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|status|[UserStatus](../types/UserStatus.md) | Required|
### Type: 

[Update](../types/Update.md)
### Example:

```
$updateUserStatus = ['_' => updateUserStatus', 'user_id' => int, 'status' => UserStatus, ];
```