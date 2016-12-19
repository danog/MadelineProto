## Method: contacts.search  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|q|[string](../types/string.md) | Required|
|limit|[int](../types/int.md) | Required|


### Return type: [contacts\_Found](../types/contacts_Found.md)

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

$contacts_Found = $MadelineProto->contacts->search(['q' => string, 'limit' => int, ]);
```