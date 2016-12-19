## Method: contacts.deleteContact  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[InputUser](../types/InputUser.md) | Required|


### Return type: [contacts\_Link](../types/contacts_Link.md)

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

$contacts_Link = $MadelineProto->contacts->deleteContact(['id' => InputUser, ]);
```