---
title: payments.sendPaymentForm
description: payments.sendPaymentForm parameters, return type and example
---
## Method: payments.sendPaymentForm  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_id|[CLICK ME int](../types/int.md) | Yes|
|requested\_info\_id|[CLICK ME string](../types/string.md) | Optional|
|shipping\_option\_id|[CLICK ME string](../types/string.md) | Optional|
|credentials|[CLICK ME InputPaymentCredentials](../types/InputPaymentCredentials.md) | Yes|


### Return type: [payments\_PaymentResult](../types/payments_PaymentResult.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|


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

$payments_PaymentResult = $MadelineProto->payments->sendPaymentForm(['msg_id' => int, 'requested_info_id' => 'string', 'shipping_option_id' => 'string', 'credentials' => InputPaymentCredentials, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.sendPaymentForm`

Parameters:

msg_id - Json encoded int

requested_info_id - Json encoded string

shipping_option_id - Json encoded string

credentials - Json encoded InputPaymentCredentials




Or, if you're into Lua:

```
payments_PaymentResult = payments.sendPaymentForm({msg_id=int, requested_info_id='string', shipping_option_id='string', credentials=InputPaymentCredentials, })
```

