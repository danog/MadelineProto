---
title: checkChatInviteLink
description: Checks chat invite link for validness and returns information about the corresponding chat
---
## Method: checkChatInviteLink  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Checks chat invite link for validness and returns information about the corresponding chat

### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|invite\_link|[string](../types/string.md) | Yes|Invite link to check. Should begin with "https: telegram.me/joinchat/"|


### Return type: [ChatInviteLinkInfo](../types/ChatInviteLinkInfo.md)

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

$ChatInviteLinkInfo = $MadelineProto->checkChatInviteLink(['invite_link' => string, ]);
```

Or, if you're into Lua:

```
ChatInviteLinkInfo = checkChatInviteLink({invite_link=string, })
```

