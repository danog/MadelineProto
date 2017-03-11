---
title: setPrivacy
description: Changes privacy settings
---
## Method: setPrivacy  
[Back to methods index](index.md)


Changes privacy settings

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|key|[PrivacyKey](../types/PrivacyKey.md) | Yes|Privacy key|
|rules|[privacyRules](../types/privacyRules.md) | Yes|New privacy rules|


### Return type: [Ok](../types/Ok.md)

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

$Ok = $MadelineProto->setPrivacy(['key' => PrivacyKey, 'rules' => privacyRules, ]);
```

Or, if you're into Lua:

```
Ok = setPrivacy({key=PrivacyKey, rules=privacyRules, })
```

