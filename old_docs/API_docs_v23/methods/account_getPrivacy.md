---
title: account.getPrivacy
description: Get privacy settings
---
## Method: account.getPrivacy  
[Back to methods index](index.md)


Get privacy settings

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|key|[InputPrivacyKey](../types/InputPrivacyKey.md) | Yes|Privacy setting key|


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

$account_PrivacyRules = $MadelineProto->account->getPrivacy(['key' => InputPrivacyKey, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getPrivacy`

Parameters:

key - Json encoded InputPrivacyKey




Or, if you're into Lua:

```
account_PrivacyRules = account.getPrivacy({key=InputPrivacyKey, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PRIVACY_KEY_INVALID|The privacy key is invalid|


