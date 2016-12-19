## Constructor: updateContactLink  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|my\_link|[ContactLink](../types/ContactLink.md) | Required|
|foreign\_link|[ContactLink](../types/ContactLink.md) | Required|
### Type: 

[Update](../types/Update.md)
### Example:

```
$updateContactLink = ['_' => updateContactLink', 'user_id' => int, 'my_link' => ContactLink, 'foreign_link' => ContactLink, ];
```