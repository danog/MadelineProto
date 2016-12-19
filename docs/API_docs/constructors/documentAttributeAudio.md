## Constructor: documentAttributeAudio  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|voice|[Bool](../types/Bool.md) | Optional|
|duration|[int](../types/int.md) | Required|
|title|[string](../types/string.md) | Optional|
|performer|[string](../types/string.md) | Optional|
|waveform|[bytes](../types/bytes.md) | Optional|
### Type: 

[DocumentAttribute](../types/DocumentAttribute.md)
### Example:

```
$documentAttributeAudio = ['_' => documentAttributeAudio', 'voice' => true, 'duration' => int, 'title' => string, 'performer' => string, 'waveform' => bytes, ];
```