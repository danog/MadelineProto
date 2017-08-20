---
title: config
description: config attributes, type and example
---
## Constructor: config  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phonecalls\_enabled|[Bool](../types/Bool.md) | Optional|
|date|[int](../types/int.md) | Yes|
|expires|[int](../types/int.md) | Yes|
|test\_mode|[Bool](../types/Bool.md) | Yes|
|this\_dc|[int](../types/int.md) | Yes|
|dc\_options|Array of [DcOption](../types/DcOption.md) | Yes|
|chat\_size\_max|[int](../types/int.md) | Yes|
|megagroup\_size\_max|[int](../types/int.md) | Yes|
|forwarded\_count\_max|[int](../types/int.md) | Yes|
|online\_update\_period\_ms|[int](../types/int.md) | Yes|
|offline\_blur\_timeout\_ms|[int](../types/int.md) | Yes|
|offline\_idle\_timeout\_ms|[int](../types/int.md) | Yes|
|online\_cloud\_timeout\_ms|[int](../types/int.md) | Yes|
|notify\_cloud\_delay\_ms|[int](../types/int.md) | Yes|
|notify\_default\_delay\_ms|[int](../types/int.md) | Yes|
|chat\_big\_size|[int](../types/int.md) | Yes|
|push\_chat\_period\_ms|[int](../types/int.md) | Yes|
|push\_chat\_limit|[int](../types/int.md) | Yes|
|saved\_gifs\_limit|[int](../types/int.md) | Yes|
|edit\_time\_limit|[int](../types/int.md) | Yes|
|rating\_e\_decay|[int](../types/int.md) | Yes|
|stickers\_recent\_limit|[int](../types/int.md) | Yes|
|tmp\_sessions|[int](../types/int.md) | Optional|
|pinned\_dialogs\_count\_max|[int](../types/int.md) | Yes|
|call\_receive\_timeout\_ms|[int](../types/int.md) | Yes|
|call\_ring\_timeout\_ms|[int](../types/int.md) | Yes|
|call\_connect\_timeout\_ms|[int](../types/int.md) | Yes|
|call\_packet\_timeout\_ms|[int](../types/int.md) | Yes|
|me\_url\_prefix|[string](../types/string.md) | Yes|
|disabled\_features|Array of [DisabledFeature](../types/DisabledFeature.md) | Yes|



### Type: [Config](../types/Config.md)


### Example:

```
$config = ['_' => 'config', 'phonecalls_enabled' => Bool, 'date' => int, 'expires' => int, 'test_mode' => Bool, 'this_dc' => int, 'dc_options' => [DcOption], 'chat_size_max' => int, 'megagroup_size_max' => int, 'forwarded_count_max' => int, 'online_update_period_ms' => int, 'offline_blur_timeout_ms' => int, 'offline_idle_timeout_ms' => int, 'online_cloud_timeout_ms' => int, 'notify_cloud_delay_ms' => int, 'notify_default_delay_ms' => int, 'chat_big_size' => int, 'push_chat_period_ms' => int, 'push_chat_limit' => int, 'saved_gifs_limit' => int, 'edit_time_limit' => int, 'rating_e_decay' => int, 'stickers_recent_limit' => int, 'tmp_sessions' => int, 'pinned_dialogs_count_max' => int, 'call_receive_timeout_ms' => int, 'call_ring_timeout_ms' => int, 'call_connect_timeout_ms' => int, 'call_packet_timeout_ms' => int, 'me_url_prefix' => 'string', 'disabled_features' => [DisabledFeature]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "config", "phonecalls_enabled": Bool, "date": int, "expires": int, "test_mode": Bool, "this_dc": int, "dc_options": [DcOption], "chat_size_max": int, "megagroup_size_max": int, "forwarded_count_max": int, "online_update_period_ms": int, "offline_blur_timeout_ms": int, "offline_idle_timeout_ms": int, "online_cloud_timeout_ms": int, "notify_cloud_delay_ms": int, "notify_default_delay_ms": int, "chat_big_size": int, "push_chat_period_ms": int, "push_chat_limit": int, "saved_gifs_limit": int, "edit_time_limit": int, "rating_e_decay": int, "stickers_recent_limit": int, "tmp_sessions": int, "pinned_dialogs_count_max": int, "call_receive_timeout_ms": int, "call_ring_timeout_ms": int, "call_connect_timeout_ms": int, "call_packet_timeout_ms": int, "me_url_prefix": "string", "disabled_features": [DisabledFeature]}
```


Or, if you're into Lua:  


```
config={_='config', phonecalls_enabled=Bool, date=int, expires=int, test_mode=Bool, this_dc=int, dc_options={DcOption}, chat_size_max=int, megagroup_size_max=int, forwarded_count_max=int, online_update_period_ms=int, offline_blur_timeout_ms=int, offline_idle_timeout_ms=int, online_cloud_timeout_ms=int, notify_cloud_delay_ms=int, notify_default_delay_ms=int, chat_big_size=int, push_chat_period_ms=int, push_chat_limit=int, saved_gifs_limit=int, edit_time_limit=int, rating_e_decay=int, stickers_recent_limit=int, tmp_sessions=int, pinned_dialogs_count_max=int, call_receive_timeout_ms=int, call_ring_timeout_ms=int, call_connect_timeout_ms=int, call_packet_timeout_ms=int, me_url_prefix='string', disabled_features={DisabledFeature}}

```


