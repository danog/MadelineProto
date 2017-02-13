---
title: help.getTermsOfService
description: help.getTermsOfService parameters, return type and example
---
## Method: help.getTermsOfService  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|lang\_code|[string](../types/string.md) | Required|


### Return type: [help\_TermsOfService](../types/help_TermsOfService.md)

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

$help_TermsOfService = $MadelineProto->help->getTermsOfService(['lang_code' => string, ]);
```
