---
title: config
description: config attributes, type and example
---
## Constructor: config  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|date|[int](../types/int.md) | Required|
|expires|[int](../types/int.md) | Required|
|test\_mode|[Bool](../types/Bool.md) | Required|
|this\_dc|[int](../types/int.md) | Required|
|dc\_options|Array of [DcOption](../types/DcOption.md) | Required|
|chat\_size\_max|[int](../types/int.md) | Required|
|megagroup\_size\_max|[int](../types/int.md) | Required|
|forwarded\_count\_max|[int](../types/int.md) | Required|
|online\_update\_period\_ms|[int](../types/int.md) | Required|
|offline\_blur\_timeout\_ms|[int](../types/int.md) | Required|
|offline\_idle\_timeout\_ms|[int](../types/int.md) | Required|
|online\_cloud\_timeout\_ms|[int](../types/int.md) | Required|
|notify\_cloud\_delay\_ms|[int](../types/int.md) | Required|
|notify\_default\_delay\_ms|[int](../types/int.md) | Required|
|chat\_big\_size|[int](../types/int.md) | Required|
|push\_chat\_period\_ms|[int](../types/int.md) | Required|
|push\_chat\_limit|[int](../types/int.md) | Required|
|saved\_gifs\_limit|[int](../types/int.md) | Required|
|edit\_time\_limit|[int](../types/int.md) | Required|
|rating\_e\_decay|[int](../types/int.md) | Required|
|stickers\_recent\_limit|[int](../types/int.md) | Required|
|tmp\_sessions|[int](../types/int.md) | Optional|
|disabled\_features|Array of [DisabledFeature](../types/DisabledFeature.md) | Required|



### Type: [Config](../types/Config.md)


### Example:

```
$config = ['_' => 'config', 'date' => int, 'expires' => int, 'test_mode' => Bool, 'this_dc' => int, 'dc_options' => [Vector t], 'chat_size_max' => int, 'megagroup_size_max' => int, 'forwarded_count_max' => int, 'online_update_period_ms' => int, 'offline_blur_timeout_ms' => int, 'offline_idle_timeout_ms' => int, 'online_cloud_timeout_ms' => int, 'notify_cloud_delay_ms' => int, 'notify_default_delay_ms' => int, 'chat_big_size' => int, 'push_chat_period_ms' => int, 'push_chat_limit' => int, 'saved_gifs_limit' => int, 'edit_time_limit' => int, 'rating_e_decay' => int, 'stickers_recent_limit' => int, 'tmp_sessions' => int, 'disabled_features' => [Vector t], ];
```  

