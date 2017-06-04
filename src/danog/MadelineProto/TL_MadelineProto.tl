MadelineProto.main flags:# encrypted_layer:int settings:MadelineProto.settings config:Config authorization:flags.0?auth.Authorization authorized:int rsa_keys:Vector<MadelineProto.RSA> last_recv:int dh_config:messages.DhConfig chats:Vector<Chat> last_stored:int qres:Vector<PWR.Chat> pending_updates:Vector<Update> updates_state:MadelineProto.Updates_state got_state:flags.1?true channels_state:MadelineProto.Channel_states updates:Vector<Update> updates_key:int getting_state:flags.2?true full_chats:MadelineProto.FullChat msg_ids:Vector<long> dialog_params:MadelineProto.DialogParams datacenter:MadelineProto.DataCenter v:int constructors:MadelineProto.Constructors td_constructors:flags.3?MadelineProto.Constructors methods:MadelineProto.Methods td_methods:flags.4?MadelineProto.Methods td_descriptions:flags.5?MadelineProto.Descriptions twoe1984:Serialized twoe2047:Serialized twoe2048:Serialized zero:Serialized one:Serialized two:Serialized three:Serialized four:Serialized = MadelineProto.Main;
MadelineProto.settings = MadelineProto.Settings;
MadelineProto.connection_settings = MadelineProto.Settings;
MadelineProto.channel_states = pts:int pending_pts_updates:Vector<Update> sync_loading:Bool MadelineProto.Channel_states;
MadelineProto.update_state = pts:int qts:int seq:int date:int pending_seq_updates:Vector<Update> pending_pts_updates:Vector<Update> sync_loading:Bool MadelineProto.Update_state;

MadelineProto.rsa e:Serialized n:Serialized fp:string = MadelineProto.RSA;
MadelineProto.dialogParams flags:# exclude_pinned:flags.0?true offset_date:int offset_id:int offset_peer:InputPeer limit:int = MadelineProto.DialogParams;
MadelineProto.dataCenter sockets:Vector<MadelineProto.Connection> curdc:int dclist:Vector<dcOption> settings:MadelineProto.Settings = MadelineProto.DataCenter;
MadelineProto.connection flags:# protocol:string ip:string port:int timeout:int parsed:flags.0?MadelineProto.ParsedUrl temp_auth_key:MadelineProto.AuthKey auth_key:MadelineProto.AuthKey ipv6:flags.1?true incoming_messages:Vector<MadelineProto.Messages> outgoing_messages:Vector<MadelineProto.Messages> new_incoming:Vector<long> new_outgoing:Vector<long>
MadelineProto.descriptions = MadelineProto.Descriptions;
MadelineProto.methods = MadelineProto.Methods;
MadelineProto.constructors = MadelineProto.Constructors;
MadelineProto.parsedUrl = MadelineProto.ParsedUrl;
MadelineProto.authKey server_salt:long id:long auth_key:string = MadelineProto.AuthKey;
MadelineProto.messages flags:# seq_no:flags.0?int content:Object response:int = MadelineProto.Messages;

pwr.chat flags:# type:string id:long access_hash:flags.0?long first_name:flags.1?string last_name:flags.2?string lang_code:flags.3?string username:flags.4?string verified:flags.5?true restricted:flags.6?true restriction_reason:flags.7?string status:flags.8?UserStatus bot_inline_placeholder:flags.9?string about:flags.10?string bot_info:flags.11?BotInfo phone_calls_available:flags.12?true phone_calls_private:flags.13?true common_chats_count:flags.14?int photo:flags.15?string title:flags.16?string participants_count:flags.17?int kicked_count:flags.18?int admin_count:flags.19?int admin:flags.20?true all_members_are_administrators:flags.21?true invite:flags.22?string participants:flags.23?Vector<PWRParticipant> democracy:flags.24?true signatures:flags.25?true can_view_participants:flags.26?true can_set_username:flags.27?true migrated_from_chat_id:flags.28?int migrated_from_max_id:flags.29?int pinned_msg_id:flags.30?int = PWR.Chat;
pwr.participant flags:# user:PWR.Chat inviter:flags.0?PWR.Chat date:int role:string = PWR.Participant;

//auth.sentCodeMP flags:# phone_registered:flags.0?true type:auth.SentCodeType phone_code_hash:string next_type:flags.1?auth.CodeType timeout:flags.2?int phone_number:flags.3?string phone_code:flags.4?string = auth.Authorization;
//auth.noPasswordMP new_salt:bytes email_unconfirmed_pattern:string = auth.Authorization;
//auth.passwordMP current_salt:bytes new_salt:bytes hint:string has_recovery:Bool email_unconfirmed_pattern:string = auth.Authorization;
