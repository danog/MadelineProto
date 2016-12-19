## Constructor: inputBotInlineMessageText  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|message|[string](../types/string.md) | Required|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|


### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)

### Example:


```
$inputBotInlineMessageText = ['no_webpage' => Bool, 'message' => string, 'entities' => [MessageEntity], 'reply_markup' => ReplyMarkup, ];
```