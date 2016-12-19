## Method: contacts.getContacts  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|hash|[string](../types/string.md) | Required|


### Return type: [contacts\_Contacts](../types/contacts\_Contacts.md)

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

$contacts_Contacts = $MadelineProto->contacts->getContacts(['hash' => string, ]);
```