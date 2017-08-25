---
title: networkStatisticsEntryCall
description: Contains information about total received and sent calls data
---
## Constructor: networkStatisticsEntryCall  
[Back to constructors index](index.md)



Contains information about total received and sent calls data

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|network\_type|[NetworkType](../types/NetworkType.md) | Yes|Type of a network the data was sent through. Call setNetworkType to maintain actual network type|
|sent\_bytes|[int53](../types/int53.md) | Yes|Total number of sent bytes|
|received\_bytes|[int53](../types/int53.md) | Yes|Total number of received bytes|
|duration|[double](../types/double.md) | Yes|Total calls duration in seconds|



### Type: [NetworkStatisticsEntry](../types/NetworkStatisticsEntry.md)


