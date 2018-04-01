# Getting info about chats

There are various methods that can be used to fetch info about chats, based on bot API id, tg-cli ID, Peer, User, Chat objects.

* [Full chat info with full list of participants](#get_pwr_chat)
* [Full chat info](#get_full_info)
* [Reduced chat info (very fast)](#get_info)

## get_pwr_chat
```php
$pwr_chat = $MadelineProto->get_pwr_chat(-100214891824);
foreach ($pwr_chat['participants'] as $participant) {
    \danog\MadelineProto\Logger::log($participant);
}
```

Use `get_pwr_chat` to get full chat info, including the full list of members, see [here for the parameters and the result](https://docs.madelineproto.xyz/get_pwr_chat.html).  

* Completeness: full
* Speed: medium
* Caching: medium

## get_full_info
```php
$full_chat = $MadelineProto->get_full_info(-10028941842);
```

You can also use `get_full_info` to get full chat info, without the full list of members, see [here for the parameters and the result](https://docs.madelineproto.xyz/get_full_info.html).  

* Completeness: medium
* Speed: medium-fast
* Caching: full

## get_info
```php
$chat = $MadelineProto->get_info(-10028941842);
```

You can also use `get_info` to get chat info, see [here for the parameters and the result](https://docs.madelineproto.xyz/get_info.html)

* Completeness: small
* Speed: very fast
* Caching: full

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/FILES.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/DIALOGS.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>