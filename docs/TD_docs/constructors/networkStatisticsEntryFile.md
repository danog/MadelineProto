---
title: networkStatisticsEntryFile
description: Contains information about total received and sent files data
---
## Constructor: networkStatisticsEntryFile  
[Back to constructors index](index.md)



Contains information about total received and sent files data

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|file\_type|[FileType](../types/FileType.md) | Yes|Type of a file the data is part of|
|network\_type|[NetworkType](../types/NetworkType.md) | Yes|Type of a network the data was sent through. Call setNetworkType to maintain actual network type|
|sent\_bytes|[int53](../types/int53.md) | Yes|Total number of sent bytes|
|received\_bytes|[int53](../types/int53.md) | Yes|Total number of received bytes|



### Type: [NetworkStatisticsEntry](../types/NetworkStatisticsEntry.md)


