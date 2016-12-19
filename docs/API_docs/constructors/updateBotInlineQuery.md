## Constructor: updateBotInlineQuery  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|query\_id|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|query|[string](../types/string.md) | Required|
|geo|[GeoPoint](../types/GeoPoint.md) | Optional|
|offset|[string](../types/string.md) | Required|


### Type: [Update](../types/Update.md)

### Example:


```
$updateBotInlineQuery = ['_' => updateBotInlineQuery', 'query_id' => long, 'user_id' => int, 'query' => string, 'geo' => GeoPoint, 'offset' => string, ];
```