## Constructor: botInlineMessageMediaContact  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|phone\_number|[string](../types/string.md) | Required|
|first\_name|[string](../types/string.md) | Required|
|last\_name|[string](../types/string.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|


### Type: [BotInlineMessage](../types/BotInlineMessage.md)

### Example:


```
$botInlineMessageMediaContact = ['_' => botInlineMessageMediaContact', 'phone_number' => string, 'first_name' => string, 'last_name' => string, 'reply_markup' => ReplyMarkup, ];
```