---
title: getPrivacy
description: Returns current privacy settings
---
## Method: getPrivacy  
[Back to methods index](index.md)


Returns current privacy settings

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|key|[PrivacyKey](../types/PrivacyKey.md) | Yes|Privacy key|


### Return type: [PrivacyRules](../types/PrivacyRules.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$PrivacyRules = $MadelineProto->getPrivacy(['key' => PrivacyKey, ]);
```

Or, if you're into Lua:

```
PrivacyRules = getPrivacy({key=PrivacyKey, })
```

