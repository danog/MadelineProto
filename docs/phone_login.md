---
title: phone_login
description: phone_login parameters, return type and example
---
## Method: phone_login  


### Parameters:

| Name     |    Type       |
|----------|---------------|
|number| A string with the phone number, including the country code|

### Return type: [auth.SentCode](API_docs/types/auth_SentCode.md)

You must then use [complete_phone_login](complete_phone_login.md) 


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();

$MadelineProto->phone_login(readline('Enter your phone number: '));
$authorization = $MadelineProto->complete_phone_login(readline('Enter the code you received: '));
if ($authorization['_'] === 'account.noPassword') {
    throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
}
if ($authorization['_'] === 'account.password') {
    $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
}
if ($authorization['_'] === 'account.needSignup') {
    $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
}
```

