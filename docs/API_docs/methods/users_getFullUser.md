---
title: users_getFullUser
description: users_getFullUser parameters, return type and example
---
## Method: users\_getFullUser  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[InputUser](../types/InputUser.md) | Required|


### Return type: [UserFull](../types/UserFull.md)

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

$UserFull = $MadelineProto->users_getFullUser(['id' => InputUser, ]);
```