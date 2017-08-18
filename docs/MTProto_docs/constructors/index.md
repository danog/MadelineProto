---
title: Constructors
description: List of constructors
---
# Constructors  
[Back to API documentation index](..)
***
<br><br>[$MTmessage](../constructors/MTmessage.md) = \['msg_id' => [long](../types/long.md), 'seqno' => [int](../types/int.md), 'bytes' => [int](../types/int.md), 'body' => [Object](../types/Object.md), \];<a name="MTmessage"></a>  

***
<br><br>[$bad\_msg\_notification](../constructors/bad_msg_notification.md) = \['bad_msg_id' => [long](../types/long.md), 'bad_msg_seqno' => [int](../types/int.md), 'error_code' => [int](../types/int.md), \];<a name="bad_msg_notification"></a>  

[$bad\_server\_salt](../constructors/bad_server_salt.md) = \['bad_msg_id' => [long](../types/long.md), 'bad_msg_seqno' => [int](../types/int.md), 'error_code' => [int](../types/int.md), 'new_server_salt' => [long](../types/long.md), \];<a name="bad_server_salt"></a>  

***
<br><br>[$bind\_auth\_key\_inner](../constructors/bind_auth_key_inner.md) = \['nonce' => [long](../types/long.md), 'temp_auth_key_id' => [long](../types/long.md), 'perm_auth_key_id' => [long](../types/long.md), 'temp_session_id' => [long](../types/long.md), 'expires_at' => [int](../types/int.md), \];<a name="bind_auth_key_inner"></a>  

***
<br><br>[$client\_DH\_inner\_data](../constructors/client_DH_inner_data.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'retry_id' => [long](../types/long.md), 'g_b' => [bytes](../types/bytes.md), \];<a name="client_DH_inner_data"></a>  

***
<br><br>[$destroy\_session\_none](../constructors/destroy_session_none.md) = \['session_id' => [long](../types/long.md), \];<a name="destroy_session_none"></a>  

[$destroy\_session\_ok](../constructors/destroy_session_ok.md) = \['session_id' => [long](../types/long.md), \];<a name="destroy_session_ok"></a>  

