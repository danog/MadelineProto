---
title: http_wait
description: Makes the server send messages waiting in the buffer
---
## Method: http\_wait  
[Back to methods index](index.md)


Makes the server send messages waiting in the buffer

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|max\_delay|[int](../types/int.md) | Yes|Denotes the maximum number of milliseconds that has elapsed between the first message for this session and the transmission of an HTTP response|
|wait\_after|[int](../types/int.md) | Yes|after the receipt of the latest message for a particular session, the server waits another wait_after milliseconds in case there are more messages. If there are no additional messages, the result is transmitted (a container with all the messages).|
|max\_wait|[int](../types/int.md) | Yes|If more messages appear, the wait_after timer is reset.|


### Return type: [HttpWait](../types/HttpWait.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$HttpWait = $MadelineProto->http_wait(['max_delay' => int, 'wait_after' => int, 'max_wait' => int, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - http_wait
* params - `{"max_delay": int, "wait_after": int, "max_wait": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/http_wait`

Parameters:

max_delay - Json encoded int

wait_after - Json encoded int

max_wait - Json encoded int




Or, if you're into Lua:

```
HttpWait = http_wait({max_delay=int, wait_after=int, max_wait=int, })
```

