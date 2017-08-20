---
title: setPrivacy
description: Changes privacy settings
---
## Method: setPrivacy  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes privacy settings

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|key|[PrivacyKey](../types/PrivacyKey.md) | Yes|Privacy key|
|rules|[privacyRules](../types/privacyRules.md) | Yes|New privacy rules|


### Return type: [Ok](../types/Ok.md)

### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
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

$Ok = $MadelineProto->setPrivacy(['key' => PrivacyKey, 'rules' => privacyRules, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - setPrivacy
* params - `{"key": PrivacyKey, "rules": privacyRules, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/setPrivacy`

Parameters:

key - Json encoded PrivacyKey

rules - Json encoded privacyRules




Or, if you're into Lua:

```
Ok = setPrivacy({key=PrivacyKey, rules=privacyRules, })
```

