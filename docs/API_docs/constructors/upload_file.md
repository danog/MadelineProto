## Constructor: upload\_file  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|type|[storage\_FileType](../types/storage\_FileType.md) | Required|
|mtime|[int](../types/int.md) | Required|
|bytes|[bytes](../types/bytes.md) | Required|


### Type: [upload\_File](../types/upload\_File.md)

### Example:


```
$upload_file = ['type' => storage_FileType, 'mtime' => int, 'bytes' => bytes, ];
```