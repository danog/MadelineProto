---
title: resetAuth
description: Logs out user. If force == false, begins to perform soft log out, returns authStateLoggingOut after completion. If force == true then succeeds almost immediately without cleaning anything at the server, but returns error with code 401 and description "Unauthorized"
---
## Method: resetAuth  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Logs out user. If force == false, begins to perform soft log out, returns authStateLoggingOut after completion. If force == true then succeeds almost immediately without cleaning anything at the server, but returns error with code 401 and description "Unauthorized"

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|force|[Bool](../types/Bool.md) | Yes|If true, just delete all local data. Session will remain in list of active sessions|


### Return type: [AuthState](../types/AuthState.md)

