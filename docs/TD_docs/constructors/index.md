---
title: Constructors
description: List of constructors
---
# Constructors  
[Back to API documentation index](..)
***
<br><br>[$accountTtl](../constructors/accountTtl.md) = \['days' => [int](../types/int.md), \];<a name="accountTtl"></a>  

***
<br><br>[$animation](../constructors/animation.md) = \['duration' => [int](../types/int.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'animation' => [file](../constructors/file.md), \];<a name="animation"></a>  

***
<br><br>[$animations](../constructors/animations.md) = \['animations' => \[[animation](../constructors/animation.md)\], \];<a name="animations"></a>  

***
<br><br>[$audio](../constructors/audio.md) = \['duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'album_cover_thumb' => [photoSize](../constructors/photoSize.md), 'audio' => [file](../constructors/file.md), \];<a name="audio"></a>  

***
<br><br>[$authCodeTypeCall](../constructors/authCodeTypeCall.md) = \['length' => [int](../types/int.md), \];<a name="authCodeTypeCall"></a>  

***
<br><br>[$authCodeTypeFlashCall](../constructors/authCodeTypeFlashCall.md) = \['pattern' => [string](../types/string.md), \];<a name="authCodeTypeFlashCall"></a>  

***
<br><br>[$authCodeTypeMessage](../constructors/authCodeTypeMessage.md) = \['length' => [int](../types/int.md), \];<a name="authCodeTypeMessage"></a>  

***
<br><br>[$authCodeTypeSms](../constructors/authCodeTypeSms.md) = \['length' => [int](../types/int.md), \];<a name="authCodeTypeSms"></a>  

***
<br><br>[$authStateLoggingOut](../constructors/authStateLoggingOut.md) = \[\];<a name="authStateLoggingOut"></a>  

***
<br><br>[$authStateOk](../constructors/authStateOk.md) = \[\];<a name="authStateOk"></a>  

***
<br><br>[$authStateWaitCode](../constructors/authStateWaitCode.md) = \['is_registered' => [Bool](../types/Bool.md), 'code_type' => [AuthCodeType](../types/AuthCodeType.md), 'next_code_type' => [AuthCodeType](../types/AuthCodeType.md), 'timeout' => [int](../types/int.md), \];<a name="authStateWaitCode"></a>  

***
<br><br>[$authStateWaitPassword](../constructors/authStateWaitPassword.md) = \['password_hint' => [string](../types/string.md), 'has_recovery_email' => [Bool](../types/Bool.md), 'recovery_email_pattern' => [string](../types/string.md), \];<a name="authStateWaitPassword"></a>  

***
<br><br>[$authStateWaitPhoneNumber](../constructors/authStateWaitPhoneNumber.md) = \[\];<a name="authStateWaitPhoneNumber"></a>  

***
<br><br>[$botCommand](../constructors/botCommand.md) = \['command' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="botCommand"></a>  

***
<br><br>[$botInfo](../constructors/botInfo.md) = \['description' => [string](../types/string.md), 'commands' => \[[botCommand](../constructors/botCommand.md)\], \];<a name="botInfo"></a>  

***
<br><br>[$call](../constructors/call.md) = \['id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'is_outgoing' => [Bool](../types/Bool.md), 'state' => [CallState](../types/CallState.md), \];<a name="call"></a>  

***
<br><br>[$callConnection](../constructors/callConnection.md) = \['id' => [long](../types/long.md), 'ip' => [string](../types/string.md), 'ipv6' => [string](../types/string.md), 'port' => [int](../types/int.md), 'peer_tag' => [bytes](../types/bytes.md), \];<a name="callConnection"></a>  

***
<br><br>[$callDiscardReasonDeclined](../constructors/callDiscardReasonDeclined.md) = \[\];<a name="callDiscardReasonDeclined"></a>  

***
<br><br>[$callDiscardReasonDisconnected](../constructors/callDiscardReasonDisconnected.md) = \[\];<a name="callDiscardReasonDisconnected"></a>  

***
<br><br>[$callDiscardReasonEmpty](../constructors/callDiscardReasonEmpty.md) = \[\];<a name="callDiscardReasonEmpty"></a>  

***
<br><br>[$callDiscardReasonHungUp](../constructors/callDiscardReasonHungUp.md) = \[\];<a name="callDiscardReasonHungUp"></a>  

***
<br><br>[$callDiscardReasonMissed](../constructors/callDiscardReasonMissed.md) = \[\];<a name="callDiscardReasonMissed"></a>  

***
<br><br>[$callId](../constructors/callId.md) = \['id' => [int](../types/int.md), \];<a name="callId"></a>  

***
<br><br>[$callProtocol](../constructors/callProtocol.md) = \['udp_p2p' => [Bool](../types/Bool.md), 'udp_reflector' => [Bool](../types/Bool.md), 'min_layer' => [int](../types/int.md), 'max_layer' => [int](../types/int.md), \];<a name="callProtocol"></a>  

***
<br><br>[$callStateDiscarded](../constructors/callStateDiscarded.md) = \['reason' => [CallDiscardReason](../types/CallDiscardReason.md), 'need_rating' => [Bool](../types/Bool.md), 'need_debug' => [Bool](../types/Bool.md), \];<a name="callStateDiscarded"></a>  

***
<br><br>[$callStateError](../constructors/callStateError.md) = \['error' => [error](../constructors/error.md), \];<a name="callStateError"></a>  

***
<br><br>[$callStateExchangingKeys](../constructors/callStateExchangingKeys.md) = \[\];<a name="callStateExchangingKeys"></a>  

***
<br><br>[$callStateHangingUp](../constructors/callStateHangingUp.md) = \[\];<a name="callStateHangingUp"></a>  

***
<br><br>[$callStatePending](../constructors/callStatePending.md) = \['is_created' => [Bool](../types/Bool.md), 'is_received' => [Bool](../types/Bool.md), \];<a name="callStatePending"></a>  

***
<br><br>[$callStateReady](../constructors/callStateReady.md) = \['protocol' => [callProtocol](../constructors/callProtocol.md), 'connections' => \[[callConnection](../constructors/callConnection.md)\], 'config' => [string](../types/string.md), 'encryption_key' => [bytes](../types/bytes.md), 'emojis' => \[[string](../types/string.md)\], \];<a name="callStateReady"></a>  

***
<br><br>[$callbackQueryAnswer](../constructors/callbackQueryAnswer.md) = \['text' => [string](../types/string.md), 'show_alert' => [Bool](../types/Bool.md), 'url' => [string](../types/string.md), \];<a name="callbackQueryAnswer"></a>  

***
<br><br>[$callbackQueryPayloadData](../constructors/callbackQueryPayloadData.md) = \['data' => [bytes](../types/bytes.md), \];<a name="callbackQueryPayloadData"></a>  

***
<br><br>[$callbackQueryPayloadGame](../constructors/callbackQueryPayloadGame.md) = \['game_short_name' => [string](../types/string.md), \];<a name="callbackQueryPayloadGame"></a>  

***
<br><br>[$channel](../constructors/channel.md) = \['id' => [int](../types/int.md), 'username' => [string](../types/string.md), 'date' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'anyone_can_invite' => [Bool](../types/Bool.md), 'sign_messages' => [Bool](../types/Bool.md), 'is_supergroup' => [Bool](../types/Bool.md), 'is_verified' => [Bool](../types/Bool.md), 'restriction_reason' => [string](../types/string.md), \];<a name="channel"></a>  

***
<br><br>[$channelFull](../constructors/channelFull.md) = \['description' => [string](../types/string.md), 'member_count' => [int](../types/int.md), 'administrator_count' => [int](../types/int.md), 'restricted_count' => [int](../types/int.md), 'banned_count' => [int](../types/int.md), 'can_get_members' => [Bool](../types/Bool.md), 'can_set_username' => [Bool](../types/Bool.md), 'invite_link' => [string](../types/string.md), 'pinned_message_id' => [int53](../constructors/int53.md), 'migrated_from_group_id' => [int](../types/int.md), 'migrated_from_max_message_id' => [int53](../constructors/int53.md), \];<a name="channelFull"></a>  

***
<br><br>[$channelMembersFilterAdministrators](../constructors/channelMembersFilterAdministrators.md) = \[\];<a name="channelMembersFilterAdministrators"></a>  

***
<br><br>[$channelMembersFilterBanned](../constructors/channelMembersFilterBanned.md) = \['query' => [string](../types/string.md), \];<a name="channelMembersFilterBanned"></a>  

***
<br><br>[$channelMembersFilterBots](../constructors/channelMembersFilterBots.md) = \[\];<a name="channelMembersFilterBots"></a>  

***
<br><br>[$channelMembersFilterRecent](../constructors/channelMembersFilterRecent.md) = \[\];<a name="channelMembersFilterRecent"></a>  

***
<br><br>[$channelMembersFilterRestricted](../constructors/channelMembersFilterRestricted.md) = \['query' => [string](../types/string.md), \];<a name="channelMembersFilterRestricted"></a>  

***
<br><br>[$channelMembersFilterSearch](../constructors/channelMembersFilterSearch.md) = \['query' => [string](../types/string.md), \];<a name="channelMembersFilterSearch"></a>  

***
<br><br>[$chat](../constructors/chat.md) = \['id' => [int53](../constructors/int53.md), 'type' => [ChatType](../types/ChatType.md), 'title' => [string](../types/string.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), 'top_message' => [message](../constructors/message.md), 'order' => [long](../types/long.md), 'is_pinned' => [Bool](../types/Bool.md), 'unread_count' => [int](../types/int.md), 'last_read_inbox_message_id' => [int53](../constructors/int53.md), 'last_read_outbox_message_id' => [int53](../constructors/int53.md), 'notification_settings' => [notificationSettings](../constructors/notificationSettings.md), 'reply_markup_message_id' => [int53](../constructors/int53.md), 'draft_message' => [draftMessage](../constructors/draftMessage.md), 'client_data' => [string](../types/string.md), \];<a name="chat"></a>  

***
<br><br>[$chatActionCancel](../constructors/chatActionCancel.md) = \[\];<a name="chatActionCancel"></a>  

***
<br><br>[$chatActionChoosingContact](../constructors/chatActionChoosingContact.md) = \[\];<a name="chatActionChoosingContact"></a>  

***
<br><br>[$chatActionChoosingLocation](../constructors/chatActionChoosingLocation.md) = \[\];<a name="chatActionChoosingLocation"></a>  

***
<br><br>[$chatActionRecordingVideo](../constructors/chatActionRecordingVideo.md) = \[\];<a name="chatActionRecordingVideo"></a>  

***
<br><br>[$chatActionRecordingVideoNote](../constructors/chatActionRecordingVideoNote.md) = \[\];<a name="chatActionRecordingVideoNote"></a>  

***
<br><br>[$chatActionRecordingVoice](../constructors/chatActionRecordingVoice.md) = \[\];<a name="chatActionRecordingVoice"></a>  

***
<br><br>[$chatActionStartPlayingGame](../constructors/chatActionStartPlayingGame.md) = \[\];<a name="chatActionStartPlayingGame"></a>  

***
<br><br>[$chatActionTyping](../constructors/chatActionTyping.md) = \[\];<a name="chatActionTyping"></a>  

***
<br><br>[$chatActionUploadingDocument](../constructors/chatActionUploadingDocument.md) = \['progress' => [int](../types/int.md), \];<a name="chatActionUploadingDocument"></a>  

***
<br><br>[$chatActionUploadingPhoto](../constructors/chatActionUploadingPhoto.md) = \['progress' => [int](../types/int.md), \];<a name="chatActionUploadingPhoto"></a>  

***
<br><br>[$chatActionUploadingVideo](../constructors/chatActionUploadingVideo.md) = \['progress' => [int](../types/int.md), \];<a name="chatActionUploadingVideo"></a>  

***
<br><br>[$chatActionUploadingVideoNote](../constructors/chatActionUploadingVideoNote.md) = \['progress' => [int](../types/int.md), \];<a name="chatActionUploadingVideoNote"></a>  

***
<br><br>[$chatActionUploadingVoice](../constructors/chatActionUploadingVoice.md) = \['progress' => [int](../types/int.md), \];<a name="chatActionUploadingVoice"></a>  

***
<br><br>[$chatEvent](../constructors/chatEvent.md) = \['id' => [long](../types/long.md), 'date' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'action' => [ChatEventAction](../types/ChatEventAction.md), \];<a name="chatEvent"></a>  

***
<br><br>[$chatEventDescriptionChanged](../constructors/chatEventDescriptionChanged.md) = \['old_description' => [string](../types/string.md), 'new_description' => [string](../types/string.md), \];<a name="chatEventDescriptionChanged"></a>  

***
<br><br>[$chatEventInvitesToggled](../constructors/chatEventInvitesToggled.md) = \['anyone_can_invite' => [Bool](../types/Bool.md), \];<a name="chatEventInvitesToggled"></a>  

***
<br><br>[$chatEventLogFilters](../constructors/chatEventLogFilters.md) = \['message_edits' => [Bool](../types/Bool.md), 'message_deletions' => [Bool](../types/Bool.md), 'message_pins' => [Bool](../types/Bool.md), 'member_joins' => [Bool](../types/Bool.md), 'member_leaves' => [Bool](../types/Bool.md), 'member_invites' => [Bool](../types/Bool.md), 'member_promotions' => [Bool](../types/Bool.md), 'member_restrictions' => [Bool](../types/Bool.md), 'info_changes' => [Bool](../types/Bool.md), 'setting_changes' => [Bool](../types/Bool.md), \];<a name="chatEventLogFilters"></a>  

***
<br><br>[$chatEventMemberInvited](../constructors/chatEventMemberInvited.md) = \['user_id' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), \];<a name="chatEventMemberInvited"></a>  

***
<br><br>[$chatEventMemberJoined](../constructors/chatEventMemberJoined.md) = \[\];<a name="chatEventMemberJoined"></a>  

***
<br><br>[$chatEventMemberLeft](../constructors/chatEventMemberLeft.md) = \[\];<a name="chatEventMemberLeft"></a>  

***
<br><br>[$chatEventMemberPromoted](../constructors/chatEventMemberPromoted.md) = \['user_id' => [int](../types/int.md), 'old_status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'new_status' => [ChatMemberStatus](../types/ChatMemberStatus.md), \];<a name="chatEventMemberPromoted"></a>  

***
<br><br>[$chatEventMemberRestricted](../constructors/chatEventMemberRestricted.md) = \['user_id' => [int](../types/int.md), 'old_status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'new_status' => [ChatMemberStatus](../types/ChatMemberStatus.md), \];<a name="chatEventMemberRestricted"></a>  

***
<br><br>[$chatEventMessageDeleted](../constructors/chatEventMessageDeleted.md) = \['message' => [message](../constructors/message.md), \];<a name="chatEventMessageDeleted"></a>  

***
<br><br>[$chatEventMessageEdited](../constructors/chatEventMessageEdited.md) = \['old_message' => [message](../constructors/message.md), 'new_message' => [message](../constructors/message.md), \];<a name="chatEventMessageEdited"></a>  

***
<br><br>[$chatEventMessagePinned](../constructors/chatEventMessagePinned.md) = \['message' => [message](../constructors/message.md), \];<a name="chatEventMessagePinned"></a>  

***
<br><br>[$chatEventMessageUnpinned](../constructors/chatEventMessageUnpinned.md) = \[\];<a name="chatEventMessageUnpinned"></a>  

***
<br><br>[$chatEventPhotoChanged](../constructors/chatEventPhotoChanged.md) = \['old_photo' => [chatPhoto](../constructors/chatPhoto.md), 'new_photo' => [chatPhoto](../constructors/chatPhoto.md), \];<a name="chatEventPhotoChanged"></a>  

***
<br><br>[$chatEventSignMessagesToggled](../constructors/chatEventSignMessagesToggled.md) = \['sign_messages' => [Bool](../types/Bool.md), \];<a name="chatEventSignMessagesToggled"></a>  

***
<br><br>[$chatEventTitleChanged](../constructors/chatEventTitleChanged.md) = \['old_title' => [string](../types/string.md), 'new_title' => [string](../types/string.md), \];<a name="chatEventTitleChanged"></a>  

***
<br><br>[$chatEventUsernameChanged](../constructors/chatEventUsernameChanged.md) = \['old_username' => [string](../types/string.md), 'new_username' => [string](../types/string.md), \];<a name="chatEventUsernameChanged"></a>  

***
<br><br>[$chatEvents](../constructors/chatEvents.md) = \['events' => \[[chatEvent](../constructors/chatEvent.md)\], \];<a name="chatEvents"></a>  

***
<br><br>[$chatInviteLink](../constructors/chatInviteLink.md) = \['invite_link' => [string](../types/string.md), \];<a name="chatInviteLink"></a>  

***
<br><br>[$chatInviteLinkInfo](../constructors/chatInviteLinkInfo.md) = \['chat_id' => [int53](../constructors/int53.md), 'title' => [string](../types/string.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), 'member_count' => [int](../types/int.md), 'member_user_ids' => \[[int](../types/int.md)\], 'is_group' => [Bool](../types/Bool.md), 'is_channel' => [Bool](../types/Bool.md), 'is_public_channel' => [Bool](../types/Bool.md), 'is_supergroup_channel' => [Bool](../types/Bool.md), \];<a name="chatInviteLinkInfo"></a>  

***
<br><br>[$chatMember](../constructors/chatMember.md) = \['user_id' => [int](../types/int.md), 'inviter_user_id' => [int](../types/int.md), 'join_date' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'bot_info' => [botInfo](../constructors/botInfo.md), \];<a name="chatMember"></a>  

***
<br><br>[$chatMemberStatusAdministrator](../constructors/chatMemberStatusAdministrator.md) = \['can_be_edited' => [Bool](../types/Bool.md), 'can_change_info' => [Bool](../types/Bool.md), 'can_post_messages' => [Bool](../types/Bool.md), 'can_edit_messages' => [Bool](../types/Bool.md), 'can_delete_messages' => [Bool](../types/Bool.md), 'can_invite_users' => [Bool](../types/Bool.md), 'can_restrict_members' => [Bool](../types/Bool.md), 'can_pin_messages' => [Bool](../types/Bool.md), 'can_promote_members' => [Bool](../types/Bool.md), \];<a name="chatMemberStatusAdministrator"></a>  

***
<br><br>[$chatMemberStatusBanned](../constructors/chatMemberStatusBanned.md) = \['banned_until_date' => [int](../types/int.md), \];<a name="chatMemberStatusBanned"></a>  

***
<br><br>[$chatMemberStatusCreator](../constructors/chatMemberStatusCreator.md) = \[\];<a name="chatMemberStatusCreator"></a>  

***
<br><br>[$chatMemberStatusLeft](../constructors/chatMemberStatusLeft.md) = \[\];<a name="chatMemberStatusLeft"></a>  

***
<br><br>[$chatMemberStatusMember](../constructors/chatMemberStatusMember.md) = \[\];<a name="chatMemberStatusMember"></a>  

***
<br><br>[$chatMemberStatusRestricted](../constructors/chatMemberStatusRestricted.md) = \['is_member' => [Bool](../types/Bool.md), 'restricted_until_date' => [int](../types/int.md), 'can_send_messages' => [Bool](../types/Bool.md), 'can_send_media_messages' => [Bool](../types/Bool.md), 'can_send_other_messages' => [Bool](../types/Bool.md), 'can_add_web_page_previews' => [Bool](../types/Bool.md), \];<a name="chatMemberStatusRestricted"></a>  

***
<br><br>[$chatMembers](../constructors/chatMembers.md) = \['total_count' => [int](../types/int.md), 'members' => \[[chatMember](../constructors/chatMember.md)\], \];<a name="chatMembers"></a>  

***
<br><br>[$chatPhoto](../constructors/chatPhoto.md) = \['small' => [file](../constructors/file.md), 'big' => [file](../constructors/file.md), \];<a name="chatPhoto"></a>  

***
<br><br>[$chatReportReasonOther](../constructors/chatReportReasonOther.md) = \['text' => [string](../types/string.md), \];<a name="chatReportReasonOther"></a>  

***
<br><br>[$chatReportReasonPornography](../constructors/chatReportReasonPornography.md) = \[\];<a name="chatReportReasonPornography"></a>  

***
<br><br>[$chatReportReasonSpam](../constructors/chatReportReasonSpam.md) = \[\];<a name="chatReportReasonSpam"></a>  

***
<br><br>[$chatReportReasonViolence](../constructors/chatReportReasonViolence.md) = \[\];<a name="chatReportReasonViolence"></a>  

***
<br><br>[$chatReportSpamState](../constructors/chatReportSpamState.md) = \['can_report_spam' => [Bool](../types/Bool.md), \];<a name="chatReportSpamState"></a>  

***
<br><br>[$chatTypeChannel](../constructors/chatTypeChannel.md) = \['channel_id' => [int](../types/int.md), \];<a name="chatTypeChannel"></a>  

***
<br><br>[$chatTypeGroup](../constructors/chatTypeGroup.md) = \['group_id' => [int](../types/int.md), \];<a name="chatTypeGroup"></a>  

***
<br><br>[$chatTypePrivate](../constructors/chatTypePrivate.md) = \['user_id' => [int](../types/int.md), \];<a name="chatTypePrivate"></a>  

***
<br><br>[$chatTypeSecret](../constructors/chatTypeSecret.md) = \['secret_chat_id' => [int](../types/int.md), \];<a name="chatTypeSecret"></a>  

***
<br><br>[$chats](../constructors/chats.md) = \['chat_ids' => \[[int53](../constructors/int53.md)\], \];<a name="chats"></a>  

***
<br><br>[$connectionStateConnecting](../constructors/connectionStateConnecting.md) = \[\];<a name="connectionStateConnecting"></a>  

***
<br><br>[$connectionStateConnectingToProxy](../constructors/connectionStateConnectingToProxy.md) = \[\];<a name="connectionStateConnectingToProxy"></a>  

***
<br><br>[$connectionStateReady](../constructors/connectionStateReady.md) = \[\];<a name="connectionStateReady"></a>  

***
<br><br>[$connectionStateUpdating](../constructors/connectionStateUpdating.md) = \[\];<a name="connectionStateUpdating"></a>  

***
<br><br>[$connectionStateWaitingForNetwork](../constructors/connectionStateWaitingForNetwork.md) = \[\];<a name="connectionStateWaitingForNetwork"></a>  

***
<br><br>[$contact](../constructors/contact.md) = \['phone_number' => [string](../types/string.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'user_id' => [int](../types/int.md), \];<a name="contact"></a>  

***
<br><br>[$customRequestResult](../constructors/customRequestResult.md) = \['result' => [string](../types/string.md), \];<a name="customRequestResult"></a>  

***
<br><br>[$deviceTokenApns](../constructors/deviceTokenApns.md) = \['token' => [string](../types/string.md), \];<a name="deviceTokenApns"></a>  

***
<br><br>[$deviceTokenBlackberry](../constructors/deviceTokenBlackberry.md) = \['token' => [string](../types/string.md), \];<a name="deviceTokenBlackberry"></a>  

***
<br><br>[$deviceTokenGcm](../constructors/deviceTokenGcm.md) = \['token' => [string](../types/string.md), \];<a name="deviceTokenGcm"></a>  

***
<br><br>[$deviceTokenMpns](../constructors/deviceTokenMpns.md) = \['token' => [string](../types/string.md), \];<a name="deviceTokenMpns"></a>  

***
<br><br>[$deviceTokenSimplePush](../constructors/deviceTokenSimplePush.md) = \['token' => [string](../types/string.md), \];<a name="deviceTokenSimplePush"></a>  

***
<br><br>[$deviceTokenUbuntuPhone](../constructors/deviceTokenUbuntuPhone.md) = \['token' => [string](../types/string.md), \];<a name="deviceTokenUbuntuPhone"></a>  

***
<br><br>[$document](../constructors/document.md) = \['file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'document' => [file](../constructors/file.md), \];<a name="document"></a>  

***
<br><br>[$draftMessage](../constructors/draftMessage.md) = \['reply_to_message_id' => [int53](../constructors/int53.md), 'input_message_text' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="draftMessage"></a>  

***
<br><br>[$error](../constructors/error.md) = \['code' => [int](../types/int.md), 'message' => [string](../types/string.md), \];<a name="error"></a>  

***
<br><br>[$file](../constructors/file.md) = \['id' => [int](../types/int.md), 'persistent_id' => [string](../types/string.md), 'size' => [int](../types/int.md), 'is_being_downloaded' => [Bool](../types/Bool.md), 'local_size' => [int](../types/int.md), 'is_being_uploaded' => [Bool](../types/Bool.md), 'remote_size' => [int](../types/int.md), 'path' => [string](../types/string.md), \];<a name="file"></a>  

***
<br><br>[$fileTypeAnimation](../constructors/fileTypeAnimation.md) = \[\];<a name="fileTypeAnimation"></a>  

***
<br><br>[$fileTypeAudio](../constructors/fileTypeAudio.md) = \[\];<a name="fileTypeAudio"></a>  

***
<br><br>[$fileTypeDocument](../constructors/fileTypeDocument.md) = \[\];<a name="fileTypeDocument"></a>  

***
<br><br>[$fileTypeNone](../constructors/fileTypeNone.md) = \[\];<a name="fileTypeNone"></a>  

***
<br><br>[$fileTypePhoto](../constructors/fileTypePhoto.md) = \[\];<a name="fileTypePhoto"></a>  

***
<br><br>[$fileTypeProfilePhoto](../constructors/fileTypeProfilePhoto.md) = \[\];<a name="fileTypeProfilePhoto"></a>  

***
<br><br>[$fileTypeSecret](../constructors/fileTypeSecret.md) = \[\];<a name="fileTypeSecret"></a>  

***
<br><br>[$fileTypeSecretThumb](../constructors/fileTypeSecretThumb.md) = \[\];<a name="fileTypeSecretThumb"></a>  

***
<br><br>[$fileTypeSticker](../constructors/fileTypeSticker.md) = \[\];<a name="fileTypeSticker"></a>  

***
<br><br>[$fileTypeThumb](../constructors/fileTypeThumb.md) = \[\];<a name="fileTypeThumb"></a>  

***
<br><br>[$fileTypeUnknown](../constructors/fileTypeUnknown.md) = \[\];<a name="fileTypeUnknown"></a>  

***
<br><br>[$fileTypeVideo](../constructors/fileTypeVideo.md) = \[\];<a name="fileTypeVideo"></a>  

***
<br><br>[$fileTypeVideoNote](../constructors/fileTypeVideoNote.md) = \[\];<a name="fileTypeVideoNote"></a>  

***
<br><br>[$fileTypeVoice](../constructors/fileTypeVoice.md) = \[\];<a name="fileTypeVoice"></a>  

***
<br><br>[$fileTypeWallpaper](../constructors/fileTypeWallpaper.md) = \[\];<a name="fileTypeWallpaper"></a>  

***
<br><br>[$foundMessages](../constructors/foundMessages.md) = \['messages' => \[[message](../constructors/message.md)\], 'next_from_search_id' => [long](../types/long.md), \];<a name="foundMessages"></a>  

***
<br><br>[$game](../constructors/game.md) = \['id' => [long](../types/long.md), 'short_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'text' => [string](../types/string.md), 'text_entities' => \[[textEntity](../constructors/textEntity.md)\], 'description' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'animation' => [animation](../constructors/animation.md), \];<a name="game"></a>  

***
<br><br>[$gameHighScore](../constructors/gameHighScore.md) = \['position' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'score' => [int](../types/int.md), \];<a name="gameHighScore"></a>  

***
<br><br>[$gameHighScores](../constructors/gameHighScores.md) = \['scores' => \[[gameHighScore](../constructors/gameHighScore.md)\], \];<a name="gameHighScores"></a>  

***
<br><br>[$group](../constructors/group.md) = \['id' => [int](../types/int.md), 'member_count' => [int](../types/int.md), 'status' => [ChatMemberStatus](../types/ChatMemberStatus.md), 'everyone_is_administrator' => [Bool](../types/Bool.md), 'is_active' => [Bool](../types/Bool.md), 'migrated_to_channel_id' => [int](../types/int.md), \];<a name="group"></a>  

***
<br><br>[$groupFull](../constructors/groupFull.md) = \['creator_user_id' => [int](../types/int.md), 'members' => \[[chatMember](../constructors/chatMember.md)\], 'invite_link' => [string](../types/string.md), \];<a name="groupFull"></a>  

***
<br><br>[$hashtags](../constructors/hashtags.md) = \['hashtags' => \[[string](../types/string.md)\], \];<a name="hashtags"></a>  

***
<br><br>[$importedContacts](../constructors/importedContacts.md) = \['user_ids' => \[[int](../types/int.md)\], 'importer_count' => \[[int](../types/int.md)\], \];<a name="importedContacts"></a>  

***
<br><br>[$inlineKeyboardButton](../constructors/inlineKeyboardButton.md) = \['text' => [string](../types/string.md), 'type' => [InlineKeyboardButtonType](../types/InlineKeyboardButtonType.md), \];<a name="inlineKeyboardButton"></a>  

***
<br><br>[$inlineKeyboardButtonTypeBuy](../constructors/inlineKeyboardButtonTypeBuy.md) = \[\];<a name="inlineKeyboardButtonTypeBuy"></a>  

***
<br><br>[$inlineKeyboardButtonTypeCallback](../constructors/inlineKeyboardButtonTypeCallback.md) = \['data' => [bytes](../types/bytes.md), \];<a name="inlineKeyboardButtonTypeCallback"></a>  

***
<br><br>[$inlineKeyboardButtonTypeCallbackGame](../constructors/inlineKeyboardButtonTypeCallbackGame.md) = \[\];<a name="inlineKeyboardButtonTypeCallbackGame"></a>  

***
<br><br>[$inlineKeyboardButtonTypeSwitchInline](../constructors/inlineKeyboardButtonTypeSwitchInline.md) = \['query' => [string](../types/string.md), 'in_current_chat' => [Bool](../types/Bool.md), \];<a name="inlineKeyboardButtonTypeSwitchInline"></a>  

***
<br><br>[$inlineKeyboardButtonTypeUrl](../constructors/inlineKeyboardButtonTypeUrl.md) = \['url' => [string](../types/string.md), \];<a name="inlineKeyboardButtonTypeUrl"></a>  

***
<br><br>[$inlineQueryResultAnimation](../constructors/inlineQueryResultAnimation.md) = \['id' => [string](../types/string.md), 'animation' => [animation](../constructors/animation.md), 'title' => [string](../types/string.md), \];<a name="inlineQueryResultAnimation"></a>  

***
<br><br>[$inlineQueryResultArticle](../constructors/inlineQueryResultArticle.md) = \['id' => [string](../types/string.md), 'url' => [string](../types/string.md), 'hide_url' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb' => [photoSize](../constructors/photoSize.md), \];<a name="inlineQueryResultArticle"></a>  

***
<br><br>[$inlineQueryResultAudio](../constructors/inlineQueryResultAudio.md) = \['id' => [string](../types/string.md), 'audio' => [audio](../constructors/audio.md), \];<a name="inlineQueryResultAudio"></a>  

***
<br><br>[$inlineQueryResultContact](../constructors/inlineQueryResultContact.md) = \['id' => [string](../types/string.md), 'contact' => [contact](../constructors/contact.md), 'thumb' => [photoSize](../constructors/photoSize.md), \];<a name="inlineQueryResultContact"></a>  

***
<br><br>[$inlineQueryResultDocument](../constructors/inlineQueryResultDocument.md) = \['id' => [string](../types/string.md), 'document' => [document](../constructors/document.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="inlineQueryResultDocument"></a>  

***
<br><br>[$inlineQueryResultGame](../constructors/inlineQueryResultGame.md) = \['id' => [string](../types/string.md), 'game' => [game](../constructors/game.md), \];<a name="inlineQueryResultGame"></a>  

***
<br><br>[$inlineQueryResultLocation](../constructors/inlineQueryResultLocation.md) = \['id' => [string](../types/string.md), 'location' => [location](../constructors/location.md), 'title' => [string](../types/string.md), 'thumb' => [photoSize](../constructors/photoSize.md), \];<a name="inlineQueryResultLocation"></a>  

***
<br><br>[$inlineQueryResultPhoto](../constructors/inlineQueryResultPhoto.md) = \['id' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="inlineQueryResultPhoto"></a>  

***
<br><br>[$inlineQueryResultSticker](../constructors/inlineQueryResultSticker.md) = \['id' => [string](../types/string.md), 'sticker' => [sticker](../constructors/sticker.md), \];<a name="inlineQueryResultSticker"></a>  

***
<br><br>[$inlineQueryResultVenue](../constructors/inlineQueryResultVenue.md) = \['id' => [string](../types/string.md), 'venue' => [venue](../constructors/venue.md), 'thumb' => [photoSize](../constructors/photoSize.md), \];<a name="inlineQueryResultVenue"></a>  

***
<br><br>[$inlineQueryResultVideo](../constructors/inlineQueryResultVideo.md) = \['id' => [string](../types/string.md), 'video' => [video](../constructors/video.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), \];<a name="inlineQueryResultVideo"></a>  

***
<br><br>[$inlineQueryResultVoice](../constructors/inlineQueryResultVoice.md) = \['id' => [string](../types/string.md), 'voice' => [voice](../constructors/voice.md), 'title' => [string](../types/string.md), \];<a name="inlineQueryResultVoice"></a>  

***
<br><br>[$inlineQueryResults](../constructors/inlineQueryResults.md) = \['inline_query_id' => [long](../types/long.md), 'next_offset' => [string](../types/string.md), 'results' => \[[InlineQueryResult](../types/InlineQueryResult.md)\], 'switch_pm_text' => [string](../types/string.md), 'switch_pm_parameter' => [string](../types/string.md), \];<a name="inlineQueryResults"></a>  

***
<br><br>[$inputCredentialsNew](../constructors/inputCredentialsNew.md) = \['data' => [string](../types/string.md), 'allow_save' => [Bool](../types/Bool.md), \];<a name="inputCredentialsNew"></a>  

***
<br><br>[$inputCredentialsSaved](../constructors/inputCredentialsSaved.md) = \['saved_credentials_id' => [string](../types/string.md), \];<a name="inputCredentialsSaved"></a>  

***
<br><br>[$inputFileGenerated](../constructors/inputFileGenerated.md) = \['original_path' => [string](../types/string.md), 'conversion' => [string](../types/string.md), 'expected_size' => [int](../types/int.md), \];<a name="inputFileGenerated"></a>  

***
<br><br>[$inputFileId](../constructors/inputFileId.md) = \['id' => [int](../types/int.md), \];<a name="inputFileId"></a>  

***
<br><br>[$inputFileLocal](../constructors/inputFileLocal.md) = \['path' => [string](../types/string.md), \];<a name="inputFileLocal"></a>  

***
<br><br>[$inputFilePersistentId](../constructors/inputFilePersistentId.md) = \['persistent_id' => [string](../types/string.md), \];<a name="inputFilePersistentId"></a>  

***
<br><br>[$inputInlineQueryResultAnimatedGif](../constructors/inputInlineQueryResultAnimatedGif.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'gif_url' => [string](../types/string.md), 'gif_duration' => [int](../types/int.md), 'gif_width' => [int](../types/int.md), 'gif_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultAnimatedGif"></a>  

***
<br><br>[$inputInlineQueryResultAnimatedMpeg4](../constructors/inputInlineQueryResultAnimatedMpeg4.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'mpeg4_url' => [string](../types/string.md), 'mpeg4_duration' => [int](../types/int.md), 'mpeg4_width' => [int](../types/int.md), 'mpeg4_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultAnimatedMpeg4"></a>  

***
<br><br>[$inputInlineQueryResultArticle](../constructors/inputInlineQueryResultArticle.md) = \['id' => [string](../types/string.md), 'url' => [string](../types/string.md), 'hide_url' => [Bool](../types/Bool.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultArticle"></a>  

***
<br><br>[$inputInlineQueryResultAudio](../constructors/inputInlineQueryResultAudio.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'audio_url' => [string](../types/string.md), 'audio_duration' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultAudio"></a>  

***
<br><br>[$inputInlineQueryResultContact](../constructors/inputInlineQueryResultContact.md) = \['id' => [string](../types/string.md), 'contact' => [contact](../constructors/contact.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultContact"></a>  

***
<br><br>[$inputInlineQueryResultDocument](../constructors/inputInlineQueryResultDocument.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'document_url' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultDocument"></a>  

***
<br><br>[$inputInlineQueryResultGame](../constructors/inputInlineQueryResultGame.md) = \['id' => [string](../types/string.md), 'game_short_name' => [string](../types/string.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="inputInlineQueryResultGame"></a>  

***
<br><br>[$inputInlineQueryResultLocation](../constructors/inputInlineQueryResultLocation.md) = \['id' => [string](../types/string.md), 'location' => [location](../constructors/location.md), 'title' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultLocation"></a>  

***
<br><br>[$inputInlineQueryResultPhoto](../constructors/inputInlineQueryResultPhoto.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'photo_url' => [string](../types/string.md), 'photo_width' => [int](../types/int.md), 'photo_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultPhoto"></a>  

***
<br><br>[$inputInlineQueryResultSticker](../constructors/inputInlineQueryResultSticker.md) = \['id' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'sticker_url' => [string](../types/string.md), 'sticker_width' => [int](../types/int.md), 'sticker_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultSticker"></a>  

***
<br><br>[$inputInlineQueryResultVenue](../constructors/inputInlineQueryResultVenue.md) = \['id' => [string](../types/string.md), 'venue' => [venue](../constructors/venue.md), 'thumb_url' => [string](../types/string.md), 'thumb_width' => [int](../types/int.md), 'thumb_height' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultVenue"></a>  

***
<br><br>[$inputInlineQueryResultVideo](../constructors/inputInlineQueryResultVideo.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'thumb_url' => [string](../types/string.md), 'video_url' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'video_width' => [int](../types/int.md), 'video_height' => [int](../types/int.md), 'video_duration' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultVideo"></a>  

***
<br><br>[$inputInlineQueryResultVoice](../constructors/inputInlineQueryResultVoice.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'voice_url' => [string](../types/string.md), 'voice_duration' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), 'input_message_content' => [InputMessageContent](../types/InputMessageContent.md), \];<a name="inputInlineQueryResultVoice"></a>  

***
<br><br>[$inputMessageAnimation](../constructors/inputMessageAnimation.md) = \['animation' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'duration' => [int](../types/int.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageAnimation"></a>  

***
<br><br>[$inputMessageAudio](../constructors/inputMessageAudio.md) = \['audio' => [InputFile](../types/InputFile.md), 'album_cover_thumb' => [InputThumb](../types/InputThumb.md), 'duration' => [int](../types/int.md), 'title' => [string](../types/string.md), 'performer' => [string](../types/string.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageAudio"></a>  

***
<br><br>[$inputMessageContact](../constructors/inputMessageContact.md) = \['contact' => [contact](../constructors/contact.md), \];<a name="inputMessageContact"></a>  

***
<br><br>[$inputMessageDocument](../constructors/inputMessageDocument.md) = \['document' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageDocument"></a>  

***
<br><br>[$inputMessageForwarded](../constructors/inputMessageForwarded.md) = \['from_chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), 'in_game_share' => [Bool](../types/Bool.md), \];<a name="inputMessageForwarded"></a>  

***
<br><br>[$inputMessageGame](../constructors/inputMessageGame.md) = \['bot_user_id' => [int](../types/int.md), 'game_short_name' => [string](../types/string.md), \];<a name="inputMessageGame"></a>  

***
<br><br>[$inputMessageInvoice](../constructors/inputMessageInvoice.md) = \['invoice' => [invoice](../constructors/invoice.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo_url' => [string](../types/string.md), 'photo_size' => [int](../types/int.md), 'photo_width' => [int](../types/int.md), 'photo_height' => [int](../types/int.md), 'payload' => [bytes](../types/bytes.md), 'provider_token' => [string](../types/string.md), 'start_parameter' => [string](../types/string.md), \];<a name="inputMessageInvoice"></a>  

***
<br><br>[$inputMessageLocation](../constructors/inputMessageLocation.md) = \['location' => [location](../constructors/location.md), \];<a name="inputMessageLocation"></a>  

***
<br><br>[$inputMessagePhoto](../constructors/inputMessagePhoto.md) = \['photo' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'added_sticker_file_ids' => \[[int](../types/int.md)\], 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [string](../types/string.md), 'ttl' => [int](../types/int.md), \];<a name="inputMessagePhoto"></a>  

***
<br><br>[$inputMessageSticker](../constructors/inputMessageSticker.md) = \['sticker' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="inputMessageSticker"></a>  

***
<br><br>[$inputMessageText](../constructors/inputMessageText.md) = \['text' => [string](../types/string.md), 'disable_web_page_preview' => [Bool](../types/Bool.md), 'clear_draft' => [Bool](../types/Bool.md), 'entities' => \[[textEntity](../constructors/textEntity.md)\], 'parse_mode' => [TextParseMode](../types/TextParseMode.md), \];<a name="inputMessageText"></a>  

***
<br><br>[$inputMessageVenue](../constructors/inputMessageVenue.md) = \['venue' => [venue](../constructors/venue.md), \];<a name="inputMessageVenue"></a>  

***
<br><br>[$inputMessageVideo](../constructors/inputMessageVideo.md) = \['video' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'added_sticker_file_ids' => \[[int](../types/int.md)\], 'duration' => [int](../types/int.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [string](../types/string.md), 'ttl' => [int](../types/int.md), \];<a name="inputMessageVideo"></a>  

***
<br><br>[$inputMessageVideoNote](../constructors/inputMessageVideoNote.md) = \['video_note' => [InputFile](../types/InputFile.md), 'thumb' => [InputThumb](../types/InputThumb.md), 'duration' => [int](../types/int.md), 'length' => [int](../types/int.md), \];<a name="inputMessageVideoNote"></a>  

***
<br><br>[$inputMessageVoice](../constructors/inputMessageVoice.md) = \['voice' => [InputFile](../types/InputFile.md), 'duration' => [int](../types/int.md), 'waveform' => [bytes](../types/bytes.md), 'caption' => [string](../types/string.md), \];<a name="inputMessageVoice"></a>  

***
<br><br>[$inputSticker](../constructors/inputSticker.md) = \['png_sticker' => [InputFile](../types/InputFile.md), 'emojis' => [string](../types/string.md), 'mask_position' => [maskPosition](../constructors/maskPosition.md), \];<a name="inputSticker"></a>  

***
<br><br>[$inputThumbGenerated](../constructors/inputThumbGenerated.md) = \['original_path' => [string](../types/string.md), 'conversion' => [string](../types/string.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="inputThumbGenerated"></a>  

***
<br><br>[$inputThumbLocal](../constructors/inputThumbLocal.md) = \['path' => [string](../types/string.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="inputThumbLocal"></a>  

***
<br><br>[$invoice](../constructors/invoice.md) = \['currency' => [string](../types/string.md), 'prices' => \[[labeledPrice](../constructors/labeledPrice.md)\], 'is_test' => [Bool](../types/Bool.md), 'need_name' => [Bool](../types/Bool.md), 'need_phone_number' => [Bool](../types/Bool.md), 'need_email' => [Bool](../types/Bool.md), 'need_shipping_address' => [Bool](../types/Bool.md), 'is_flexible' => [Bool](../types/Bool.md), \];<a name="invoice"></a>  

***
<br><br>[$keyboardButton](../constructors/keyboardButton.md) = \['text' => [string](../types/string.md), 'type' => [KeyboardButtonType](../types/KeyboardButtonType.md), \];<a name="keyboardButton"></a>  

***
<br><br>[$keyboardButtonTypeRequestLocation](../constructors/keyboardButtonTypeRequestLocation.md) = \[\];<a name="keyboardButtonTypeRequestLocation"></a>  

***
<br><br>[$keyboardButtonTypeRequestPhoneNumber](../constructors/keyboardButtonTypeRequestPhoneNumber.md) = \[\];<a name="keyboardButtonTypeRequestPhoneNumber"></a>  

***
<br><br>[$keyboardButtonTypeText](../constructors/keyboardButtonTypeText.md) = \[\];<a name="keyboardButtonTypeText"></a>  

***
<br><br>[$labeledPrice](../constructors/labeledPrice.md) = \['label' => [string](../types/string.md), 'amount' => [int53](../constructors/int53.md), \];<a name="labeledPrice"></a>  

***
<br><br>[$linkStateContact](../constructors/linkStateContact.md) = \[\];<a name="linkStateContact"></a>  

***
<br><br>[$linkStateKnowsPhoneNumber](../constructors/linkStateKnowsPhoneNumber.md) = \[\];<a name="linkStateKnowsPhoneNumber"></a>  

***
<br><br>[$linkStateNone](../constructors/linkStateNone.md) = \[\];<a name="linkStateNone"></a>  

***
<br><br>[$location](../constructors/location.md) = \['latitude' => [double](../types/double.md), 'longitude' => [double](../types/double.md), \];<a name="location"></a>  

***
<br><br>[$maskPosition](../constructors/maskPosition.md) = \['point' => [int](../types/int.md), 'x_shift' => [double](../types/double.md), 'y_shift' => [double](../types/double.md), 'scale' => [double](../types/double.md), \];<a name="maskPosition"></a>  

***
<br><br>[$message](../constructors/message.md) = \['id' => [int53](../constructors/int53.md), 'sender_user_id' => [int](../types/int.md), 'chat_id' => [int53](../constructors/int53.md), 'send_state' => [MessageSendState](../types/MessageSendState.md), 'can_be_edited' => [Bool](../types/Bool.md), 'can_be_forwarded' => [Bool](../types/Bool.md), 'can_be_deleted_only_for_self' => [Bool](../types/Bool.md), 'can_be_deleted_for_everyone' => [Bool](../types/Bool.md), 'is_post' => [Bool](../types/Bool.md), 'date' => [int](../types/int.md), 'edit_date' => [int](../types/int.md), 'forward_info' => [MessageForwardInfo](../types/MessageForwardInfo.md), 'reply_to_message_id' => [int53](../constructors/int53.md), 'ttl' => [int](../types/int.md), 'ttl_expires_in' => [double](../types/double.md), 'via_bot_user_id' => [int](../types/int.md), 'author_signature' => [string](../types/string.md), 'views' => [int](../types/int.md), 'content' => [MessageContent](../types/MessageContent.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="message"></a>  

***
<br><br>[$messageAnimation](../constructors/messageAnimation.md) = \['animation' => [animation](../constructors/animation.md), 'caption' => [string](../types/string.md), \];<a name="messageAnimation"></a>  

***
<br><br>[$messageAudio](../constructors/messageAudio.md) = \['audio' => [audio](../constructors/audio.md), 'caption' => [string](../types/string.md), \];<a name="messageAudio"></a>  

***
<br><br>[$messageCall](../constructors/messageCall.md) = \['discard_reason' => [CallDiscardReason](../types/CallDiscardReason.md), 'duration' => [int](../types/int.md), \];<a name="messageCall"></a>  

***
<br><br>[$messageChannelChatCreate](../constructors/messageChannelChatCreate.md) = \['title' => [string](../types/string.md), \];<a name="messageChannelChatCreate"></a>  

***
<br><br>[$messageChatAddMembers](../constructors/messageChatAddMembers.md) = \['member_user_ids' => \[[int](../types/int.md)\], \];<a name="messageChatAddMembers"></a>  

***
<br><br>[$messageChatChangePhoto](../constructors/messageChatChangePhoto.md) = \['photo' => [photo](../constructors/photo.md), \];<a name="messageChatChangePhoto"></a>  

***
<br><br>[$messageChatChangeTitle](../constructors/messageChatChangeTitle.md) = \['title' => [string](../types/string.md), \];<a name="messageChatChangeTitle"></a>  

***
<br><br>[$messageChatDeleteMember](../constructors/messageChatDeleteMember.md) = \['user_id' => [int](../types/int.md), \];<a name="messageChatDeleteMember"></a>  

***
<br><br>[$messageChatDeletePhoto](../constructors/messageChatDeletePhoto.md) = \[\];<a name="messageChatDeletePhoto"></a>  

***
<br><br>[$messageChatJoinByLink](../constructors/messageChatJoinByLink.md) = \[\];<a name="messageChatJoinByLink"></a>  

***
<br><br>[$messageChatMigrateFrom](../constructors/messageChatMigrateFrom.md) = \['title' => [string](../types/string.md), 'group_id' => [int](../types/int.md), \];<a name="messageChatMigrateFrom"></a>  

***
<br><br>[$messageChatMigrateTo](../constructors/messageChatMigrateTo.md) = \['channel_id' => [int](../types/int.md), \];<a name="messageChatMigrateTo"></a>  

***
<br><br>[$messageChatSetTtl](../constructors/messageChatSetTtl.md) = \['ttl' => [int](../types/int.md), \];<a name="messageChatSetTtl"></a>  

***
<br><br>[$messageContact](../constructors/messageContact.md) = \['contact' => [contact](../constructors/contact.md), \];<a name="messageContact"></a>  

***
<br><br>[$messageContactRegistered](../constructors/messageContactRegistered.md) = \[\];<a name="messageContactRegistered"></a>  

***
<br><br>[$messageDocument](../constructors/messageDocument.md) = \['document' => [document](../constructors/document.md), 'caption' => [string](../types/string.md), \];<a name="messageDocument"></a>  

***
<br><br>[$messageExpiredPhoto](../constructors/messageExpiredPhoto.md) = \[\];<a name="messageExpiredPhoto"></a>  

***
<br><br>[$messageExpiredVideo](../constructors/messageExpiredVideo.md) = \[\];<a name="messageExpiredVideo"></a>  

***
<br><br>[$messageForwardedFromUser](../constructors/messageForwardedFromUser.md) = \['sender_user_id' => [int](../types/int.md), 'date' => [int](../types/int.md), \];<a name="messageForwardedFromUser"></a>  

***
<br><br>[$messageForwardedPost](../constructors/messageForwardedPost.md) = \['chat_id' => [int53](../constructors/int53.md), 'author_signature' => [string](../types/string.md), 'date' => [int](../types/int.md), 'message_id' => [int53](../constructors/int53.md), \];<a name="messageForwardedPost"></a>  

***
<br><br>[$messageGame](../constructors/messageGame.md) = \['game' => [game](../constructors/game.md), \];<a name="messageGame"></a>  

***
<br><br>[$messageGameScore](../constructors/messageGameScore.md) = \['game_message_id' => [int53](../constructors/int53.md), 'game_id' => [long](../types/long.md), 'score' => [int](../types/int.md), \];<a name="messageGameScore"></a>  

***
<br><br>[$messageGroupChatCreate](../constructors/messageGroupChatCreate.md) = \['title' => [string](../types/string.md), 'member_user_ids' => \[[int](../types/int.md)\], \];<a name="messageGroupChatCreate"></a>  

***
<br><br>[$messageInvoice](../constructors/messageInvoice.md) = \['title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'currency' => [string](../types/string.md), 'total_amount' => [int53](../constructors/int53.md), 'start_parameter' => [string](../types/string.md), 'is_test' => [Bool](../types/Bool.md), 'need_shipping_address' => [Bool](../types/Bool.md), 'receipt_message_id' => [int53](../constructors/int53.md), \];<a name="messageInvoice"></a>  

***
<br><br>[$messageIsBeingSent](../constructors/messageIsBeingSent.md) = \[\];<a name="messageIsBeingSent"></a>  

***
<br><br>[$messageIsFailedToSend](../constructors/messageIsFailedToSend.md) = \[\];<a name="messageIsFailedToSend"></a>  

***
<br><br>[$messageIsIncoming](../constructors/messageIsIncoming.md) = \[\];<a name="messageIsIncoming"></a>  

***
<br><br>[$messageIsSuccessfullySent](../constructors/messageIsSuccessfullySent.md) = \[\];<a name="messageIsSuccessfullySent"></a>  

***
<br><br>[$messageLocation](../constructors/messageLocation.md) = \['location' => [location](../constructors/location.md), \];<a name="messageLocation"></a>  

***
<br><br>[$messagePaymentSuccessful](../constructors/messagePaymentSuccessful.md) = \['currency' => [string](../types/string.md), 'total_amount' => [int53](../constructors/int53.md), \];<a name="messagePaymentSuccessful"></a>  

***
<br><br>[$messagePaymentSuccessfulBot](../constructors/messagePaymentSuccessfulBot.md) = \['currency' => [string](../types/string.md), 'total_amount' => [int53](../constructors/int53.md), 'invoice_payload' => [bytes](../types/bytes.md), 'shipping_option_id' => [string](../types/string.md), 'order_info' => [orderInfo](../constructors/orderInfo.md), 'telegram_payment_charge_id' => [string](../types/string.md), 'provider_payment_charge_id' => [string](../types/string.md), \];<a name="messagePaymentSuccessfulBot"></a>  

***
<br><br>[$messagePhoto](../constructors/messagePhoto.md) = \['photo' => [photo](../constructors/photo.md), 'caption' => [string](../types/string.md), \];<a name="messagePhoto"></a>  

***
<br><br>[$messagePinMessage](../constructors/messagePinMessage.md) = \['message_id' => [int53](../constructors/int53.md), \];<a name="messagePinMessage"></a>  

***
<br><br>[$messageScreenshotTaken](../constructors/messageScreenshotTaken.md) = \[\];<a name="messageScreenshotTaken"></a>  

***
<br><br>[$messageSticker](../constructors/messageSticker.md) = \['sticker' => [sticker](../constructors/sticker.md), \];<a name="messageSticker"></a>  

***
<br><br>[$messageText](../constructors/messageText.md) = \['text' => [string](../types/string.md), 'entities' => \[[textEntity](../constructors/textEntity.md)\], 'web_page' => [webPage](../constructors/webPage.md), \];<a name="messageText"></a>  

***
<br><br>[$messageUnsupported](../constructors/messageUnsupported.md) = \[\];<a name="messageUnsupported"></a>  

***
<br><br>[$messageVenue](../constructors/messageVenue.md) = \['venue' => [venue](../constructors/venue.md), \];<a name="messageVenue"></a>  

***
<br><br>[$messageVideo](../constructors/messageVideo.md) = \['video' => [video](../constructors/video.md), 'caption' => [string](../types/string.md), \];<a name="messageVideo"></a>  

***
<br><br>[$messageVideoNote](../constructors/messageVideoNote.md) = \['video_note' => [videoNote](../constructors/videoNote.md), 'is_viewed' => [Bool](../types/Bool.md), \];<a name="messageVideoNote"></a>  

***
<br><br>[$messageVoice](../constructors/messageVoice.md) = \['voice' => [voice](../constructors/voice.md), 'caption' => [string](../types/string.md), 'is_listened' => [Bool](../types/Bool.md), \];<a name="messageVoice"></a>  

***
<br><br>[$messages](../constructors/messages.md) = \['total_count' => [int](../types/int.md), 'messages' => \[[message](../constructors/message.md)\], \];<a name="messages"></a>  

***
<br><br>[$networkStatistics](../constructors/networkStatistics.md) = \['since_date' => [int](../types/int.md), 'entries' => \[[NetworkStatisticsEntry](../types/NetworkStatisticsEntry.md)\], \];<a name="networkStatistics"></a>  

***
<br><br>[$networkStatisticsEntryCall](../constructors/networkStatisticsEntryCall.md) = \['network_type' => [NetworkType](../types/NetworkType.md), 'sent_bytes' => [int53](../constructors/int53.md), 'received_bytes' => [int53](../constructors/int53.md), 'duration' => [double](../types/double.md), \];<a name="networkStatisticsEntryCall"></a>  

***
<br><br>[$networkStatisticsEntryFile](../constructors/networkStatisticsEntryFile.md) = \['file_type' => [FileType](../types/FileType.md), 'network_type' => [NetworkType](../types/NetworkType.md), 'sent_bytes' => [int53](../constructors/int53.md), 'received_bytes' => [int53](../constructors/int53.md), \];<a name="networkStatisticsEntryFile"></a>  

***
<br><br>[$networkTypeMobile](../constructors/networkTypeMobile.md) = \[\];<a name="networkTypeMobile"></a>  

***
<br><br>[$networkTypeMobileRoaming](../constructors/networkTypeMobileRoaming.md) = \[\];<a name="networkTypeMobileRoaming"></a>  

***
<br><br>[$networkTypeNone](../constructors/networkTypeNone.md) = \[\];<a name="networkTypeNone"></a>  

***
<br><br>[$networkTypeOther](../constructors/networkTypeOther.md) = \[\];<a name="networkTypeOther"></a>  

***
<br><br>[$networkTypeWiFi](../constructors/networkTypeWiFi.md) = \[\];<a name="networkTypeWiFi"></a>  

***
<br><br>[$notificationSettings](../constructors/notificationSettings.md) = \['mute_for' => [int](../types/int.md), 'sound' => [string](../types/string.md), 'show_preview' => [Bool](../types/Bool.md), \];<a name="notificationSettings"></a>  

***
<br><br>[$notificationSettingsScopeAllChats](../constructors/notificationSettingsScopeAllChats.md) = \[\];<a name="notificationSettingsScopeAllChats"></a>  

***
<br><br>[$notificationSettingsScopeChat](../constructors/notificationSettingsScopeChat.md) = \['chat_id' => [int53](../constructors/int53.md), \];<a name="notificationSettingsScopeChat"></a>  

***
<br><br>[$notificationSettingsScopeGroupChats](../constructors/notificationSettingsScopeGroupChats.md) = \[\];<a name="notificationSettingsScopeGroupChats"></a>  

***
<br><br>[$notificationSettingsScopePrivateChats](../constructors/notificationSettingsScopePrivateChats.md) = \[\];<a name="notificationSettingsScopePrivateChats"></a>  

***
<br><br>[$ok](../constructors/ok.md) = \[\];<a name="ok"></a>  

***
<br><br>[$optionValueBoolean](../constructors/optionValueBoolean.md) = \['value' => [Bool](../types/Bool.md), \];<a name="optionValueBoolean"></a>  

***
<br><br>[$optionValueEmpty](../constructors/optionValueEmpty.md) = \[\];<a name="optionValueEmpty"></a>  

***
<br><br>[$optionValueInteger](../constructors/optionValueInteger.md) = \['value' => [int](../types/int.md), \];<a name="optionValueInteger"></a>  

***
<br><br>[$optionValueString](../constructors/optionValueString.md) = \['value' => [string](../types/string.md), \];<a name="optionValueString"></a>  

***
<br><br>[$orderInfo](../constructors/orderInfo.md) = \['name' => [string](../types/string.md), 'phone_number' => [string](../types/string.md), 'email' => [string](../types/string.md), 'shipping_address' => [shippingAddress](../constructors/shippingAddress.md), \];<a name="orderInfo"></a>  

***
<br><br>[$pageBlockAnchor](../constructors/pageBlockAnchor.md) = \['name' => [string](../types/string.md), \];<a name="pageBlockAnchor"></a>  

***
<br><br>[$pageBlockAnimation](../constructors/pageBlockAnimation.md) = \['animation' => [animation](../constructors/animation.md), 'caption' => [RichText](../types/RichText.md), 'need_autoplay' => [Bool](../types/Bool.md), \];<a name="pageBlockAnimation"></a>  

***
<br><br>[$pageBlockAudio](../constructors/pageBlockAudio.md) = \['audio' => [audio](../constructors/audio.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockAudio"></a>  

***
<br><br>[$pageBlockAuthorDate](../constructors/pageBlockAuthorDate.md) = \['author' => [RichText](../types/RichText.md), 'publish_date' => [int](../types/int.md), \];<a name="pageBlockAuthorDate"></a>  

***
<br><br>[$pageBlockBlockQuote](../constructors/pageBlockBlockQuote.md) = \['text' => [RichText](../types/RichText.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockBlockQuote"></a>  

***
<br><br>[$pageBlockChatLink](../constructors/pageBlockChatLink.md) = \['title' => [string](../types/string.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), 'username' => [string](../types/string.md), \];<a name="pageBlockChatLink"></a>  

***
<br><br>[$pageBlockCollage](../constructors/pageBlockCollage.md) = \['page_blocks' => \[[PageBlock](../types/PageBlock.md)\], 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockCollage"></a>  

***
<br><br>[$pageBlockCover](../constructors/pageBlockCover.md) = \['cover' => [PageBlock](../types/PageBlock.md), \];<a name="pageBlockCover"></a>  

***
<br><br>[$pageBlockDivider](../constructors/pageBlockDivider.md) = \[\];<a name="pageBlockDivider"></a>  

***
<br><br>[$pageBlockEmbedded](../constructors/pageBlockEmbedded.md) = \['url' => [string](../types/string.md), 'html' => [string](../types/string.md), 'poster_photo' => [photo](../constructors/photo.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'caption' => [RichText](../types/RichText.md), 'is_full_width' => [Bool](../types/Bool.md), 'allow_scrolling' => [Bool](../types/Bool.md), \];<a name="pageBlockEmbedded"></a>  

***
<br><br>[$pageBlockEmbeddedPost](../constructors/pageBlockEmbeddedPost.md) = \['url' => [string](../types/string.md), 'author' => [string](../types/string.md), 'author_photo' => [photo](../constructors/photo.md), 'date' => [int](../types/int.md), 'page_blocks' => \[[PageBlock](../types/PageBlock.md)\], 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockEmbeddedPost"></a>  

***
<br><br>[$pageBlockFooter](../constructors/pageBlockFooter.md) = \['footer' => [RichText](../types/RichText.md), \];<a name="pageBlockFooter"></a>  

***
<br><br>[$pageBlockHeader](../constructors/pageBlockHeader.md) = \['header' => [RichText](../types/RichText.md), \];<a name="pageBlockHeader"></a>  

***
<br><br>[$pageBlockList](../constructors/pageBlockList.md) = \['items' => \[[RichText](../types/RichText.md)\], 'is_ordered' => [Bool](../types/Bool.md), \];<a name="pageBlockList"></a>  

***
<br><br>[$pageBlockParagraph](../constructors/pageBlockParagraph.md) = \['text' => [RichText](../types/RichText.md), \];<a name="pageBlockParagraph"></a>  

***
<br><br>[$pageBlockPhoto](../constructors/pageBlockPhoto.md) = \['photo' => [photo](../constructors/photo.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockPhoto"></a>  

***
<br><br>[$pageBlockPreformatted](../constructors/pageBlockPreformatted.md) = \['text' => [RichText](../types/RichText.md), 'language' => [string](../types/string.md), \];<a name="pageBlockPreformatted"></a>  

***
<br><br>[$pageBlockPullQuote](../constructors/pageBlockPullQuote.md) = \['text' => [RichText](../types/RichText.md), 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockPullQuote"></a>  

***
<br><br>[$pageBlockSlideshow](../constructors/pageBlockSlideshow.md) = \['page_blocks' => \[[PageBlock](../types/PageBlock.md)\], 'caption' => [RichText](../types/RichText.md), \];<a name="pageBlockSlideshow"></a>  

***
<br><br>[$pageBlockSubheader](../constructors/pageBlockSubheader.md) = \['subheader' => [RichText](../types/RichText.md), \];<a name="pageBlockSubheader"></a>  

***
<br><br>[$pageBlockSubtitle](../constructors/pageBlockSubtitle.md) = \['subtitle' => [RichText](../types/RichText.md), \];<a name="pageBlockSubtitle"></a>  

***
<br><br>[$pageBlockTitle](../constructors/pageBlockTitle.md) = \['title' => [RichText](../types/RichText.md), \];<a name="pageBlockTitle"></a>  

***
<br><br>[$pageBlockVideo](../constructors/pageBlockVideo.md) = \['video' => [video](../constructors/video.md), 'caption' => [RichText](../types/RichText.md), 'need_autoplay' => [Bool](../types/Bool.md), 'is_looped' => [Bool](../types/Bool.md), \];<a name="pageBlockVideo"></a>  

***
<br><br>[$passwordRecoveryInfo](../constructors/passwordRecoveryInfo.md) = \['recovery_email_pattern' => [string](../types/string.md), \];<a name="passwordRecoveryInfo"></a>  

***
<br><br>[$passwordState](../constructors/passwordState.md) = \['has_password' => [Bool](../types/Bool.md), 'password_hint' => [string](../types/string.md), 'has_recovery_email' => [Bool](../types/Bool.md), 'unconfirmed_recovery_email_pattern' => [string](../types/string.md), \];<a name="passwordState"></a>  

***
<br><br>[$paymentForm](../constructors/paymentForm.md) = \['invoice' => [invoice](../constructors/invoice.md), 'url' => [string](../types/string.md), 'payments_provider' => [paymentsProviderStripe](../constructors/paymentsProviderStripe.md), 'saved_order_info' => [orderInfo](../constructors/orderInfo.md), 'saved_credentials' => [savedCredentials](../constructors/savedCredentials.md), 'can_save_credentials' => [Bool](../types/Bool.md), 'need_password' => [Bool](../types/Bool.md), \];<a name="paymentForm"></a>  

***
<br><br>[$paymentReceipt](../constructors/paymentReceipt.md) = \['date' => [int](../types/int.md), 'payments_provider_user_id' => [int](../types/int.md), 'invoice' => [invoice](../constructors/invoice.md), 'order_info' => [orderInfo](../constructors/orderInfo.md), 'shipping_option' => [shippingOption](../constructors/shippingOption.md), 'credentials_title' => [string](../types/string.md), \];<a name="paymentReceipt"></a>  

***
<br><br>[$paymentResult](../constructors/paymentResult.md) = \['success' => [Bool](../types/Bool.md), 'verification_url' => [string](../types/string.md), \];<a name="paymentResult"></a>  

***
<br><br>[$paymentsProviderStripe](../constructors/paymentsProviderStripe.md) = \['publishable_key' => [string](../types/string.md), 'need_country' => [Bool](../types/Bool.md), 'need_zip' => [Bool](../types/Bool.md), 'need_cardholder_name' => [Bool](../types/Bool.md), \];<a name="paymentsProviderStripe"></a>  

***
<br><br>[$photo](../constructors/photo.md) = \['id' => [long](../types/long.md), 'has_stickers' => [Bool](../types/Bool.md), 'sizes' => \[[photoSize](../constructors/photoSize.md)\], \];<a name="photo"></a>  

***
<br><br>[$photoSize](../constructors/photoSize.md) = \['type' => [string](../types/string.md), 'photo' => [file](../constructors/file.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), \];<a name="photoSize"></a>  

***
<br><br>[$privacyKeyCall](../constructors/privacyKeyCall.md) = \[\];<a name="privacyKeyCall"></a>  

***
<br><br>[$privacyKeyChatInvite](../constructors/privacyKeyChatInvite.md) = \[\];<a name="privacyKeyChatInvite"></a>  

***
<br><br>[$privacyKeyUserStatus](../constructors/privacyKeyUserStatus.md) = \[\];<a name="privacyKeyUserStatus"></a>  

***
<br><br>[$privacyRuleAllowAll](../constructors/privacyRuleAllowAll.md) = \[\];<a name="privacyRuleAllowAll"></a>  

***
<br><br>[$privacyRuleAllowContacts](../constructors/privacyRuleAllowContacts.md) = \[\];<a name="privacyRuleAllowContacts"></a>  

***
<br><br>[$privacyRuleAllowUsers](../constructors/privacyRuleAllowUsers.md) = \['user_ids' => \[[int](../types/int.md)\], \];<a name="privacyRuleAllowUsers"></a>  

***
<br><br>[$privacyRuleDisallowAll](../constructors/privacyRuleDisallowAll.md) = \[\];<a name="privacyRuleDisallowAll"></a>  

***
<br><br>[$privacyRuleDisallowContacts](../constructors/privacyRuleDisallowContacts.md) = \[\];<a name="privacyRuleDisallowContacts"></a>  

***
<br><br>[$privacyRuleDisallowUsers](../constructors/privacyRuleDisallowUsers.md) = \['user_ids' => \[[int](../types/int.md)\], \];<a name="privacyRuleDisallowUsers"></a>  

***
<br><br>[$privacyRules](../constructors/privacyRules.md) = \['rules' => \[[PrivacyRule](../types/PrivacyRule.md)\], \];<a name="privacyRules"></a>  

***
<br><br>[$profilePhoto](../constructors/profilePhoto.md) = \['id' => [long](../types/long.md), 'small' => [file](../constructors/file.md), 'big' => [file](../constructors/file.md), \];<a name="profilePhoto"></a>  

***
<br><br>[$proxyEmpty](../constructors/proxyEmpty.md) = \[\];<a name="proxyEmpty"></a>  

***
<br><br>[$proxySocks5](../constructors/proxySocks5.md) = \['server' => [string](../types/string.md), 'port' => [int](../types/int.md), 'username' => [string](../types/string.md), 'password' => [string](../types/string.md), \];<a name="proxySocks5"></a>  

***
<br><br>[$publicMessageLink](../constructors/publicMessageLink.md) = \['url' => [string](../types/string.md), \];<a name="publicMessageLink"></a>  

***
<br><br>[$recoveryEmail](../constructors/recoveryEmail.md) = \['recovery_email' => [string](../types/string.md), \];<a name="recoveryEmail"></a>  

***
<br><br>[$replyMarkupForceReply](../constructors/replyMarkupForceReply.md) = \['personal' => [Bool](../types/Bool.md), \];<a name="replyMarkupForceReply"></a>  

***
<br><br>[$replyMarkupInlineKeyboard](../constructors/replyMarkupInlineKeyboard.md) = \['rows' => \[[inlineKeyboardButton>](../constructors/inlineKeyboardButton>.md)\], \];<a name="replyMarkupInlineKeyboard"></a>  

***
<br><br>[$replyMarkupRemoveKeyboard](../constructors/replyMarkupRemoveKeyboard.md) = \['personal' => [Bool](../types/Bool.md), \];<a name="replyMarkupRemoveKeyboard"></a>  

***
<br><br>[$replyMarkupShowKeyboard](../constructors/replyMarkupShowKeyboard.md) = \['rows' => \[[keyboardButton>](../constructors/keyboardButton>.md)\], 'resize_keyboard' => [Bool](../types/Bool.md), 'one_time' => [Bool](../types/Bool.md), 'personal' => [Bool](../types/Bool.md), \];<a name="replyMarkupShowKeyboard"></a>  

***
<br><br>[$richTextBold](../constructors/richTextBold.md) = \['text' => [RichText](../types/RichText.md), \];<a name="richTextBold"></a>  

***
<br><br>[$richTextConcatenation](../constructors/richTextConcatenation.md) = \['texts' => \[[RichText](../types/RichText.md)\], \];<a name="richTextConcatenation"></a>  

***
<br><br>[$richTextEmail](../constructors/richTextEmail.md) = \['text' => [RichText](../types/RichText.md), 'email' => [string](../types/string.md), \];<a name="richTextEmail"></a>  

***
<br><br>[$richTextFixed](../constructors/richTextFixed.md) = \['text' => [RichText](../types/RichText.md), \];<a name="richTextFixed"></a>  

***
<br><br>[$richTextItalic](../constructors/richTextItalic.md) = \['text' => [RichText](../types/RichText.md), \];<a name="richTextItalic"></a>  

***
<br><br>[$richTextPlain](../constructors/richTextPlain.md) = \['text' => [string](../types/string.md), \];<a name="richTextPlain"></a>  

***
<br><br>[$richTextStrikethrough](../constructors/richTextStrikethrough.md) = \['text' => [RichText](../types/RichText.md), \];<a name="richTextStrikethrough"></a>  

***
<br><br>[$richTextUnderline](../constructors/richTextUnderline.md) = \['text' => [RichText](../types/RichText.md), \];<a name="richTextUnderline"></a>  

***
<br><br>[$richTextUrl](../constructors/richTextUrl.md) = \['text' => [RichText](../types/RichText.md), 'url' => [string](../types/string.md), \];<a name="richTextUrl"></a>  

***
<br><br>[$savedCredentials](../constructors/savedCredentials.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), \];<a name="savedCredentials"></a>  

***
<br><br>[$searchMessagesFilterAnimation](../constructors/searchMessagesFilterAnimation.md) = \[\];<a name="searchMessagesFilterAnimation"></a>  

***
<br><br>[$searchMessagesFilterAudio](../constructors/searchMessagesFilterAudio.md) = \[\];<a name="searchMessagesFilterAudio"></a>  

***
<br><br>[$searchMessagesFilterCall](../constructors/searchMessagesFilterCall.md) = \[\];<a name="searchMessagesFilterCall"></a>  

***
<br><br>[$searchMessagesFilterChatPhoto](../constructors/searchMessagesFilterChatPhoto.md) = \[\];<a name="searchMessagesFilterChatPhoto"></a>  

***
<br><br>[$searchMessagesFilterDocument](../constructors/searchMessagesFilterDocument.md) = \[\];<a name="searchMessagesFilterDocument"></a>  

***
<br><br>[$searchMessagesFilterEmpty](../constructors/searchMessagesFilterEmpty.md) = \[\];<a name="searchMessagesFilterEmpty"></a>  

***
<br><br>[$searchMessagesFilterMissedCall](../constructors/searchMessagesFilterMissedCall.md) = \[\];<a name="searchMessagesFilterMissedCall"></a>  

***
<br><br>[$searchMessagesFilterPhoto](../constructors/searchMessagesFilterPhoto.md) = \[\];<a name="searchMessagesFilterPhoto"></a>  

***
<br><br>[$searchMessagesFilterPhotoAndVideo](../constructors/searchMessagesFilterPhotoAndVideo.md) = \[\];<a name="searchMessagesFilterPhotoAndVideo"></a>  

***
<br><br>[$searchMessagesFilterUrl](../constructors/searchMessagesFilterUrl.md) = \[\];<a name="searchMessagesFilterUrl"></a>  

***
<br><br>[$searchMessagesFilterVideo](../constructors/searchMessagesFilterVideo.md) = \[\];<a name="searchMessagesFilterVideo"></a>  

***
<br><br>[$searchMessagesFilterVideoNote](../constructors/searchMessagesFilterVideoNote.md) = \[\];<a name="searchMessagesFilterVideoNote"></a>  

***
<br><br>[$searchMessagesFilterVoice](../constructors/searchMessagesFilterVoice.md) = \[\];<a name="searchMessagesFilterVoice"></a>  

***
<br><br>[$searchMessagesFilterVoiceAndVideoNote](../constructors/searchMessagesFilterVoiceAndVideoNote.md) = \[\];<a name="searchMessagesFilterVoiceAndVideoNote"></a>  

***
<br><br>[$secretChat](../constructors/secretChat.md) = \['id' => [int](../types/int.md), 'user_id' => [int](../types/int.md), 'state' => [int](../types/int.md), 'is_outbound' => [Bool](../types/Bool.md), 'ttl' => [int](../types/int.md), 'key_hash' => [bytes](../types/bytes.md), 'layer' => [int](../types/int.md), \];<a name="secretChat"></a>  

***
<br><br>[$session](../constructors/session.md) = \['id' => [long](../types/long.md), 'is_current' => [Bool](../types/Bool.md), 'app_id' => [int](../types/int.md), 'app_name' => [string](../types/string.md), 'app_version' => [string](../types/string.md), 'is_official_app' => [Bool](../types/Bool.md), 'device_model' => [string](../types/string.md), 'platform' => [string](../types/string.md), 'system_version' => [string](../types/string.md), 'log_in_date' => [int](../types/int.md), 'last_active_date' => [int](../types/int.md), 'ip' => [string](../types/string.md), 'country' => [string](../types/string.md), 'region' => [string](../types/string.md), \];<a name="session"></a>  

***
<br><br>[$sessions](../constructors/sessions.md) = \['sessions' => \[[session](../constructors/session.md)\], \];<a name="sessions"></a>  

***
<br><br>[$shippingAddress](../constructors/shippingAddress.md) = \['country_code' => [string](../types/string.md), 'state' => [string](../types/string.md), 'city' => [string](../types/string.md), 'street_line1' => [string](../types/string.md), 'street_line2' => [string](../types/string.md), 'post_code' => [string](../types/string.md), \];<a name="shippingAddress"></a>  

***
<br><br>[$shippingOption](../constructors/shippingOption.md) = \['id' => [string](../types/string.md), 'title' => [string](../types/string.md), 'prices' => \[[labeledPrice](../constructors/labeledPrice.md)\], \];<a name="shippingOption"></a>  

***
<br><br>[$sticker](../constructors/sticker.md) = \['set_id' => [long](../types/long.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'emoji' => [string](../types/string.md), 'is_mask' => [Bool](../types/Bool.md), 'mask_position' => [maskPosition](../constructors/maskPosition.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'sticker' => [file](../constructors/file.md), \];<a name="sticker"></a>  

***
<br><br>[$stickerEmojis](../constructors/stickerEmojis.md) = \['emojis' => \[[string](../types/string.md)\], \];<a name="stickerEmojis"></a>  

***
<br><br>[$stickerSet](../constructors/stickerSet.md) = \['id' => [long](../types/long.md), 'title' => [string](../types/string.md), 'name' => [string](../types/string.md), 'is_installed' => [Bool](../types/Bool.md), 'is_archived' => [Bool](../types/Bool.md), 'is_official' => [Bool](../types/Bool.md), 'is_masks' => [Bool](../types/Bool.md), 'is_viewed' => [Bool](../types/Bool.md), 'stickers' => \[[sticker](../constructors/sticker.md)\], 'emojis' => \[[stickerEmojis](../constructors/stickerEmojis.md)\], \];<a name="stickerSet"></a>  

***
<br><br>[$stickerSetInfo](../constructors/stickerSetInfo.md) = \['id' => [long](../types/long.md), 'title' => [string](../types/string.md), 'name' => [string](../types/string.md), 'is_installed' => [Bool](../types/Bool.md), 'is_archived' => [Bool](../types/Bool.md), 'is_official' => [Bool](../types/Bool.md), 'is_masks' => [Bool](../types/Bool.md), 'is_viewed' => [Bool](../types/Bool.md), 'size' => [int](../types/int.md), 'covers' => \[[sticker](../constructors/sticker.md)\], \];<a name="stickerSetInfo"></a>  

***
<br><br>[$stickerSets](../constructors/stickerSets.md) = \['total_count' => [int](../types/int.md), 'sets' => \[[stickerSetInfo](../constructors/stickerSetInfo.md)\], \];<a name="stickerSets"></a>  

***
<br><br>[$stickers](../constructors/stickers.md) = \['stickers' => \[[sticker](../constructors/sticker.md)\], \];<a name="stickers"></a>  

***
<br><br>[$storageStatistics](../constructors/storageStatistics.md) = \['size' => [int53](../constructors/int53.md), 'count' => [int](../types/int.md), 'by_chat' => \[[storageStatisticsByChat](../constructors/storageStatisticsByChat.md)\], \];<a name="storageStatistics"></a>  

***
<br><br>[$storageStatisticsByChat](../constructors/storageStatisticsByChat.md) = \['chat_id' => [int53](../constructors/int53.md), 'size' => [int53](../constructors/int53.md), 'count' => [int](../types/int.md), 'by_file_type' => \[[storageStatisticsByFileType](../constructors/storageStatisticsByFileType.md)\], \];<a name="storageStatisticsByChat"></a>  

***
<br><br>[$storageStatisticsByFileType](../constructors/storageStatisticsByFileType.md) = \['file_type' => [FileType](../types/FileType.md), 'size' => [int53](../constructors/int53.md), 'count' => [int](../types/int.md), \];<a name="storageStatisticsByFileType"></a>  

***
<br><br>[$storageStatisticsFast](../constructors/storageStatisticsFast.md) = \['files_size' => [int53](../constructors/int53.md), 'files_count' => [int](../types/int.md), 'database_size' => [int53](../constructors/int53.md), \];<a name="storageStatisticsFast"></a>  

***
<br><br>[$temporaryPasswordState](../constructors/temporaryPasswordState.md) = \['has_password' => [Bool](../types/Bool.md), 'valid_for' => [int](../types/int.md), \];<a name="temporaryPasswordState"></a>  

***
<br><br>[$testBytes](../constructors/testBytes.md) = \['value' => [bytes](../types/bytes.md), \];<a name="testBytes"></a>  

***
<br><br>[$testInt](../constructors/testInt.md) = \['value' => [int](../types/int.md), \];<a name="testInt"></a>  

***
<br><br>[$testString](../constructors/testString.md) = \['value' => [string](../types/string.md), \];<a name="testString"></a>  

***
<br><br>[$testVectorInt](../constructors/testVectorInt.md) = \['value' => \[[int](../types/int.md)\], \];<a name="testVectorInt"></a>  

***
<br><br>[$testVectorIntObject](../constructors/testVectorIntObject.md) = \['value' => \[[testInt](../constructors/testInt.md)\], \];<a name="testVectorIntObject"></a>  

***
<br><br>[$testVectorString](../constructors/testVectorString.md) = \['value' => \[[string](../types/string.md)\], \];<a name="testVectorString"></a>  

***
<br><br>[$testVectorStringObject](../constructors/testVectorStringObject.md) = \['value' => \[[testString](../constructors/testString.md)\], \];<a name="testVectorStringObject"></a>  

***
<br><br>[$text](../constructors/text.md) = \['text' => [string](../types/string.md), \];<a name="text"></a>  

***
<br><br>[$textEntity](../constructors/textEntity.md) = \['offset' => [int](../types/int.md), 'length' => [int](../types/int.md), 'type' => [TextEntityType](../types/TextEntityType.md), \];<a name="textEntity"></a>  

***
<br><br>[$textEntityTypeBold](../constructors/textEntityTypeBold.md) = \[\];<a name="textEntityTypeBold"></a>  

***
<br><br>[$textEntityTypeBotCommand](../constructors/textEntityTypeBotCommand.md) = \[\];<a name="textEntityTypeBotCommand"></a>  

***
<br><br>[$textEntityTypeCode](../constructors/textEntityTypeCode.md) = \[\];<a name="textEntityTypeCode"></a>  

***
<br><br>[$textEntityTypeEmail](../constructors/textEntityTypeEmail.md) = \[\];<a name="textEntityTypeEmail"></a>  

***
<br><br>[$textEntityTypeHashtag](../constructors/textEntityTypeHashtag.md) = \[\];<a name="textEntityTypeHashtag"></a>  

***
<br><br>[$textEntityTypeItalic](../constructors/textEntityTypeItalic.md) = \[\];<a name="textEntityTypeItalic"></a>  

***
<br><br>[$textEntityTypeMention](../constructors/textEntityTypeMention.md) = \[\];<a name="textEntityTypeMention"></a>  

***
<br><br>[$textEntityTypeMentionName](../constructors/textEntityTypeMentionName.md) = \['user_id' => [int](../types/int.md), \];<a name="textEntityTypeMentionName"></a>  

***
<br><br>[$textEntityTypePre](../constructors/textEntityTypePre.md) = \[\];<a name="textEntityTypePre"></a>  

***
<br><br>[$textEntityTypePreCode](../constructors/textEntityTypePreCode.md) = \['language' => [string](../types/string.md), \];<a name="textEntityTypePreCode"></a>  

***
<br><br>[$textEntityTypeTextUrl](../constructors/textEntityTypeTextUrl.md) = \['url' => [string](../types/string.md), \];<a name="textEntityTypeTextUrl"></a>  

***
<br><br>[$textEntityTypeUrl](../constructors/textEntityTypeUrl.md) = \[\];<a name="textEntityTypeUrl"></a>  

***
<br><br>[$textParseModeHTML](../constructors/textParseModeHTML.md) = \[\];<a name="textParseModeHTML"></a>  

***
<br><br>[$textParseModeMarkdown](../constructors/textParseModeMarkdown.md) = \[\];<a name="textParseModeMarkdown"></a>  

***
<br><br>[$topChatCategoryBots](../constructors/topChatCategoryBots.md) = \[\];<a name="topChatCategoryBots"></a>  

***
<br><br>[$topChatCategoryCalls](../constructors/topChatCategoryCalls.md) = \[\];<a name="topChatCategoryCalls"></a>  

***
<br><br>[$topChatCategoryChannels](../constructors/topChatCategoryChannels.md) = \[\];<a name="topChatCategoryChannels"></a>  

***
<br><br>[$topChatCategoryGroups](../constructors/topChatCategoryGroups.md) = \[\];<a name="topChatCategoryGroups"></a>  

***
<br><br>[$topChatCategoryInlineBots](../constructors/topChatCategoryInlineBots.md) = \[\];<a name="topChatCategoryInlineBots"></a>  

***
<br><br>[$topChatCategoryUsers](../constructors/topChatCategoryUsers.md) = \[\];<a name="topChatCategoryUsers"></a>  

***
<br><br>[$updateAuthState](../constructors/updateAuthState.md) = \['auth_state' => [AuthState](../types/AuthState.md), \];<a name="updateAuthState"></a>  

***
<br><br>[$updateCall](../constructors/updateCall.md) = \['call' => [call](../constructors/call.md), \];<a name="updateCall"></a>  

***
<br><br>[$updateChannel](../constructors/updateChannel.md) = \['channel' => [channel](../constructors/channel.md), \];<a name="updateChannel"></a>  

***
<br><br>[$updateChannelFull](../constructors/updateChannelFull.md) = \['channel_id' => [int](../types/int.md), 'channel_full' => [channelFull](../constructors/channelFull.md), \];<a name="updateChannelFull"></a>  

***
<br><br>[$updateChatDraftMessage](../constructors/updateChatDraftMessage.md) = \['chat_id' => [int53](../constructors/int53.md), 'draft_message' => [draftMessage](../constructors/draftMessage.md), \];<a name="updateChatDraftMessage"></a>  

***
<br><br>[$updateChatIsPinned](../constructors/updateChatIsPinned.md) = \['chat_id' => [int53](../constructors/int53.md), 'is_pinned' => [Bool](../types/Bool.md), \];<a name="updateChatIsPinned"></a>  

***
<br><br>[$updateChatOrder](../constructors/updateChatOrder.md) = \['chat_id' => [int53](../constructors/int53.md), 'order' => [long](../types/long.md), \];<a name="updateChatOrder"></a>  

***
<br><br>[$updateChatPhoto](../constructors/updateChatPhoto.md) = \['chat_id' => [int53](../constructors/int53.md), 'photo' => [chatPhoto](../constructors/chatPhoto.md), \];<a name="updateChatPhoto"></a>  

***
<br><br>[$updateChatReadInbox](../constructors/updateChatReadInbox.md) = \['chat_id' => [int53](../constructors/int53.md), 'last_read_inbox_message_id' => [int53](../constructors/int53.md), 'unread_count' => [int](../types/int.md), \];<a name="updateChatReadInbox"></a>  

***
<br><br>[$updateChatReadOutbox](../constructors/updateChatReadOutbox.md) = \['chat_id' => [int53](../constructors/int53.md), 'last_read_outbox_message_id' => [int53](../constructors/int53.md), \];<a name="updateChatReadOutbox"></a>  

***
<br><br>[$updateChatReplyMarkup](../constructors/updateChatReplyMarkup.md) = \['chat_id' => [int53](../constructors/int53.md), 'reply_markup_message_id' => [int53](../constructors/int53.md), \];<a name="updateChatReplyMarkup"></a>  

***
<br><br>[$updateChatTitle](../constructors/updateChatTitle.md) = \['chat_id' => [int53](../constructors/int53.md), 'title' => [string](../types/string.md), \];<a name="updateChatTitle"></a>  

***
<br><br>[$updateChatTopMessage](../constructors/updateChatTopMessage.md) = \['chat_id' => [int53](../constructors/int53.md), 'top_message' => [message](../constructors/message.md), \];<a name="updateChatTopMessage"></a>  

***
<br><br>[$updateConnectionState](../constructors/updateConnectionState.md) = \['state' => [ConnectionState](../types/ConnectionState.md), \];<a name="updateConnectionState"></a>  

***
<br><br>[$updateDeleteMessages](../constructors/updateDeleteMessages.md) = \['chat_id' => [int53](../constructors/int53.md), 'message_ids' => \[[int53](../constructors/int53.md)\], \];<a name="updateDeleteMessages"></a>  

***
<br><br>[$updateFile](../constructors/updateFile.md) = \['file' => [file](../constructors/file.md), \];<a name="updateFile"></a>  

***
<br><br>[$updateFileGenerationFinish](../constructors/updateFileGenerationFinish.md) = \['file' => [file](../constructors/file.md), \];<a name="updateFileGenerationFinish"></a>  

***
<br><br>[$updateFileGenerationProgress](../constructors/updateFileGenerationProgress.md) = \['file_id' => [int](../types/int.md), 'size' => [int](../types/int.md), 'ready' => [int](../types/int.md), \];<a name="updateFileGenerationProgress"></a>  

***
<br><br>[$updateFileGenerationStart](../constructors/updateFileGenerationStart.md) = \['generation_id' => [long](../types/long.md), 'original_path' => [string](../types/string.md), 'destination_path' => [string](../types/string.md), 'conversion' => [string](../types/string.md), \];<a name="updateFileGenerationStart"></a>  

***
<br><br>[$updateFileProgress](../constructors/updateFileProgress.md) = \['file_id' => [int](../types/int.md), 'size' => [int](../types/int.md), 'ready' => [int](../types/int.md), \];<a name="updateFileProgress"></a>  

***
<br><br>[$updateGroup](../constructors/updateGroup.md) = \['group' => [group](../constructors/group.md), \];<a name="updateGroup"></a>  

***
<br><br>[$updateGroupFull](../constructors/updateGroupFull.md) = \['group_id' => [int](../types/int.md), 'group_full' => [groupFull](../constructors/groupFull.md), \];<a name="updateGroupFull"></a>  

***
<br><br>[$updateInstalledStickerSets](../constructors/updateInstalledStickerSets.md) = \['is_masks' => [Bool](../types/Bool.md), 'sticker_set_ids' => \[[long](../types/long.md)\], \];<a name="updateInstalledStickerSets"></a>  

***
<br><br>[$updateMessageContent](../constructors/updateMessageContent.md) = \['chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), 'new_content' => [MessageContent](../types/MessageContent.md), \];<a name="updateMessageContent"></a>  

***
<br><br>[$updateMessageEdited](../constructors/updateMessageEdited.md) = \['chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), 'edit_date' => [int](../types/int.md), 'reply_markup' => [ReplyMarkup](../types/ReplyMarkup.md), \];<a name="updateMessageEdited"></a>  

***
<br><br>[$updateMessageSendAcknowledged](../constructors/updateMessageSendAcknowledged.md) = \['chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), \];<a name="updateMessageSendAcknowledged"></a>  

***
<br><br>[$updateMessageSendFailed](../constructors/updateMessageSendFailed.md) = \['message' => [message](../constructors/message.md), 'old_message_id' => [int53](../constructors/int53.md), 'error_code' => [int](../types/int.md), 'error_message' => [string](../types/string.md), \];<a name="updateMessageSendFailed"></a>  

***
<br><br>[$updateMessageSendSucceeded](../constructors/updateMessageSendSucceeded.md) = \['message' => [message](../constructors/message.md), 'old_message_id' => [int53](../constructors/int53.md), \];<a name="updateMessageSendSucceeded"></a>  

***
<br><br>[$updateMessageViews](../constructors/updateMessageViews.md) = \['chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), 'views' => [int](../types/int.md), \];<a name="updateMessageViews"></a>  

***
<br><br>[$updateNewCallbackQuery](../constructors/updateNewCallbackQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), 'chat_instance' => [long](../types/long.md), 'payload' => [CallbackQueryPayload](../types/CallbackQueryPayload.md), \];<a name="updateNewCallbackQuery"></a>  

***
<br><br>[$updateNewChat](../constructors/updateNewChat.md) = \['chat' => [chat](../constructors/chat.md), \];<a name="updateNewChat"></a>  

***
<br><br>[$updateNewChosenInlineResult](../constructors/updateNewChosenInlineResult.md) = \['sender_user_id' => [int](../types/int.md), 'user_location' => [location](../constructors/location.md), 'query' => [string](../types/string.md), 'result_id' => [string](../types/string.md), 'inline_message_id' => [string](../types/string.md), \];<a name="updateNewChosenInlineResult"></a>  

***
<br><br>[$updateNewCustomEvent](../constructors/updateNewCustomEvent.md) = \['event' => [string](../types/string.md), \];<a name="updateNewCustomEvent"></a>  

***
<br><br>[$updateNewCustomQuery](../constructors/updateNewCustomQuery.md) = \['id' => [long](../types/long.md), 'data' => [string](../types/string.md), 'timeout' => [int](../types/int.md), \];<a name="updateNewCustomQuery"></a>  

***
<br><br>[$updateNewInlineCallbackQuery](../constructors/updateNewInlineCallbackQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'inline_message_id' => [string](../types/string.md), 'chat_instance' => [long](../types/long.md), 'payload' => [CallbackQueryPayload](../types/CallbackQueryPayload.md), \];<a name="updateNewInlineCallbackQuery"></a>  

***
<br><br>[$updateNewInlineQuery](../constructors/updateNewInlineQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'user_location' => [location](../constructors/location.md), 'query' => [string](../types/string.md), 'offset' => [string](../types/string.md), \];<a name="updateNewInlineQuery"></a>  

***
<br><br>[$updateNewMessage](../constructors/updateNewMessage.md) = \['message' => [message](../constructors/message.md), 'disable_notification' => [Bool](../types/Bool.md), 'contains_mention' => [Bool](../types/Bool.md), \];<a name="updateNewMessage"></a>  

***
<br><br>[$updateNewPreCheckoutQuery](../constructors/updateNewPreCheckoutQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'currency' => [string](../types/string.md), 'total_amount' => [int53](../constructors/int53.md), 'invoice_payload' => [bytes](../types/bytes.md), 'shipping_option_id' => [string](../types/string.md), 'order_info' => [orderInfo](../constructors/orderInfo.md), \];<a name="updateNewPreCheckoutQuery"></a>  

***
<br><br>[$updateNewShippingQuery](../constructors/updateNewShippingQuery.md) = \['id' => [long](../types/long.md), 'sender_user_id' => [int](../types/int.md), 'invoice_payload' => [string](../types/string.md), 'shipping_address' => [shippingAddress](../constructors/shippingAddress.md), \];<a name="updateNewShippingQuery"></a>  

***
<br><br>[$updateNotificationSettings](../constructors/updateNotificationSettings.md) = \['scope' => [NotificationSettingsScope](../types/NotificationSettingsScope.md), 'notification_settings' => [notificationSettings](../constructors/notificationSettings.md), \];<a name="updateNotificationSettings"></a>  

***
<br><br>[$updateOpenMessageContent](../constructors/updateOpenMessageContent.md) = \['chat_id' => [int53](../constructors/int53.md), 'message_id' => [int53](../constructors/int53.md), \];<a name="updateOpenMessageContent"></a>  

***
<br><br>[$updateOption](../constructors/updateOption.md) = \['name' => [string](../types/string.md), 'value' => [OptionValue](../types/OptionValue.md), \];<a name="updateOption"></a>  

***
<br><br>[$updatePrivacy](../constructors/updatePrivacy.md) = \['key' => [PrivacyKey](../types/PrivacyKey.md), 'rules' => [privacyRules](../constructors/privacyRules.md), \];<a name="updatePrivacy"></a>  

***
<br><br>[$updateRecentStickers](../constructors/updateRecentStickers.md) = \['is_attached' => [Bool](../types/Bool.md), 'sticker_ids' => \[[int](../types/int.md)\], \];<a name="updateRecentStickers"></a>  

***
<br><br>[$updateSavedAnimations](../constructors/updateSavedAnimations.md) = \['animation_ids' => \[[int](../types/int.md)\], \];<a name="updateSavedAnimations"></a>  

***
<br><br>[$updateSecretChat](../constructors/updateSecretChat.md) = \['secret_chat' => [secretChat](../constructors/secretChat.md), \];<a name="updateSecretChat"></a>  

***
<br><br>[$updateServiceNotification](../constructors/updateServiceNotification.md) = \['type' => [string](../types/string.md), 'content' => [MessageContent](../types/MessageContent.md), \];<a name="updateServiceNotification"></a>  

***
<br><br>[$updateTrendingStickerSets](../constructors/updateTrendingStickerSets.md) = \['sticker_sets' => [stickerSets](../constructors/stickerSets.md), \];<a name="updateTrendingStickerSets"></a>  

***
<br><br>[$updateUser](../constructors/updateUser.md) = \['user' => [user](../constructors/user.md), \];<a name="updateUser"></a>  

***
<br><br>[$updateUserChatAction](../constructors/updateUserChatAction.md) = \['chat_id' => [int53](../constructors/int53.md), 'user_id' => [int](../types/int.md), 'action' => [ChatAction](../types/ChatAction.md), \];<a name="updateUserChatAction"></a>  

***
<br><br>[$updateUserFull](../constructors/updateUserFull.md) = \['user_id' => [int](../types/int.md), 'user_full' => [userFull](../constructors/userFull.md), \];<a name="updateUserFull"></a>  

***
<br><br>[$updateUserStatus](../constructors/updateUserStatus.md) = \['user_id' => [int](../types/int.md), 'status' => [UserStatus](../types/UserStatus.md), \];<a name="updateUserStatus"></a>  

***
<br><br>[$user](../constructors/user.md) = \['id' => [int](../types/int.md), 'first_name' => [string](../types/string.md), 'last_name' => [string](../types/string.md), 'username' => [string](../types/string.md), 'phone_number' => [string](../types/string.md), 'status' => [UserStatus](../types/UserStatus.md), 'profile_photo' => [profilePhoto](../constructors/profilePhoto.md), 'my_link' => [LinkState](../types/LinkState.md), 'foreign_link' => [LinkState](../types/LinkState.md), 'is_verified' => [Bool](../types/Bool.md), 'restriction_reason' => [string](../types/string.md), 'have_access' => [Bool](../types/Bool.md), 'type' => [UserType](../types/UserType.md), 'language_code' => [string](../types/string.md), \];<a name="user"></a>  

***
<br><br>[$userFull](../constructors/userFull.md) = \['is_blocked' => [Bool](../types/Bool.md), 'can_be_called' => [Bool](../types/Bool.md), 'has_private_calls' => [Bool](../types/Bool.md), 'about' => [string](../types/string.md), 'common_chat_count' => [int](../types/int.md), 'bot_info' => [botInfo](../constructors/botInfo.md), \];<a name="userFull"></a>  

***
<br><br>[$userProfilePhotos](../constructors/userProfilePhotos.md) = \['total_count' => [int](../types/int.md), 'photos' => \[[photo](../constructors/photo.md)\], \];<a name="userProfilePhotos"></a>  

***
<br><br>[$userStatusEmpty](../constructors/userStatusEmpty.md) = \[\];<a name="userStatusEmpty"></a>  

***
<br><br>[$userStatusLastMonth](../constructors/userStatusLastMonth.md) = \[\];<a name="userStatusLastMonth"></a>  

***
<br><br>[$userStatusLastWeek](../constructors/userStatusLastWeek.md) = \[\];<a name="userStatusLastWeek"></a>  

***
<br><br>[$userStatusOffline](../constructors/userStatusOffline.md) = \['was_online' => [int](../types/int.md), \];<a name="userStatusOffline"></a>  

***
<br><br>[$userStatusOnline](../constructors/userStatusOnline.md) = \['expires' => [int](../types/int.md), \];<a name="userStatusOnline"></a>  

***
<br><br>[$userStatusRecently](../constructors/userStatusRecently.md) = \[\];<a name="userStatusRecently"></a>  

***
<br><br>[$userTypeBot](../constructors/userTypeBot.md) = \['can_join_group_chats' => [Bool](../types/Bool.md), 'can_read_all_group_chat_messages' => [Bool](../types/Bool.md), 'is_inline' => [Bool](../types/Bool.md), 'inline_query_placeholder' => [string](../types/string.md), 'need_location' => [Bool](../types/Bool.md), \];<a name="userTypeBot"></a>  

***
<br><br>[$userTypeDeleted](../constructors/userTypeDeleted.md) = \[\];<a name="userTypeDeleted"></a>  

***
<br><br>[$userTypeGeneral](../constructors/userTypeGeneral.md) = \[\];<a name="userTypeGeneral"></a>  

***
<br><br>[$userTypeUnknown](../constructors/userTypeUnknown.md) = \[\];<a name="userTypeUnknown"></a>  

***
<br><br>[$users](../constructors/users.md) = \['total_count' => [int](../types/int.md), 'user_ids' => \[[int](../types/int.md)\], \];<a name="users"></a>  

***
<br><br>[$validatedOrderInfo](../constructors/validatedOrderInfo.md) = \['order_info_id' => [string](../types/string.md), 'shipping_options' => \[[shippingOption](../constructors/shippingOption.md)\], \];<a name="validatedOrderInfo"></a>  

***
<br><br>[$venue](../constructors/venue.md) = \['location' => [location](../constructors/location.md), 'title' => [string](../types/string.md), 'address' => [string](../types/string.md), 'provider' => [string](../types/string.md), 'id' => [string](../types/string.md), \];<a name="venue"></a>  

***
<br><br>[$video](../constructors/video.md) = \['duration' => [int](../types/int.md), 'width' => [int](../types/int.md), 'height' => [int](../types/int.md), 'file_name' => [string](../types/string.md), 'mime_type' => [string](../types/string.md), 'has_stickers' => [Bool](../types/Bool.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'video' => [file](../constructors/file.md), \];<a name="video"></a>  

***
<br><br>[$videoNote](../constructors/videoNote.md) = \['duration' => [int](../types/int.md), 'length' => [int](../types/int.md), 'thumb' => [photoSize](../constructors/photoSize.md), 'video' => [file](../constructors/file.md), \];<a name="videoNote"></a>  

***
<br><br>[$voice](../constructors/voice.md) = \['duration' => [int](../types/int.md), 'waveform' => [bytes](../types/bytes.md), 'mime_type' => [string](../types/string.md), 'voice' => [file](../constructors/file.md), \];<a name="voice"></a>  

***
<br><br>[$wallpaper](../constructors/wallpaper.md) = \['id' => [int](../types/int.md), 'sizes' => \[[photoSize](../constructors/photoSize.md)\], 'color' => [int](../types/int.md), \];<a name="wallpaper"></a>  

***
<br><br>[$wallpapers](../constructors/wallpapers.md) = \['wallpapers' => \[[wallpaper](../constructors/wallpaper.md)\], \];<a name="wallpapers"></a>  

***
<br><br>[$webPage](../constructors/webPage.md) = \['url' => [string](../types/string.md), 'display_url' => [string](../types/string.md), 'type' => [string](../types/string.md), 'site_name' => [string](../types/string.md), 'title' => [string](../types/string.md), 'description' => [string](../types/string.md), 'photo' => [photo](../constructors/photo.md), 'embed_url' => [string](../types/string.md), 'embed_type' => [string](../types/string.md), 'embed_width' => [int](../types/int.md), 'embed_height' => [int](../types/int.md), 'duration' => [int](../types/int.md), 'author' => [string](../types/string.md), 'animation' => [animation](../constructors/animation.md), 'audio' => [audio](../constructors/audio.md), 'document' => [document](../constructors/document.md), 'sticker' => [sticker](../constructors/sticker.md), 'video' => [video](../constructors/video.md), 'video_note' => [videoNote](../constructors/videoNote.md), 'voice' => [voice](../constructors/voice.md), 'has_instant_view' => [Bool](../types/Bool.md), \];<a name="webPage"></a>  

***
<br><br>[$webPageInstantView](../constructors/webPageInstantView.md) = \['page_blocks' => \[[PageBlock](../types/PageBlock.md)\], 'is_full' => [Bool](../types/Bool.md), \];<a name="webPageInstantView"></a>  

