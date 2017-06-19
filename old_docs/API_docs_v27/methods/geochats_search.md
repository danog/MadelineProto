---
title: geochats.search
description: geochats.search parameters, return type and example
---
## Method: geochats.search  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|
|q|[string](../types/string.md) | Yes|
|filter|[MessagesFilter](../types/MessagesFilter.md) | Yes|
|min\_date|[int](../types/int.md) | Yes|
|max\_date|[int](../types/int.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [geochats\_Messages](../types/geochats_Messages.md)

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

$geochats_Messages = $MadelineProto->geochats->search(['peer' => InputGeoChat, 'q' => string, 'filter' => MessagesFilter, 'min_date' => int, 'max_date' => int, 'offset' => int, 'max_id' => int, 'limit' => int, ]);
```

Or, if you're into Lua:

```
geochats_Messages = geochats.search({peer=InputGeoChat, q=string, filter=MessagesFilter, min_date=int, max_date=int, offset=int, max_id=int, limit=int, })
```

