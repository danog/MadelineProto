---
title: changeChatReportSpamState
description: Reports chat as a spam chat or as not a spam chat. Can be used only if ChatReportSpamState.can_report_spam is true. After this request ChatReportSpamState.can_report_spam became false forever
---
## Method: changeChatReportSpamState  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Reports chat as a spam chat or as not a spam chat. Can be used only if ChatReportSpamState.can_report_spam is true. After this request ChatReportSpamState.can_report_spam became false forever

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|is\_spam\_chat|[Bool](../types/Bool.md) | Yes|If true, chat will be reported as a spam chat, otherwise it will be marked as not a spam chat|


### Return type: [Ok](../types/Ok.md)

