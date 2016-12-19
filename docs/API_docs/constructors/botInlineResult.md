## Constructor: botInlineResult  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[string](../types/string.md) | Required|
|type|[string](../types/string.md) | Required|
|title|[string](../types/string.md) | Optional|
|description|[string](../types/string.md) | Optional|
|url|[string](../types/string.md) | Optional|
|thumb\_url|[string](../types/string.md) | Optional|
|content\_url|[string](../types/string.md) | Optional|
|content\_type|[string](../types/string.md) | Optional|
|w|[int](../types/int.md) | Optional|
|h|[int](../types/int.md) | Optional|
|duration|[int](../types/int.md) | Optional|
|send\_message|[BotInlineMessage](../types/BotInlineMessage.md) | Required|



### Type: [BotInlineResult](../types/BotInlineResult.md)


### Example:

```
$botInlineResult = ['_' => botInlineResult', 'id' => string, 'type' => string, 'title' => string, 'description' => string, 'url' => string, 'thumb_url' => string, 'content_url' => string, 'content_type' => string, 'w' => int, 'h' => int, 'duration' => int, 'send_message' => BotInlineMessage, ];
```