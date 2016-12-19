## Method: upload\_saveBigFilePart  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|file\_id|[long](../types/long.md) | Required|
|file\_part|[int](../types/int.md) | Required|
|file\_total\_parts|[int](../types/int.md) | Required|
|bytes|[bytes](../types/bytes.md) | Required|


### Return type: [Bool](../types/Bool.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->upload_saveBigFilePart(['file_id' => long, 'file_part' => int, 'file_total_parts' => int, 'bytes' => bytes, ]);
```