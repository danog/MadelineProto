---
title: contacts.deleteContacts
description: Delete multiple contacts
---
## Method: contacts.deleteContacts  
[Back to methods index](index.md)


Delete multiple contacts

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|The contacts to delete|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->contacts->deleteContacts(['id' => [InputUser, InputUser], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/contacts.deleteContacts`

Parameters:

id - Json encoded  array of InputUser




Or, if you're into Lua:

```
Bool = contacts.deleteContacts({id={InputUser}, })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|NEED_MEMBER_INVALID|The provided member is invalid|
|Timeout|A timeout occurred while fetching data from the bot|


