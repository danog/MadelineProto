## Constructor: replyKeyboardMarkup  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|resize|[Bool](../types/Bool.md) | Optional|
|single\_use|[Bool](../types/Bool.md) | Optional|
|selective|[Bool](../types/Bool.md) | Optional|
|rows|Array of [KeyboardButtonRow](../types/KeyboardButtonRow.md) | Required|
### Type: 

[ReplyMarkup](../types/ReplyMarkup.md)
### Example:

```
$replyKeyboardMarkup = ['_' => replyKeyboardMarkup', 'resize' => true, 'single_use' => true, 'selective' => true, 'rows' => [Vector t], ];
```