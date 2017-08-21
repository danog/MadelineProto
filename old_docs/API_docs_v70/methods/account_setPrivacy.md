---
title: account.setPrivacy
description: account.setPrivacy parameters, return type and example
---
## Method: account.setPrivacy  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|key|[InputPrivacyKey](../types/InputPrivacyKey.md) | Yes|
|rules|Array of [InputPrivacyRule](../types/InputPrivacyRule.md) | Yes|


### Return type: [account\_PrivacyRules](../types/account_PrivacyRules.md)

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

$account_PrivacyRules = $MadelineProto->account->setPrivacy(['key' => InputPrivacyKey, 'rules' => [InputPrivacyRule], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - account.setPrivacy
* params - `{"key": InputPrivacyKey, "rules": [InputPrivacyRule], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setPrivacy`

Parameters:

key - Json encoded InputPrivacyKey

rules - Json encoded  array of InputPrivacyRule




Or, if you're into Lua:

```
account_PrivacyRules = account.setPrivacy({key=InputPrivacyKey, rules={InputPrivacyRule}, })
```

