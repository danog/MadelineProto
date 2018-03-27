---
title: account.setPrivacy
description: Set privacy settings
---
## Method: account.setPrivacy  
[Back to methods index](index.md)


Set privacy settings

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|key|[InputPrivacyKey](../types/InputPrivacyKey.md) | Yes|Privacy setting|
|rules|Array of [InputPrivacyRule](../types/InputPrivacyRule.md) | Yes|Privacy settings|


### Return type: [account\_PrivacyRules](../types/account_PrivacyRules.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$account_PrivacyRules = $MadelineProto->account->setPrivacy(['key' => InputPrivacyKey, 'rules' => [InputPrivacyRule, InputPrivacyRule], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setPrivacy`

Parameters:

key - Json encoded InputPrivacyKey

rules - Json encoded  array of InputPrivacyRule




Or, if you're into Lua:

```
account_PrivacyRules = account.setPrivacy({key=InputPrivacyKey, rules={InputPrivacyRule}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PRIVACY_KEY_INVALID|The privacy key is invalid|


