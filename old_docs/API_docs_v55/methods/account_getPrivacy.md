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
|key|[CLICK ME InputPrivacyKey](../types/InputPrivacyKey.md) | Yes|Privacy setting key|


### Return type: [account\_PrivacyRules](../types/account_PrivacyRules.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PRIVACY_KEY_INVALID|The privacy key is invalid|


### Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$account_PrivacyRules = $MadelineProto->account->getPrivacy(['key' => InputPrivacyKey, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.getPrivacy`

Parameters:

key - Json encoded InputPrivacyKey




Or, if you're into Lua:

```
account_PrivacyRules = account.getPrivacy({key=InputPrivacyKey, })
```