***
<br><br>[$dh\_gen\_fail](../constructors/dh_gen_fail.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash3' => [int128](../types/int128.md), \];<a name="dh_gen_fail"></a>  

[$dh\_gen\_ok](../constructors/dh_gen_ok.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash1' => [int128](../types/int128.md), \];<a name="dh_gen_ok"></a>  

[$dh\_gen\_retry](../constructors/dh_gen_retry.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash2' => [int128](../types/int128.md), \];<a name="dh_gen_retry"></a>  

***
<br><br>[$future\_salt](../constructors/future_salt.md) = \['valid_since' => [int](../types/int.md), 'valid_until' => [int](../types/int.md), 'salt' => [long](../types/long.md), \];<a name="future_salt"></a>  

[$future\_salts](../constructors/future_salts.md) = \['req_msg_id' => [long](../types/long.md), 'now' => [int](../types/int.md), 'salts' => \[[future\_salt](../constructors/future_salt.md)\], \];<a name="future_salts"></a>  

***
<br><br>[$gzip\_packed](../constructors/gzip_packed.md) = \['packed_data' => [bytes](../types/bytes.md), \];<a name="gzip_packed"></a>  

***
<br><br>[$msg\_container](../constructors/msg_container.md) = \['messages' => \[[MTmessage](../constructors/MTmessage.md)\], \];<a name="msg_container"></a>  

[$msg\_copy](../constructors/msg_copy.md) = \['orig_message' => [MTMessage](../types/MTMessage.md), \];<a name="msg_copy"></a>  

[$msg\_detailed\_info](../constructors/msg_detailed_info.md) = \['msg_id' => [long](../types/long.md), 'answer_msg_id' => [long](../types/long.md), 'bytes' => [int](../types/int.md), 'status' => [int](../types/int.md), \];<a name="msg_detailed_info"></a>  

[$msg\_new\_detailed\_info](../constructors/msg_new_detailed_info.md) = \['answer_msg_id' => [long](../types/long.md), 'bytes' => [int](../types/int.md), 'status' => [int](../types/int.md), \];<a name="msg_new_detailed_info"></a>  

[$msg\_resend\_req](../constructors/msg_resend_req.md) = \['msg_ids' => \[[long](../types/long.md)\], \];<a name="msg_resend_req"></a>  

***
<br><br>[$msgs\_ack](../constructors/msgs_ack.md) = \['msg_ids' => \[[long](../types/long.md)\], \];<a name="msgs_ack"></a>  

[$msgs\_all\_info](../constructors/msgs_all_info.md) = \['msg_ids' => \[[long](../types/long.md)\], 'info' => [bytes](../types/bytes.md), \];<a name="msgs_all_info"></a>  

[$msgs\_state\_info](../constructors/msgs_state_info.md) = \['req_msg_id' => [long](../types/long.md), 'info' => [bytes](../types/bytes.md), \];<a name="msgs_state_info"></a>  

[$msgs\_state\_req](../constructors/msgs_state_req.md) = \['msg_ids' => \[[long](../types/long.md)\], \];<a name="msgs_state_req"></a>  

***
<br><br>[$new\_session\_created](../constructors/new_session_created.md) = \['first_msg_id' => [long](../types/long.md), 'unique_id' => [long](../types/long.md), 'server_salt' => [long](../types/long.md), \];<a name="new_session_created"></a>  

***
<br><br>[$p\_q\_inner\_data](../constructors/p_q_inner_data.md) = \['pq' => [bytes](../types/bytes.md), 'p' => [bytes](../types/bytes.md), 'q' => [bytes](../types/bytes.md), 'nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce' => [int256](../types/int256.md), \];<a name="p_q_inner_data"></a>  

[$p\_q\_inner\_data\_temp](../constructors/p_q_inner_data_temp.md) = \['pq' => [bytes](../types/bytes.md), 'p' => [bytes](../types/bytes.md), 'q' => [bytes](../types/bytes.md), 'nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce' => [int256](../types/int256.md), 'expires_in' => [int](../types/int.md), \];<a name="p_q_inner_data_temp"></a>  

***
<br><br>[$pong](../constructors/pong.md) = \['msg_id' => [long](../types/long.md), 'ping_id' => [long](../types/long.md), \];<a name="pong"></a>  

***
<br><br>[$resPQ](../constructors/resPQ.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'pq' => [bytes](../types/bytes.md), 'server_public_key_fingerprints' => \[[long](../types/long.md)\], \];<a name="resPQ"></a>  

***
<br><br>[$rpc\_answer\_dropped](../constructors/rpc_answer_dropped.md) = \['msg_id' => [long](../types/long.md), 'seq_no' => [int](../types/int.md), 'bytes' => [int](../types/int.md), \];<a name="rpc_answer_dropped"></a>  

[$rpc\_answer\_dropped\_running](../constructors/rpc_answer_dropped_running.md) = \[\];<a name="rpc_answer_dropped_running"></a>  

[$rpc\_answer\_unknown](../constructors/rpc_answer_unknown.md) = \[\];<a name="rpc_answer_unknown"></a>  

[$rpc\_error](../constructors/rpc_error.md) = \['error_code' => [int](../types/int.md), 'error_message' => [string](../types/string.md), \];<a name="rpc_error"></a>  

[$rpc\_result](../constructors/rpc_result.md) = \['req_msg_id' => [long](../types/long.md), 'result' => [Object](../types/Object.md), \];<a name="rpc_result"></a>  

***
<br><br>[$server\_DH\_inner\_data](../constructors/server_DH_inner_data.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'g' => [int](../types/int.md), 'dh_prime' => [bytes](../types/bytes.md), 'g_a' => [bytes](../types/bytes.md), 'server_time' => [int](../types/int.md), \];<a name="server_DH_inner_data"></a>  

[$server\_DH\_params\_fail](../constructors/server_DH_params_fail.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'new_nonce_hash' => [int128](../types/int128.md), \];<a name="server_DH_params_fail"></a>  

[$server\_DH\_params\_ok](../constructors/server_DH_params_ok.md) = \['nonce' => [int128](../types/int128.md), 'server_nonce' => [int128](../types/int128.md), 'encrypted_answer' => [bytes](../types/bytes.md), \];<a name="server_DH_params_ok"></a>  

***
<br><br>[$vector](../constructors/vector.md) = \[\];<a name="vector"></a>  

