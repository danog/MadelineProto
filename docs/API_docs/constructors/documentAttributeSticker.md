## Constructor: documentAttributeSticker  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|mask|[Bool](../types/Bool.md) | Optional|
|alt|[string](../types/string.md) | Required|
|stickerset|[InputStickerSet](../types/InputStickerSet.md) | Required|
|mask\_coords|[MaskCoords](../types/MaskCoords.md) | Optional|



### Type: [DocumentAttribute](../types/DocumentAttribute.md)


### Example:

```
$documentAttributeSticker = ['_' => documentAttributeSticker', 'mask' => true, 'alt' => string, 'stickerset' => InputStickerSet, 'mask_coords' => MaskCoords, ];
```