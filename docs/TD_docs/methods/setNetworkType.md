---
title: setNetworkType
description: Sets current network type. Can be called before authorization. Call to this method forces reopening of all network connections mitigating delay in switching between different networks, so it should be called whenever network is changed even network type remains the same. Network type is used to check if library can use network at all and for collecting detailed network data usage statistics
---
## Method: setNetworkType  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Sets current network type. Can be called before authorization. Call to this method forces reopening of all network connections mitigating delay in switching between different networks, so it should be called whenever network is changed even network type remains the same. Network type is used to check if library can use network at all and for collecting detailed network data usage statistics

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|type|[NetworkType](../types/NetworkType.md) | Yes|New network type, defaults to networkTypeNone|


### Return type: [Ok](../types/Ok.md)

