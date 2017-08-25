---
title: Methods
description: List of methods
---
# Methods  
[Back to API documentation index](..)


$MadelineProto->[logout](https://docs.madelineproto.xyz/logout.html)();

$MadelineProto->[phone_login](https://docs.madelineproto.xyz/phone_login.html)($number);

$MadelineProto->[complete_phone_login](https://docs.madelineproto.xyz/complete_phone_login.html)($code);

$MadelineProto->[complete_2FA_login](https://docs.madelineproto.xyz/complete_2FA_login.html)($password);

$MadelineProto->[bot_login](https://docs.madelineproto.xyz/complete_phone_login.html)($token);


$MadelineProto->[get_dialogs](https://docs.madelineproto.xyz/get_dialogs.html)();

$MadelineProto->[get_pwr_chat](https://docs.madelineproto.xyz/get_pwr_chat.html)($id);

$MadelineProto->[get_info](https://docs.madelineproto.xyz/get_info.html)($id);

$MadelineProto->[get_full_info](https://docs.madelineproto.xyz/get_full_info.html)($id);

$MadelineProto->[get_self](https://docs.madelineproto.xyz/get_self.html)();


***
<br><br>$MadelineProto->[destroy_session](destroy_session.md)(\['session_id' => [long](../types/long.md), \]) === [$DestroySessionRes](../types/DestroySessionRes.md)<a name="destroy_session"></a>  

***
<br><br>$MadelineProto->[get_future_salts](get_future_salts.md)(\['num' => [int](../types/int.md), \]) === [$FutureSalts](../types/FutureSalts.md)<a name="get_future_salts"></a>  

***
<br><br>$MadelineProto->[http_wait](http_wait.md)(\['max_delay' => [int](../types/int.md), 'wait_after' => [int](../types/int.md), 'max_wait' => [int](../types/int.md), \]) === [$HttpWait](../types/HttpWait.md)<a name="http_wait"></a>  

***
<br><br>$MadelineProto->[ping](ping.md)(\['ping_id' => [long](../types/long.md), \]) === [$Pong](../types/Pong.md)<a name="ping"></a>  

$MadelineProto->[ping_delay_disconnect](ping_delay_disconnect.md)(\['ping_id' => [long](../types/long.md), 'disconnect_delay' => [int](../types/int.md), \]) === [$Pong](../types/Pong.md)<a name="ping_delay_disconnect"></a>  

***
<br><br>$MadelineProto->[req_DH_params](req_DH_params.md)(\['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'p' => [bytes](../types/bytes.md), 'q' => [bytes](../types/bytes.md), 'public_key_fingerprint' => [long](../types/long.md), 'encrypted_data' => [bytes](../types/bytes.md), \]) === [$Server\_DH\_Params](../types/Server_DH_Params.md)<a name="req_DH_params"></a>  

$MadelineProto->[req_pq](req_pq.md)(\['nonce' => [int128](../types/int128.md), \]) === [$ResPQ](../types/ResPQ.md)<a name="req_pq"></a>  

***
<br><br>$MadelineProto->[rpc_drop_answer](rpc_drop_answer.md)(\['req_msg_id' => [long](../types/long.md), \]) === [$RpcDropAnswer](../types/RpcDropAnswer.md)<a name="rpc_drop_answer"></a>  

***
<br><br>$MadelineProto->[set_client_DH_params](set_client_DH_params.md)(\['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'encrypted_data' => [bytes](../types/bytes.md), \]) === [$Set\_client\_DH\_params\_answer](../types/Set_client_DH_params_answer.md)<a name="set_client_DH_params"></a>  

