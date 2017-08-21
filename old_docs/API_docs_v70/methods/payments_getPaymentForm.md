---
title: payments.getPaymentForm
description: payments.getPaymentForm parameters, return type and example
---
## Method: payments.getPaymentForm  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_id|[int](../types/int.md) | Yes|


### Return type: [payments\_PaymentForm](../types/payments_PaymentForm.md)

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

$payments_PaymentForm = $MadelineProto->payments->getPaymentForm(['msg_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - payments.getPaymentForm
* params - `{"msg_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/payments.getPaymentForm`

Parameters:

msg_id - Json encoded int




Or, if you're into Lua:

```
payments_PaymentForm = payments.getPaymentForm({msg_id=int, })
```

