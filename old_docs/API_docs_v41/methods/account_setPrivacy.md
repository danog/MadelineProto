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

$account_PrivacyRules = $MadelineProto->account->setPrivacy(['key' => InputPrivacyKey, 'rules' => [InputPrivacyRule], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/account.setPrivacy`

Parameters:

key - Json encoded InputPrivacyKey

rules - Json encoded  array of InputPrivacyRule




Or, if you're into Lua:

```
account_PrivacyRules = account.setPrivacy({key=InputPrivacyKey, rules={InputPrivacyRule}, })
```

