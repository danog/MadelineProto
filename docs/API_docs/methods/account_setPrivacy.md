## Method: account\_setPrivacy  

### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|key|[InputPrivacyKey](../types/InputPrivacyKey.md) | Required|
|rules|Array of [InputPrivacyRule](../types/InputPrivacyRule.md) | Required|


### Return type: [account\_PrivacyRules](../types/account_PrivacyRules.md)

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

$account_PrivacyRules = $MadelineProto->account_setPrivacy(['key' => InputPrivacyKey, 'rules' => [InputPrivacyRule], ]);
```