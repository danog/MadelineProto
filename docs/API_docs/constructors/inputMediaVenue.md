## Constructor: inputMediaVenue  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Required|
|title|[string](../types/string.md) | Required|
|address|[string](../types/string.md) | Required|
|provider|[string](../types/string.md) | Required|
|venue\_id|[string](../types/string.md) | Required|


### Type: [InputMedia](../types/InputMedia.md)

### Example:


```
$inputMediaVenue = ['geo_point' => InputGeoPoint, 'title' => string, 'address' => string, 'provider' => string, 'venue_id' => string, ];
```