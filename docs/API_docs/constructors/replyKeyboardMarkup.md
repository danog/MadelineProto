## Constructor: replyKeyboardMarkup  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|resize|[Bool](../types/Bool.md) | Optional|
|single\_use|[Bool](../types/Bool.md) | Optional|
|selective|[Bool](../types/Bool.md) | Optional|
|rows|Array of [KeyboardButtonRow](../types/KeyboardButtonRow.md) | Required|


### Type: [ReplyMarkup](../types/ReplyMarkup.md)

### Example:


```
$replyKeyboardMarkup = ['resize' => Bool, 'single_use' => Bool, 'selective' => Bool, 'rows' => [KeyboardButtonRow], ];
```