---
title: complete_phone_login
description: complete_phone_login parameters, return type and example
---
## Method: complete\_phone\_login  


### Parameters:

| Name     |    Type       |
|----------|---------------|
|code| A string with the phone code|

### Return type: [auth.Authorization](API_docs/types/auth_Authorization.md) or [account.Password](http://docs.madelineproto.xyz/API_docs/types/account_Password.html) or `['_' => 'account.needSignup']`

You must then use [complete_2FA_login](complete_2FA_login.md) or [complete_signup](complete_signup.md) to login or signup, or simply start using `$MadelineProto` if the result is a `auth.Authorization` object.

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
