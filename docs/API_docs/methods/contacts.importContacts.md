## Method: contacts.importContacts  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|contacts|Array of [InputContact](../types/InputContact.md) | Required|
|replace|[Bool](../types/Bool.md) | Required|


### Return type: [contacts\_ImportedContacts](../types/contacts_ImportedContacts.md)

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

$contacts_ImportedContacts = $MadelineProto->contacts->importContacts(['contacts' => [InputContact], 'replace' => Bool, ]);
```