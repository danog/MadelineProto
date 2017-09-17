//@description Object of this type may be returned on every function call in case of the error
//@code Error code, maybe changed in the future. If code == 406, error message should not be processed in any way and shouldn't be showed to the user
//@message Error message, may be changed in the future
error code:int message:string = Error;


//@description Object of this type returns on successful function call for some functions
ok = Ok;


//@class AuthCodeType @description Provides information about the way an authentication code is delivered to the user

//@description Code is delivered through private Telegram message, which can be viewed in the other client @length Length of the code
authCodeTypeMessage length:int = AuthCodeType;

//@description Code is delivered by SMS to the specified phone number @length Length of the code
authCodeTypeSms length:int = AuthCodeType;

//@description Code is delivered by phone call to the specified phone number @length Length of the code
authCodeTypeCall length:int = AuthCodeType;

//@description Code is delivered by the immediately cancelled call to the specified phone number. Number from which the call was done is the code @pattern Pattern of the phone number from which the call will be done
authCodeTypeFlashCall pattern:string = AuthCodeType;


//@class AuthState @description Represents current authorization state of the Client

//@description TDLib needs user's phone number to authorize
authStateWaitPhoneNumber = AuthState;

//@description TDLib needs user authentication code to finish authorization @is_registered True, if user is already registered @code_type Describes the way, code was sent to the user @next_code_type Describes the way, next code will be sent to the user, nullable @timeout Timeout in seconds before code should be resent by calling resendAuthCode
authStateWaitCode is_registered:Bool code_type:AuthCodeType next_code_type:AuthCodeType timeout:int = AuthState;

//@description User is authorized but he needs to enter its password to begin to use application @password_hint Hint on password, can be empty @has_recovery_email Is recovery email set up
//@recovery_email_pattern Pattern of email to which recovery mail was sent, empty before recovery email was sent
authStateWaitPassword password_hint:string has_recovery_email:Bool recovery_email_pattern:string = AuthState;

//@description User is successfully authorized. TDLib can answer queries
authStateOk = AuthState;

//@description User is currently logging out
authStateLoggingOut = AuthState;


//@description Represents current state of the two-step verification @has_password Is password set up @password_hint Hint on password, can be empty @has_recovery_email Is recovery email set up @unconfirmed_recovery_email_pattern Pattern of email to which confirmation mail was sent
passwordState has_password:Bool password_hint:string has_recovery_email:Bool unconfirmed_recovery_email_pattern:string = PasswordState;

//@description Contains information available to the user after requesting password recovery @recovery_email_pattern Pattern of email to which recovery mail was sent
passwordRecoveryInfo recovery_email_pattern:string = PasswordRecoveryInfo;

//@description Contains information about set up recovery email @recovery_email Recovery email
recoveryEmail recovery_email:string = RecoveryEmail;


//@description Returns information about availability of temporary password, which should be used for payments @has_password True, if we have temporary password @valid_for Time left before temporary password expires, seconds
temporaryPasswordState has_password:Bool valid_for:int = TemporaryPasswordState;


//@description Represents a file
//@id Unique file identifier
//@persistent_id Persistent file identifier, if exists. Can be used across application restarts or even other devices for current logged user. If begins with "http://" or "https://", it is HTTP URL of the file. Currently, TDLib is unable to download files if only they URL is known. If downloadFile is called on a such file or it is sended to a secret chat TDLib starts file generation process by sending to the client updateFileGenerationStart with HTTP URL in the original_path and "#url#" as conversion string. Client supposed to generate the file by downloading it to the specified location
//@size File size, 0 if unknown
//@is_being_downloaded True, if the file is currently being downloaded
//@local_size Size of locally available part of the file. If size != 0 && local_size == size, full file is available locally
//@is_being_uploaded True, if the file is currently being uploaded
//@remote_size Size of remotely available part of the file. If size != 0 && remote_size == size, the file is available remotely
//@path Local path to the available file part, may be empty
file id:int persistent_id:string size:int is_being_downloaded:Bool local_size:int is_being_uploaded:Bool remote_size:int path:string = File;

//@class InputFile @description Points to some file

//@description File defined by its id @id Unique file identifier
inputFileId id:int = InputFile;

//@description File defined by its persistent id @persistent_id Persistent file identifier
inputFilePersistentId persistent_id:string = InputFile;

//@description File deifned by local path @path Local path to the file
inputFileLocal path:string = InputFile;

//@description File generated by the client @original_path Local path to a file from which the file is generated, may be empty if there is no such file @conversion String specifying conversion applied to the original file, should be persistent across application restart @expected_size Expected size of the generated file
inputFileGenerated original_path:string conversion:string expected_size:int = InputFile;


//@description Photo description @type Thumbnail type (see https://core.telegram.org/constructor/photoSize) @photo Information about photo file @width Photo width @height Photo height
photoSize type:string photo:file width:int height:int = PhotoSize;

//@description Position on a photo where a mask should be placed @point Part of a face relative to which the mask should be placed. 0 - forehead, 1 - eyes, 2 - mouth, 3 - chin
//@x_shift Shift by X-axis measured in widths of the mask scaled to the face size, from left to right. For example, choosing -1.0 will place mask just to the left of the default mask position
//@y_shift Shift by Y-axis measured in heights of the mask scaled to the face size, from top to bottom. For example, 1.0 will place the mask just below the default mask position.
//@scale Mask scaling coefficient. For example, 2.0 means double size
maskPosition point:int x_shift:double y_shift:double scale:double = MaskPosition;


//@description Represent part of the text which needs to be formatted in some unusual way @offset Offset of the entity in UTF-16 code points @length Length of the entity in UTF-16 code points @type Type of the entity
textEntity offset:int length:int type:TextEntityType = TextEntity;


//@description Describes animation file. Animation should be encoded in gif or mp4 format @duration Duration of the animation in seconds as defined by sender @width Width of the animation @height Height of the animation
//@file_name Original name of a file as defined by sender @mime_type MIME type of a file, usually "image/gif" or "video/mp4" @thumb Animation thumb, nullable @animation File with the animation
animation duration:int width:int height:int file_name:string mime_type:string thumb:photoSize animation:file = Animation;

//@description Describes audio file. Audio is usually in mp3 format @duration Duration of the audio in seconds as defined by sender @title Title of the audio as defined by sender @performer Performer of the audio as defined by sender
//@file_name Original name of a file as defined by sender @mime_type MIME type of a file as defined by sender @album_cover_thumb Thumb of the album's cover as defined by sender. Full size thumb should be extracted from the downloaded file, nullable @audio File with the audio
audio duration:int title:string performer:string file_name:string mime_type:string album_cover_thumb:photoSize audio:file = Audio;

//@description Describes document of any type @file_name Original name of a file as defined by sender @mime_type MIME type of file as defined by sender
//@thumb Document thumb as defined by sender, nullable @document File with document
document file_name:string mime_type:string thumb:photoSize document:file = Document;

//@description Describes photo @id Photo identifier, 0 for deleted photo @has_stickers True, if some stickers was added to the photo @sizes Available variants of photo of different sizes
photo id:long has_stickers:Bool sizes:vector<photoSize> = Photo;

//@description Describes sticker @set_id Identifier of sticker set to which the sticker belongs or 0 if none @width Sticker width as defined by sender @height Sticker height as defined by sender
//@emoji Emoji corresponding to the sticker @is_mask True, if the sticker is a mask @mask_position Position where the mask should be placed, nullable @thumb Sticker thumb in webp or jpeg format, nullable @sticker File with sticker
sticker set_id:long width:int height:int emoji:string is_mask:Bool mask_position:maskPosition thumb:photoSize sticker:file = Sticker;

//@description Describes video file @duration Duration of the video in seconds as defined by sender @width Video width as defined by sender @height Video height as defined by sender
//@file_name Original name of a file as defined by sender @mime_type MIME type of a file as defined by sender @has_stickers True, if some stickers was added to the photo @thumb Video thumb as defined by sender, nullable @video File with the video
video duration:int width:int height:int file_name:string mime_type:string has_stickers:Bool thumb:photoSize video:file = Video;

//@description Describes video note. Video must have equal width and height, cropped to circle and stored in mpeg4 format @duration Duration of the video in seconds as defined by sender @length Video width and height as defined by sender @thumb Video thumb as defined by sender, nullable @video File with the video
videoNote duration:int length:int thumb:photoSize video:file = VideoNote;

//@description Describes voice note. Voice must be encoded with Opus codec and must be stored inside Ogg container @duration Duration of the voice record in seconds as defined by sender
//@waveform Waveform representation of the voice in 5-bit format @mime_type MIME type of a file as defined by sender @voice File with the voice record
voice duration:int waveform:bytes mime_type:string voice:file = Voice;

//@description Describes user contact @phone_number User's phone number @first_name User first name, 1-255 characters @last_name User last name @user_id User identifier if known, 0 otherwise
contact phone_number:string first_name:string last_name:string user_id:int = Contact;

//@description Describes location on Earth @latitude Latitude of location in degrees as defined by sender @longitude Longitude of location in degrees as defined by sender
location latitude:double longitude:double = Location;

//@description Describes venue @location Venue location as defined by sender @title Venue name as defined by sender @address Venue address as defined by sender @provider Provider of venue database as defined by sender. Only "foursquare" need to be supported currently
//@id Identifier of the venue in provider database as defined by sender
venue location:location title:string address:string provider:string id:string = Venue;

//@description Describes a game @id Game id @short_name Game short name, to share a game use a URL https://t.me/{bot_username}?game={game_short_name} @title Game title @text Game text, usually containing game scoreboards
//@text_entities Entities contained in the text @param_description Game description @photo Game photo @animation Game animation, nullable
game id:long short_name:string title:string text:string text_entities:vector<textEntity> description:string photo:photo animation:animation = Game;


//@description Describes user profile photo @id Photo identifier, 0 for empty photo. Can be used to find photo in list of userProfilePhotos
//@small Small (160x160) user profile photo @big Big (640x640) user profile photo
profilePhoto id:long small:file big:file = ProfilePhoto;

//@description Describes chat photo @small Small (160x160) chat photo @big Big (640x640) chat photo
chatPhoto small:file big:file = ChatPhoto;


//@class LinkState @description Represents ordered relationship between two users

//@description Other user's phone number doesn't known
linkStateNone = LinkState;

//@description Other user's phone number is known but user not in contacts list
linkStateKnowsPhoneNumber = LinkState;

//@description Other user is in contacts list, particularly its phone number is known
linkStateContact = LinkState;


//@class UserType @description Allows to distinguish different kinds of users: general users, deleted users and bots

//@description General user
userTypeGeneral = UserType;

//@description Deleted user or deleted bot. There is no any information about it except user_id. None of active action can be performed with deleted user
userTypeDeleted = UserType;

//@description Bot (see https://core.telegram.org/bots) @can_join_group_chats If true, bot can be invited to group and supergroup chats
//@can_read_all_group_chat_messages If true, bot can read all group or supergroup chat messages, not only addressed to him. In private chats bot always can read all messages
//@is_inline True, if bot supports inline queries @inline_query_placeholder Placeholder for inline query @need_location If true, user location should be sent with every inline query to this bot
userTypeBot can_join_group_chats:Bool can_read_all_group_chat_messages:Bool is_inline:Bool inline_query_placeholder:string need_location:Bool = UserType;

//@description Currently there is no any information about the user except user_id. It can happens very-very rarely. None of active action can be performed with unknown user
userTypeUnknown = UserType;


//@description Represents command supported by bot @command Text of the bot command @param_description Description of the bot command
botCommand command:string description:string = BotCommand;

//@description Provides information about bot and command supported by him @param_description Big description shown in user info page @commands List of commands cupported by bot
botInfo description:string commands:vector<botCommand> = BotInfo;


//@description Represents user @id User identifier @first_name User first name @last_name User last name @username User username
//@phone_number User's phone number @status User's online status @profile_photo User profile photo, nullable
//@my_link Relationships from me to other user @foreign_link Relationships from other user to me @is_verified True, if user is verified @restriction_reason If non-empty, contains the reason, why access to this user must be restricted. Format of the string is "{type}: {description}". {type} contains type of the restriction and at least one of the suffixes "-all", "-ios", "-android", "-wp", which describes platforms on which access should be restricted. For example, "terms-ios-android". {description} contains human-readable description of the restriction, which can be showed to the user
//@have_access If false, the user is inaccessible and the only known information about it is inside this class. It can't be passed to any method except GetUser. Currently it can be false only for inaccessible authors of the channel posts @type Type of the user @language_code Bots only. IETF language tag of users language
user id:int first_name:string last_name:string username:string phone_number:string status:UserStatus profile_photo:profilePhoto my_link:LinkState foreign_link:LinkState is_verified:Bool restriction_reason:string have_access:Bool type:UserType language_code:string = User;

//@description Gives full information about a user (except full list of profile photos) @is_blocked Is user blacklisted by the current user @can_be_called True, if the user can be called @has_private_calls True, if the user can't be called only because of his privacy settings
//@about Short user bio or bot share text @common_chat_count Number of common chats between the user and current user, 0 for the current user @bot_info Information about bot if user is a bot, nullable
userFull is_blocked:Bool can_be_called:Bool has_private_calls:Bool about:string common_chat_count:int bot_info:botInfo = UserFull;

//@description Contains part of the list of user photos @total_count Total number of user profile photos @photos List of photos
userProfilePhotos total_count:int photos:vector<photo> = UserProfilePhotos;

//@description Represents list of users @total_count Approximate total count of found users @user_ids List of user identifiers
users total_count:int user_ids:vector<int> = Users;


//@class ChatMemberStatus @description Provides information about status of a member in the chat

//@description User is creator of the chat which has all administrator priviledges
chatMemberStatusCreator = ChatMemberStatus;

//@description User is a chat member with some additional priviledges. In groups, administrators can edit and delete other messages, add new members and ban unpriviledged members
//@can_be_edited True, if current user has rights to edit administrator privileges of that user
//@can_change_info True, if the administrator can change chat title, photo and other settings
//@can_post_messages True, if the administrator can create channel posts, broadcast channels only
//@can_edit_messages True, if the administrator can edit messages of other users, broadcast channels only
//@can_delete_messages True, if the administrator can delete messages of other users
//@can_invite_users True, if the administrator can invite new users to the chat
//@can_restrict_members True, if the administrator can restrict, ban or unban chat members
//@can_pin_messages True, if the administrator can pin messages, supergroup channels only
//@can_promote_members True, if the administrator can add new administrators with a subset of his own privileges or demote administrators directly or indirectly promoted by him
chatMemberStatusAdministrator can_be_edited:Bool can_change_info:Bool can_post_messages:Bool can_edit_messages:Bool can_delete_messages:Bool can_invite_users:Bool can_restrict_members:Bool can_pin_messages:Bool can_promote_members:Bool = ChatMemberStatus;

//@description User is a member of the chat, but have no any additional privileges or restrictions
chatMemberStatusMember = ChatMemberStatus;

//@description User has some additional restrictions in the chat. Unsupported in group chats and broadcast channels
//@is_member True, if user is chat member
//@restricted_until_date Date when the user will be unrestricted, 0 if never. Unix time. If user is restricted for more than 366 days or less than 30 seconds from the current time it considered to be restricted forever
//@can_send_messages True, if the user can send text messages, contacts, locations and venues
//@can_send_media_messages True, if the user can send audios, documents, photos, videos, video notes and voice notes, implies can_send_messages
//@can_send_other_messages True, if the user can send animations, games, stickers and use inline bots, implies can_send_media_messages
//@can_add_web_page_previews True, if user may add web page preview to his messages, implies can_send_messages
chatMemberStatusRestricted is_member:Bool restricted_until_date:int can_send_messages:Bool can_send_media_messages:Bool can_send_other_messages:Bool can_add_web_page_previews:Bool = ChatMemberStatus;

//@description User is not a chat member
chatMemberStatusLeft = ChatMemberStatus;

//@description User was banned (and obviously is not a chat member) and can't return to the chat or view messages
//@banned_until_date Date when the user will be unbanned, 0 if never. Unix time. If user is banned for more than 366 days or less than 30 seconds from the current time it considered to be banned forever
chatMemberStatusBanned banned_until_date:int = ChatMemberStatus;


//@description User with information about its chat joining/leaving @user_id User identifier of the chat member @inviter_user_id Identifier of a user invited/promoted/banned this member in the chat, 0 if unknown
//@join_date Date the user has joined a chat, unix time @status Status of the member in the chat @bot_info Information about bot if user is a bot, nullable. Can be null even for bot if bot is not a chat member
chatMember user_id:int inviter_user_id:int join_date:int status:ChatMemberStatus bot_info:botInfo = ChatMember;

//@description Contains list of chat members @total_count Approximate total count of found chat members @members List of members
chatMembers total_count:int members:vector<chatMember> = ChatMembers;


//@class ChannelMembersFilter @description Specifies kind of chat users to return in getChannelMembers

//@description Return recently active users in reverse chronological order
channelMembersFilterRecent = ChannelMembersFilter;

//@description Return creator and administrators
channelMembersFilterAdministrators = ChannelMembersFilter;

//@description Searches for channel members using specified query @query Query to search for
channelMembersFilterSearch query:string = ChannelMembersFilter;

//@description Return restricted channel members, administrators only @query Query to search for
channelMembersFilterRestricted query:string = ChannelMembersFilter;

//@description Return banned from the channel users, administrators only @query Query to search for
channelMembersFilterBanned query:string = ChannelMembersFilter;

//@description Return bot members of the channel
channelMembersFilterBots = ChannelMembersFilter;


//@description Represents a group of zero or more other users @id Group identifier
//@member_count Group member count
//@status Status of the current user in the group
//@everyone_is_administrator True, if all members granted administrator rights in the group
//@is_active True, if group is active
//@migrated_to_channel_id Identifier of channel (supergroup) to which this group was migrated or 0 if none
group id:int member_count:int status:ChatMemberStatus everyone_is_administrator:Bool is_active:Bool migrated_to_channel_id:int = Group;

//@description Gives full information about a group @creator_user_id User identifier of the group creator, 0 if unknown @members Group members @invite_link Invite link for this group, available only for group creator and only after it is generated at least once
groupFull creator_user_id:int members:vector<chatMember> invite_link:string = GroupFull;


//@description Represents a channel with zero or more subscribers. There two different kinds of channels: supergroups and broadcast channels
//@id Channel identifier
//@username Channel username, empty for private channels
//@date Date when current user has joined the channel or date when channel was created, if user is not a member. Unix time
//@status Status of the current user in the channel
//@anyone_can_invite True, if any member of the supergroup can invite other members. If the channel is not a supergroup, the field is meaningless
//@sign_messages True, if messages sent to the channel should content information about the sender. If the channel is a supergroup, the field is meaningless
//@is_supergroup True, if channel is a supergroup and is not a broadcast
//@is_verified True, if the channel is verified
//@restriction_reason If non-empty, contains the reason, why access to this channel must be restricted. Format of the string is "{type}: {description}". {type} contains type of the restriction and at least one of the suffixes "-all", "-ios", "-android", "-wp", which describes platforms on which access should be restricted. For example, "terms-ios-android". {description} contains human-readable description of the restriction, which can be showed to the user
channel id:int username:string date:int status:ChatMemberStatus anyone_can_invite:Bool sign_messages:Bool is_supergroup:Bool is_verified:Bool restriction_reason:string = Channel;

//@description Gives full information about a channel
//@param_description Channel description
//@member_count Channel member count, 0 if unknown
//@administrator_count Number of privileged users in the channel, 0 if unknown
//@restricted_count Number of restricted users in the channel, 0 if unknown
//@banned_count Number of users banned from the channel, 0 if unknown
//@can_get_members True, if members of the channel can be retrieved
//@can_set_username True, if channel can be made public
//@invite_link Invite link for this channel
//@pinned_message_id Identifier of the pinned message in the channel chat, or 0 if none
//@migrated_from_group_id Identifier of the group, this supergroup migrated from, or 0 if none
//@migrated_from_max_message_id Identifier of last message in the group chat migrated from, or 0 if none
channelFull description:string member_count:int administrator_count:int restricted_count:int banned_count:int can_get_members:Bool can_set_username:Bool invite_link:string pinned_message_id:int53 migrated_from_group_id:int migrated_from_max_message_id:int53 = ChannelFull;


//@description Represents a secret chat
//@id Secret chat identifier
//@user_id Identifier of the interlocutor
//@state State of the secret chat, 0 - yet not created, 1 - active, 2 - closed
//@is_outbound True if chat was created by the current logged in user, false otherwise
//@ttl Current message TTL setting for the chat in seconds
//@key_hash Hash of the current used key for comparison with the hash of the interlocutor's key. String of 36 bytes, which should be used to make a 12x12 square image with a color depth of 4. First 16 bytes should be used to make a central 8 * 8 square, left 20 bytes should be used to construct a border of width 2 around that square. Alternatively first 32 bytes of the hash can be converted to hex and printed as 32 2-digit hex numbers
//@layer Secret chat layer, determining features supported by other client. Video notes are supported if layer >= 66
secretChat id:int user_id:int state:int is_outbound:Bool ttl:int key_hash:bytes layer:int = SecretChat;


//@description Contains chat invite link @invite_link Chat invite link
chatInviteLink invite_link:string = ChatInviteLink;

//@description Contains information about chat invite link @chat_id Chat identifier of the invite link or 0 if user is not a member of this chat @title Title of the chat @photo Chat photo, nullable @member_count Total member count @member_user_ids User identifiers of some chat members that may be known to the current user
//@is_group True, if the chat is a group chat @is_channel True, if the chat is a channel chat @is_public_channel True, if the chat is a channel chat with set up username @is_supergroup_channel True, if the chat is a supergroup channel chat
chatInviteLinkInfo chat_id:int53 title:string photo:chatPhoto member_count:int member_user_ids:vector<int> is_group:Bool is_channel:Bool is_public_channel:Bool is_supergroup_channel:Bool = ChatInviteLinkInfo;


//@class MessageForwardInfo @description Contains information about initial sender of forwarded message

//@description Message is originally written by known user @sender_user_id Identifier of a user, who originally sent this message @date Date when message was originally sent
messageForwardedFromUser sender_user_id:int date:int = MessageForwardInfo;

//@description Message is orifinally a channel post @chat_id Identifier of a chat from which message is forwarded @author_signature Post author signature
//@date Date when message was originally sent @message_id Message identifier of the message from which the message is forwarded, 0 if unknown
messageForwardedPost chat_id:int53 author_signature:string date:int message_id:int53 = MessageForwardInfo;


//@class MessageSendState @description Contains information about sending state of the message

//@description Message is incoming
messageIsIncoming = MessageSendState;

//@description Message is outgoing but is yet not delivered to the server
messageIsBeingSent = MessageSendState;

//@description Message was synchronized with the server
messageIsSuccessfullySent = MessageSendState;

//@description Message is failed to send
messageIsFailedToSend = MessageSendState;


//@description Describes message
//@id Unique message identifier
//@sender_user_id Identifier of the user who sent the message, 0 if unknown. It is unknown for channel posts
//@chat_id Chat identifier
//@send_state Information about sending state of the message
//@can_be_edited True, if message can be edited
//@can_be_forwarded True, if message can be forwarded
//@can_be_deleted_only_for_self True, if message can be deleted only for self, other users will continue to see it
//@can_be_deleted_for_everyone True, if message can be deleted for everyone
//@is_post True, if message is channel post. All messages to broadcast channels are posts, all other messages are not posts
//@date Date when message was sent, unix time
//@edit_date Date when message was edited last time, unix time
//@forward_info Information about initial message sender, nullable
//@reply_to_message_id If non-zero, identifier of the message this message replies to, can be identifier of deleted message
//@ttl Message TTL in seconds, 0 if none. TDLib will send updateDeleteMessages or updateMessageContent when TTL expires
//@ttl_expires_in Time left for message TTL to expire in seconds
//@via_bot_user_id If non-zero, user identifier of the bot this message is sent via
//@author_signature For channel posts, optional author signature
//@views Number of times this message was viewed
//@content Content of the message
//@reply_markup Reply markup for the message, nullable
message id:int53 sender_user_id:int chat_id:int53 send_state:MessageSendState can_be_edited:Bool can_be_forwarded:Bool can_be_deleted_only_for_self:Bool can_be_deleted_for_everyone:Bool is_post:Bool date:int edit_date:int forward_info:MessageForwardInfo reply_to_message_id:int53 ttl:int ttl_expires_in:double via_bot_user_id:int author_signature:string views:int content:MessageContent reply_markup:ReplyMarkup = Message;

//@description Contains list of messages @total_count Approximate total count of found messages @messages List of messages
messages total_count:int messages:vector<message> = Messages;

//@description Contains list of found by search messages @messages List of messages @next_from_search_id Value to pass as from_search_id to get more results
foundMessages messages:vector<message> next_from_search_id:long = FoundMessages;


//@class NotificationSettingsScope @description Describes kinds of chat for which notification settings are applied

//@description Notification settings applied to particular chat @chat_id Chat identifier
notificationSettingsScopeChat chat_id:int53 = NotificationSettingsScope;

//@description Notification settings applied to all private chats
notificationSettingsScopePrivateChats = NotificationSettingsScope;

//@description Notification settings applied to all group and broadcast channel chats (supergroup channels have no common settings)
notificationSettingsScopeGroupChats = NotificationSettingsScope;

//@description Notification settings applied to all chats
notificationSettingsScopeAllChats = NotificationSettingsScope;


//@description Contains information about notification settings for chat or chats @mute_for Time left before notifications will be unmuted, seconds @sound Audio file name for notifications, iPhone apps only @show_preview Display message text/media in notification
notificationSettings mute_for:int sound:string show_preview:Bool = NotificationSettings;


//@description Contains information about draft of a message @reply_to_message_id Identifier of a message to reply to or 0 @input_message_text Content of a draft message, always should be of a type inputMessageText
draftMessage reply_to_message_id:int53 input_message_text:InputMessageContent = DraftMessage;


//@class ChatType @description Describes type of a chat

//@description Ordinary chat with a user @user_id User identifier
chatTypePrivate user_id:int = ChatType;

//@description Chat with zero or more other users @group_id Group identifier
chatTypeGroup group_id:int = ChatType;

//@description Chat with unlimited number of members @channel_id Channel identifier
chatTypeChannel channel_id:int = ChatType;

//@description Secret chat with a user @secret_chat_id Secret chat identifier
chatTypeSecret secret_chat_id:int = ChatType;


//@description Chat (private chat or group chat or channel chat)
//@id Chat unique identifier
//@type Information about type of the chat
//@title Chat title
//@photo Chat photo, nullable
//@top_message Last message in the chat, nullable
//@order Parameter by descending of which chats are sorted in the chat list. If order of two chats is equal, then they need to be sorted by id also in descending order. If order == 0, position of the chat in the list is undetermined
//@is_pinned True, if the chat is pinned
//@unread_count Count of unread messages in the chat
//@last_read_inbox_message_id Identifier of last read incoming message
//@last_read_outbox_message_id Identifier of last read outgoing message
//@notification_settings Notification settings for this chat
//@reply_markup_message_id Identifier of the message from which reply markup need to be used or 0 if there is no default custom reply markup in the chat
//@draft_message Draft of a message in the chat, nullable. parse_mode in input_message_text always will be null
//@client_data Client specified data, associated with the chat. For example, chat position or local chat notification settings may be stored here. Persistent if message db is used
chat id:int53 type:ChatType title:string photo:chatPhoto top_message:message order:long is_pinned:Bool unread_count:int last_read_inbox_message_id:int53 last_read_outbox_message_id:int53 notification_settings:notificationSettings reply_markup_message_id:int53 draft_message:draftMessage client_data:string = Chat;

//@description Represents list of chats @chat_ids List of chat identifiers
chats chat_ids:vector<int53> = Chats;


//@class KeyboardButtonType @description Describes type of the keyboard button

//@description Simple button with a text, which should be sent when the button is pressed
keyboardButtonTypeText = KeyboardButtonType;

//@description A button which sends user's phone number when pressed, available only in private chats
keyboardButtonTypeRequestPhoneNumber = KeyboardButtonType;

//@description A button which sends user location when pressed, available only in private chats
keyboardButtonTypeRequestLocation = KeyboardButtonType;


//@description Represents one button of the bot keyboard @text Text of the button @type Type of the button
keyboardButton text:string type:KeyboardButtonType = KeyboardButton;


//@class InlineKeyboardButtonType @description Describes type of the inline keyboard button

//@description A button which opens the specified URL @url URL to open
inlineKeyboardButtonTypeUrl url:string = InlineKeyboardButtonType;

//@description A button which sends to the bot special callback query @data Data to be sent to the bot through a callack query
inlineKeyboardButtonTypeCallback data:bytes = InlineKeyboardButtonType;

//@description A button with a game which sends to the bot special callback query, must be in the first column and row of the keyboard, can be attached only to a message with content of the type messageGame
inlineKeyboardButtonTypeCallbackGame = InlineKeyboardButtonType;

//@description A button which forces inline query to the bot to be substitued in the input field @query Inline query to be sent to the bot @in_current_chat True, if the inline query should be sent from the current chat
inlineKeyboardButtonTypeSwitchInline query:string in_current_chat:Bool = InlineKeyboardButtonType;

//@description A button for buying, must be in the first column and row of the keyboard, can be attached only to a message with content of the type messageInvoice
inlineKeyboardButtonTypeBuy = InlineKeyboardButtonType;


//@description Represents one button of the inline keyboard @text Text of the button @type Type of the button
inlineKeyboardButton text:string type:InlineKeyboardButtonType = InlineKeyboardButton;


//@class ReplyMarkup @description Contains description of custom keyboard and actions with it for fast reply to bots

//@description Instruct clients to remove keyboard after receiving this message. This kind of keyboard can't be received. Instead UpdateChatReplyMarkup with message_id == 0 will be send
//@personal Keyboard is removed only for mentioned users or replied to user
replyMarkupRemoveKeyboard personal:Bool = ReplyMarkup;

//@description Instruct clients to force reply to this message @personal Forced reply is used automatically only for mentioned users or replied to chat user, for incoming messages it is true if and only if forced reply needs to be automatically showed to the current user
replyMarkupForceReply personal:Bool = ReplyMarkup;

//@description Contains custom keyboard layout for fast reply to bot
//@rows List of rows of bot keyboard buttons
//@resize_keyboard Do clients need to resize keyboard vertically
//@one_time Do clients need to hide keyboard after use
//@personal Keyboard is showed automatically only for mentioned users or replied to user, for incoming messages it is true if and only if keyboard needs to be automatically showed to current user
replyMarkupShowKeyboard rows:vector<vector<keyboardButton>> resize_keyboard:Bool one_time:Bool personal:Bool = ReplyMarkup;

//@description Contains inline keyboard layout
//@rows List of rows of inline keyboard buttons
replyMarkupInlineKeyboard rows:vector<vector<inlineKeyboardButton>> = ReplyMarkup;


//@class RichText @description Describes a text inside web page instant view

//@description Plain text @text The text
richTextPlain text:string = RichText;

//@description Bold rich text @text The text
richTextBold text:RichText = RichText;

//@description Italicized rich text @text The text
richTextItalic text:RichText = RichText;

//@description Underlined rich text @text The text
richTextUnderline text:RichText = RichText;

//@description Striked through rich text @text The text
richTextStrikethrough text:RichText = RichText;

//@description Fixed width rich text @text The text
richTextFixed text:RichText = RichText;

//@description Rich text URL link @text The text @url The URL
richTextUrl text:RichText url:string = RichText;

//@description Rich text email link @text The text @email The email
richTextEmail text:RichText email:string = RichText;

//@description Concatenation of rich texts @texts The texts
richTextConcatenation texts:vector<RichText> = RichText;


//@class PageBlock @description Describes a block of web page instant view

//@description Title of a page @title The title
pageBlockTitle title:RichText = PageBlock;

//@description Subtitle of a page @subtitle The subtitle
pageBlockSubtitle subtitle:RichText = PageBlock;

//@description Author and publish date of a page @author The author @publish_date Date of article publish, unix time. 0 if unknown
pageBlockAuthorDate author:RichText publish_date:int = PageBlock;

//@description A header @header The header
pageBlockHeader header:RichText = PageBlock;

//@description A subheader @subheader The subheader
pageBlockSubheader subheader:RichText = PageBlock;

//@description A text paragraph @text Paragraph text
pageBlockParagraph text:RichText = PageBlock;

//@description Preformatted text paragraph @text Paragraph text @language Programming language for which the text should be formatted
pageBlockPreformatted text:RichText language:string = PageBlock;

//@description Footer of a page @footer The footer
pageBlockFooter footer:RichText = PageBlock;

//@description An empty block separating parts of a page
pageBlockDivider = PageBlock;

//@description Invisible anchor on a page which can be used in a URL to open a page from the specified anchor @name Name of the anchor
pageBlockAnchor name:string = PageBlock;

//@description List of texts @items Texts @is_ordered True, if items should be marked with numbers
pageBlockList items:vector<RichText> is_ordered:Bool = PageBlock;

//@description Block quote @text Quote text @caption Quote caption
pageBlockBlockQuote text:RichText caption:RichText = PageBlock;

//@description Pull quote @text Quote text @caption Quote caption
pageBlockPullQuote text:RichText caption:RichText = PageBlock;

//@description An animation @animation The animation, nullable @caption Animation caption @need_autoplay True, if the animation should be autoplayed
pageBlockAnimation animation:animation caption:RichText need_autoplay:Bool = PageBlock;

//@description An audio @audio The audio, nullable @caption Audio caption
pageBlockAudio audio:audio caption:RichText = PageBlock;

//@description A photo @photo The photo, nullable @caption Photo caption
pageBlockPhoto photo:photo caption:RichText = PageBlock;

//@description A video @video The video, nullable @caption Video caption @need_autoplay True, if the video should be autoplayed @is_looped True, if the video is looped
pageBlockVideo video:video caption:RichText need_autoplay:Bool is_looped:Bool = PageBlock;

//@description Page cover @cover The cover
pageBlockCover cover:PageBlock = PageBlock;

//@description Embedded web page @url Web page URL, if available @html HTML-markup of the embedded page @poster_photo Poster photo if available, nullable @width Block width @height Block height @caption Block caption @is_full_width True, if the block should be full width @allow_scrolling True, if scrolling should be allowed
pageBlockEmbedded url:string html:string poster_photo:photo width:int height:int caption:RichText is_full_width:Bool allow_scrolling:Bool = PageBlock;

//@description Embedded post @url Web page URL @author Post author @author_photo Post author photo @date Post date, unix time. 0 if unknown @page_blocks Post content @caption Post caption
pageBlockEmbeddedPost url:string author:string author_photo:photo date:int page_blocks:vector<PageBlock> caption:RichText = PageBlock;

//@description A collage @page_blocks Collage item contents @caption Block caption
pageBlockCollage page_blocks:vector<PageBlock> caption:RichText = PageBlock;

//@description A slideshow @page_blocks Slideshow item contents @caption Block caption
pageBlockSlideshow page_blocks:vector<PageBlock> caption:RichText = PageBlock;

//@description A link to a chat @title Chat title @photo Chat photo, nullable @username Chat username by which all other information about the chat should be resolved
pageBlockChatLink title:string photo:chatPhoto username:string = PageBlock;


//@description Describes instant view of a web page @page_blocks Content of the web page @is_full True, if instant view contains full page. Network request may be needed to get full web page instant view
webPageInstantView page_blocks:vector<PageBlock> is_full:Bool = WebPageInstantView;


//@description Describes web page preview @url Original URL of link @display_url URL to display
//@type Type of web page: article, photo, audio, video, document, profile, app or something other
//@site_name Short name of the site (i.e. Google Docs or App Store) @title Title of the content @param_description Description of the content
//@photo Image representing the content, nullable
//@embed_url Url to show embedded preview
//@embed_type MIME type of embedded preview, i.e. text/html or video/mp4
//@embed_width Width of embedded preview
//@embed_height Height of embedded preview
//@duration Duration of the content in seconds
//@author Author of the content
//@animation Preview as an Animation if available, nullable
//@audio Preview as an Audio if available, nullable
//@document Preview as a Document if available (currently only for small pdf files and zip archives), nullable
//@sticker Preview as a Sticker for small .webp files if available, nullable
//@video Preview as a Video if available, nullable
//@video_note Preview as a VideoNote if available, nullable
//@voice Preview as a Voice if available, nullable
//@has_instant_view True if web page has instant view
webPage url:string display_url:string type:string site_name:string title:string description:string photo:photo embed_url:string embed_type:string embed_width:int embed_height:int duration:int author:string animation:animation audio:audio document:document sticker:sticker video:video video_note:videoNote voice:voice has_instant_view:Bool = WebPage;


//@description Goods price portion @label Portion label @amount Currency amount in minimal quantity of the currency
labeledPrice label:string amount:int53 = LabeledPrice;

//@description Goods invoice @currency ISO 4217 currency code @prices List of objects used to calculate total price @is_test True, if payment is test @need_name True, if user's name is needed for payment @need_phone_number True, if user's phone number is needed for payment @need_email True, if user's email is needed for payment
//@need_shipping_address True, if user's shipping address is needed for payment @is_flexible True, if total price depends on shipping method
invoice currency:string prices:vector<labeledPrice> is_test:Bool need_name:Bool need_phone_number:Bool need_email:Bool need_shipping_address:Bool is_flexible:Bool = Invoice;

//@description Describes shipping address @country_code Two letter ISO 3166-1 alpha-2 country code @state State if applicable @city City @street_line1 First line for the address @street_line2 Second line for the address @post_code Address post code
shippingAddress country_code:string state:string city:string street_line1:string street_line2:string post_code:string = ShippingAddress;

//@description Order information @name User name @phone_number User's phone number @email User email @shipping_address User shipping address, nullable
orderInfo name:string phone_number:string email:string shipping_address:shippingAddress = OrderInfo;

//@description One shipping option @id Shipping option identifier @title Option title @prices List of objects used to calculate total shipping price
shippingOption id:string title:string prices:vector<labeledPrice> = ShippingOption;

//@description Information about saved card credentials @id Unique identifier of the saved credentials @title Title of the saved credentials
savedCredentials id:string title:string = SavedCredentials;

//@class InputCredentials @description Contains information about payment method chosen by user

//@description User chooses previosly saved payment credentials. To use previously saved credentials user should have valid temporary password @saved_credentials_id Identifier of saved credentials
inputCredentialsSaved saved_credentials_id:string = InputCredentials;

//@description User enters new credentials on payment provider web site @data JSON-encoded data with credentials identifier from the payment provider @allow_save True, if credentials identifier can be saved server-side
inputCredentialsNew data:string allow_save:Bool = InputCredentials;

//@description Stripe payments provider @publishable_key Stripe API publishable key @need_country True, if user country should be entered @need_zip True, if user zip code should be entered @need_cardholder_name True, if cardholder name should be entered
paymentsProviderStripe publishable_key:string need_country:Bool need_zip:Bool need_cardholder_name:Bool = PaymentsProviderStripe;

//@description Information about invoice payment form @invoice Full information about the invoice @url Payment form URL @payments_provider Information about payment provider if available, to support it natively without opening the URL, nullable
//@saved_order_info Saved server-side order information, nullable @saved_credentials Information about saved card credentials, nullable @can_save_credentials True, if the user can choose to save credentials @need_password True, if the user will be able to save credentials if he set up a password
paymentForm invoice:invoice url:string payments_provider:paymentsProviderStripe saved_order_info:orderInfo saved_credentials:savedCredentials can_save_credentials:Bool need_password:Bool = PaymentForm;

//@description Contains temporary identifier of validated order information stored for an hour and available shipping options @order_info_id Temporary identifier of order information @shipping_options Available shipping options
validatedOrderInfo order_info_id:string shipping_options:vector<shippingOption> = ValidatedOrderInfo;

//@description Contains result of a payment query @success True, if payment request was successful. If false, verification_url will be not empty @verification_url Url for additional payments credentials verification
paymentResult success:Bool verification_url:string = PaymentResult;

//@description Contains information about successful payment @date Payment date, unix time @payments_provider_user_id User identifier of payments provider bot @invoice Information about the invoice
//@order_info Order information, nullable @shipping_option Chosen shipping option, nullable @credentials_title Title of the saved credentials
paymentReceipt date:int payments_provider_user_id:int invoice:invoice order_info:orderInfo shipping_option:shippingOption credentials_title:string = PaymentReceipt;


//@class MessageContent @description Content of a message

//@description Text message @text Text of the message @entities Entities contained in the text @web_page Preview of a web page mentioned in the text, nullable
messageText text:string entities:vector<textEntity> web_page:webPage = MessageContent;

//@description Animation message @animation Message content @caption Animation caption
messageAnimation animation:animation caption:string = MessageContent;

//@description Audio message @audio Message content @caption Audio caption
messageAudio audio:audio caption:string = MessageContent;

//@description Document message @document Message content @caption Document caption
messageDocument document:document caption:string = MessageContent;

//@description Photo message @photo Message content @caption Photo caption
messagePhoto photo:photo caption:string = MessageContent;

//@description Photo message expired by TTL
messageExpiredPhoto = MessageContent;

//@description Sticker message @sticker Message content
messageSticker sticker:sticker = MessageContent;

//@description Video message @video Message content @caption Video caption
messageVideo video:video caption:string = MessageContent;

//@description Video message expired by TTL
messageExpiredVideo = MessageContent;

//@description Video note message @video_note Message content @is_viewed True, if the video note message was viewed
messageVideoNote video_note:videoNote is_viewed:Bool = MessageContent;

//@description Voice message @voice Message content @caption Voice caption @is_listened True, if the voice message was listened to
messageVoice voice:voice caption:string is_listened:Bool = MessageContent;

//@description Message with location @location Message content
messageLocation location:location = MessageContent;

//@description Message with information about venue @venue Message content
messageVenue venue:venue = MessageContent;

//@description User contact message @contact Message content
messageContact contact:contact = MessageContent;

//@description Message with a game @game The game
messageGame game:game = MessageContent;

//@description Message with an invoice from a bot @title Goods title @param_description Goods description @photo Goods photo, nullable @currency Currency for goods price @total_amount Goods total price in minimal quantity of the currency
//@start_parameter Unique invoice bot start_parameter. To share an invoice use a URL https://t.me/{bot_username}?start={start_parameter} @is_test True, if invoice is test
//@need_shipping_address True, if shipping address should be specified @receipt_message_id Identifier of message with receipt after the goods are paid
messageInvoice title:string description:string photo:photo currency:string total_amount:int53 start_parameter:string is_test:Bool need_shipping_address:Bool receipt_message_id:int53 = MessageContent;

//@description Message with an information about ended call @discard_reason Call discard reason @duration Call duration in seconds
messageCall discard_reason:CallDiscardReason duration:int = MessageContent;

//@description New group chat created @title Title of created group chat @member_user_ids User identifiers of members of created group chat
messageGroupChatCreate title:string member_user_ids:vector<int> = MessageContent;

//@description New channel chat created @title Title of created channel chat
messageChannelChatCreate title:string = MessageContent;

//@description Chat title changed @title New chat title
messageChatChangeTitle title:string = MessageContent;

//@description Chat photo changed @photo New chat photo
messageChatChangePhoto photo:photo = MessageContent;

//@description Chat photo deleted
messageChatDeletePhoto = MessageContent;

//@description Chat members added @member_user_ids User identifiers of new chat members
messageChatAddMembers member_user_ids:vector<int> = MessageContent;

//@description Chat member joined by invite link
messageChatJoinByLink = MessageContent;

//@description Chat member deleted @user_id User identifier of deleted chat memeber
messageChatDeleteMember user_id:int = MessageContent;

//@description Group chat is migrated to supergroup channel and deactivated @channel_id Identifier of the channel it is migrated to
messageChatMigrateTo channel_id:int = MessageContent;

//@description Supergroup channel is created from group chat @title Title of created channel chat @group_id Identifier of the group it is migrated from
messageChatMigrateFrom title:string group_id:int = MessageContent;

//@description Some message was pinned @message_id Identifier of the pinned message, can be identifier of the deleted message
messagePinMessage message_id:int53 = MessageContent;

//@description Screenshot of messages in the chat was taken
messageScreenshotTaken = MessageContent;

//@description Messages ttl setting in secret chat has changed @ttl New ttl
messageChatSetTtl ttl:int = MessageContent;

//@description New high score was achieved in a game @game_message_id Identifier of the message with the game, can be identifier of the deleted message @game_id Identifier of the game, may be different from the games presented in the message with the game @score New score
messageGameScore game_message_id:int53 game_id:long score:int = MessageContent;

//@description Payment completed @currency Currency for goods price @total_amount Goods total price in minimal quantity of the currency
messagePaymentSuccessful currency:string total_amount:int53 = MessageContent;

//@description Bots only. Payment completed @currency Currency for goods price @total_amount Goods total price in minimal quantity of the currency @invoice_payload Invoice payload @shipping_option_id Identifier of a choosed by user shipping option, may be empty if not applicable @order_info Information about the order, nullable
//@telegram_payment_charge_id Telegram payment identifier @provider_payment_charge_id Provider payment identifier
messagePaymentSuccessfulBot currency:string total_amount:int53 invoice_payload:bytes shipping_option_id:string order_info:orderInfo telegram_payment_charge_id:string provider_payment_charge_id:string = MessageContent;

//@description Contact has registered
messageContactRegistered = MessageContent;

//@description Unsupported message content
messageUnsupported = MessageContent;


//@class TextEntityType @description Represent part of the text which needs to be formatted in some unusual way

//@description Mention of the user by his username
textEntityTypeMention = TextEntityType;

//@description Hashtag beginning with #
textEntityTypeHashtag = TextEntityType;

//@description Bot command beginning with /. It shouldn't be highlighted if there is no bots in the chat
textEntityTypeBotCommand = TextEntityType;

//@description Url beginning with http
textEntityTypeUrl = TextEntityType;

//@description Email
textEntityTypeEmail = TextEntityType;

//@description Bold text
textEntityTypeBold = TextEntityType;

//@description Italic text
textEntityTypeItalic = TextEntityType;

//@description Text needs to be formatted as inside of code HTML tag
textEntityTypeCode = TextEntityType;

//@description Text needs to be formatted as inside of pre HTML tag
textEntityTypePre = TextEntityType;

//@description Text needs to be formatted as inside of pre and code HTML tags @language Language of code as defined by sender
textEntityTypePreCode language:string = TextEntityType;

//@description Text description showed instead of the url @url Url to be opened after link will be clicked
textEntityTypeTextUrl url:string = TextEntityType;

//@description Mention of the user by some text @user_id Identifier of the mentioned user
textEntityTypeMentionName user_id:int = TextEntityType;


//@class TextParseMode @description Describes a way text should be parsed for MessageEntities, by default text is treated as is

//@description Text should be parsed in markdown-style way
textParseModeMarkdown = TextParseMode;

//@description Text should be parsed in the HTML-style way
textParseModeHTML = TextParseMode;


//@class InputThumb @description Thumb to send along with a file, should be in jpeg format or webp format for stickers

//@description Local file with the thumb @path Path to the file with the thumb, file should be less than 200KB @width Thumb width, usually shouldn't excceed 90. Use 0 if unknown @height Thumb height, usually shouldn't excceed 90. Use 0 if unknown
inputThumbLocal path:string width:int height:int = InputThumb;

//@description Generated thumb, should be less than 200KB @original_path Path to the original file @conversion String specifying conversion applied to the original file, should be persistent across application restart
//@width Thumb width, usually shouldn't excceed 90. Use 0 if unknown @height Thumb height, usually shouldn't excceed 90. Use 0 if unknown
inputThumbGenerated original_path:string conversion:string width:int height:int = InputThumb;


//@class InputMessageContent @description Content of a message to send

//@description Text message @text Text to send @disable_web_page_preview Pass true to disable rich preview for link in the message text @clear_draft Pass true if chat draft message should be deleted
//@entities Bold, Italic, Code, Pre, PreCode and TextUrl entities contained in the text. Non-bot users can't use TextUrl entities. Can't be used with non-null parse_mode @parse_mode Text parse mode, nullable. Can't be used along with enitities
inputMessageText text:string disable_web_page_preview:Bool clear_draft:Bool entities:vector<textEntity> parse_mode:TextParseMode = InputMessageContent;

//@description Animation message @animation Animation file to send @thumb Animation thumb, if available @duration Duration of the animation in seconds @width Width of the animation, may be replaced by the server @height Height of the animation, may be replaced by the server @caption Animation caption, 0-200 characters
inputMessageAnimation animation:InputFile thumb:InputThumb duration:int width:int height:int caption:string = InputMessageContent;

//@description Audio message @audio Audio file to send @album_cover_thumb Thumb of the album's cover, if available @duration Duration of the audio in seconds, may be replaced by the server @title Title of the audio, 0-64 characters, may be replaced by the server
//@performer Performer of the audio, 0-64 characters, may be replaced by the server @caption Audio caption, 0-200 characters
inputMessageAudio audio:InputFile album_cover_thumb:InputThumb duration:int title:string performer:string caption:string = InputMessageContent;

//@description Document message @document Document to send @thumb Document thumb, if available @caption Document caption, 0-200 characters
inputMessageDocument document:InputFile thumb:InputThumb caption:string = InputMessageContent;

//@description Photo message @photo Photo to send @thumb Photo thumb to send, is sent to the other party in secret chats only @added_sticker_file_ids File identifiers of stickers added onto the photo @width Photo width @height Photo height @caption Photo caption, 0-200 characters
//@ttl Photo TTL in seconds, 0-60. Non-zero TTL can be only specified in private chats
inputMessagePhoto photo:InputFile thumb:InputThumb added_sticker_file_ids:vector<int> width:int height:int caption:string ttl:int = InputMessageContent;

//@description Sticker message @sticker Sticker to send @thumb Sticker thumb, if available @width Sticker width @height Sticker height
inputMessageSticker sticker:InputFile thumb:InputThumb width:int height:int = InputMessageContent;

//@description Video message @video Video to send @thumb Video thumb, if available @added_sticker_file_ids File identifiers of stickers added onto the video @duration Duration of the video in seconds @width Video width @height Video height @caption Video caption, 0-200 characters
//@ttl Video TTL in seconds, 0-60. Non-zero TTL can be only specified in private chats
inputMessageVideo video:InputFile thumb:InputThumb added_sticker_file_ids:vector<int> duration:int width:int height:int caption:string ttl:int = InputMessageContent;

//@description Video note message @video_note Video note to send @thumb Video thumb, if available @duration Duration of the video in seconds @length Video width and height, should be positive and not greater than 640
inputMessageVideoNote video_note:InputFile thumb:InputThumb duration:int length:int = InputMessageContent;

//@description Voice message @voice Voice file to send @duration Duration of the voice in seconds @waveform Waveform representation of the voice in 5-bit format @caption Voice caption, 0-200 characters
inputMessageVoice voice:InputFile duration:int waveform:bytes caption:string = InputMessageContent;

//@description Message with location @location Location to send
inputMessageLocation location:location = InputMessageContent;

//@description Message with information about venue @venue Venue to send
inputMessageVenue venue:venue = InputMessageContent;

//@description User contact message @contact Contact to send
inputMessageContact contact:contact = InputMessageContent;

//@description Message with a game, can't be used in broadcast channels and secret chats @bot_user_id User identifier of a bot owned the game @game_short_name Game short name
inputMessageGame bot_user_id:int game_short_name:string = InputMessageContent;

//@description Message with an invoice, can be used only by bots and in private chats only @invoice The invoice @title Product title, 1-32 characters @param_description Product description, 0-255 characters @photo_url Goods photo URL, optional @photo_size Goods photo size @photo_width Goods photo width @photo_height Goods photo height
//@payload Invoice payload @provider_token Payments provider token @start_parameter Unique invoice bot start_parameter for generation of this invoice
inputMessageInvoice invoice:invoice title:string description:string photo_url:string photo_size:int photo_width:int photo_height:int payload:bytes provider_token:string start_parameter:string = InputMessageContent;

//@description Forwarded message @from_chat_id Chat identifier of the message to forward @message_id Identifier of the message to forward @in_game_share Pass true to share a game message within a launched game, for Game messages only
inputMessageForwarded from_chat_id:int53 message_id:int53 in_game_share:Bool = InputMessageContent;


//@class SearchMessagesFilter @description Represents filter for content of searched messages

//@description Return all found messages
searchMessagesFilterEmpty = SearchMessagesFilter;

//@description Return only animation messages
searchMessagesFilterAnimation = SearchMessagesFilter;

//@description Return only audio messages
searchMessagesFilterAudio = SearchMessagesFilter;

//@description Return only document messages
searchMessagesFilterDocument = SearchMessagesFilter;

//@description Return only photo messages
searchMessagesFilterPhoto = SearchMessagesFilter;

//@description Return only video messages
searchMessagesFilterVideo = SearchMessagesFilter;

//@description Return only voice messages
searchMessagesFilterVoice = SearchMessagesFilter;

//@description Return only photo and video messages
searchMessagesFilterPhotoAndVideo = SearchMessagesFilter;

//@description Return only messages containing url
searchMessagesFilterUrl = SearchMessagesFilter;

//@description Return only messages containing chat photos
searchMessagesFilterChatPhoto = SearchMessagesFilter;

//@description Return only call messages
searchMessagesFilterCall = SearchMessagesFilter;

//@description Return only incoming call messages with missed/declined discard reason
searchMessagesFilterMissedCall = SearchMessagesFilter;

//@description Return only video note messages
searchMessagesFilterVideoNote = SearchMessagesFilter;

//@description Return only voice and video note messages
searchMessagesFilterVoiceAndVideoNote = SearchMessagesFilter;


//@class ChatAction @description Describes different types of activity in a chat

//@description User typing a message
chatActionTyping = ChatAction;
//@description User records a video
chatActionRecordingVideo = ChatAction;
//@description User uploads a video @progress Upload progress in percents
chatActionUploadingVideo progress:int = ChatAction;
//@description User records voice message
chatActionRecordingVoice = ChatAction;
//@description User uploads voice message @progress Upload progress in percents
chatActionUploadingVoice progress:int = ChatAction;
//@description User uploads a photo @progress Upload progress in percents
chatActionUploadingPhoto progress:int = ChatAction;
//@description User uploads a document @progress Upload progress in percents
chatActionUploadingDocument progress:int = ChatAction;
//@description User chooses location or venue to send
chatActionChoosingLocation = ChatAction;
//@description User chooses contact to send
chatActionChoosingContact = ChatAction;
//@description User starts to play a game
chatActionStartPlayingGame = ChatAction;
//@description User records video note
chatActionRecordingVideoNote = ChatAction;
//@description User uploads a video note @progress Upload progress in percents
chatActionUploadingVideoNote progress:int = ChatAction;
//@description User cancels previous action
chatActionCancel = ChatAction;


//@class UserStatus @description Describes last time user was online

//@description User status was newer changed
userStatusEmpty = UserStatus;

//@description User is online @expires Unix time when user's online status will expire
userStatusOnline expires:int = UserStatus;

//@description User is offline @was_online Unix time user was online last time
userStatusOffline was_online:int = UserStatus;

//@description User was online recently
userStatusRecently = UserStatus;

//@description User is offline, but was online last week
userStatusLastWeek = UserStatus;

//@description User is offline, but was online last month
userStatusLastMonth = UserStatus;


//@description Represents list of stickers @stickers Stickers
stickers stickers:vector<sticker> = Stickers;

//@description Represents list of all emojis corresponding to a sticker in a sticker set. The list is only for informational purposes because sticker is always sent with a fixed emoji from the corresponding Sticker object @emojis List of emojis
stickerEmojis emojis:vector<string> = StickerEmojis;

//@description Represents sticker set @id Sticker set identifier @title Title of the sticker set @name Name of the sticker set @is_installed True if sticker set is installed by logged in user
//@is_archived True if sticker set is archived. A sticker set can't be installed and archived simultaneously @is_official True if sticker set is official @is_masks True if stickers in the set are masks
//@is_viewed True for viewed trending sticker set @stickers List of stickers in this set @emojis Lists of emojis corresponding to the stickers in the same order
stickerSet id:long title:string name:string is_installed:Bool is_archived:Bool is_official:Bool is_masks:Bool is_viewed:Bool stickers:vector<sticker> emojis:vector<stickerEmojis> = StickerSet;

//@description Represents short information about sticker set @id Sticker set identifier @title Title of the sticker set @name Name of the sticker set @is_installed True if sticker set is installed by logged in user
//@is_archived True if sticker set is archived. A sticker set can't be installed and archived simultaneously @is_official True if sticker set is official @is_masks True if stickers in the set are masks
//@is_viewed True for viewed trending sticker set @size Total number of stickers in the set @covers Up to 5 first stickers from the set depending on the context. If client needs more stickers it should request full sticker set
stickerSetInfo id:long title:string name:string is_installed:Bool is_archived:Bool is_official:Bool is_masks:Bool is_viewed:Bool size:int covers:vector<sticker> = StickerSetInfo;

//@description Represents list of sticker sets @total_count Approximate total count of found sticker sets @sets List of sticker sets
stickerSets total_count:int sets:vector<stickerSetInfo> = StickerSets;


//@class CallDiscardReason @description Describes reason a call was discarded

//@description Call wasn't discarded or the reason is unknown
callDiscardReasonEmpty = CallDiscardReason;

//@description Call has ended before conversation begins. It was cancelled by the caller or was missed by the callee
callDiscardReasonMissed = CallDiscardReason;

//@description Call has ended before conversation begins. It was declined by the callee
callDiscardReasonDeclined = CallDiscardReason;

//@description Call has ended during conversation because users was disconnected
callDiscardReasonDisconnected = CallDiscardReason;

//@description Call was hung up by the caller or by the callee
callDiscardReasonHungUp = CallDiscardReason;


//@description Specifies supported call protocols @udp_p2p True, if UDP peer to peer connections are supported @udp_reflector True, if connection through UDP reflectors are supported @min_layer Minimum supported layer, use 65 @max_layer Maximum supported layer, use 65
callProtocol udp_p2p:Bool udp_reflector:Bool min_layer:int max_layer:int = CallProtocol;

//@description Describes address of UDP reflectors @id Reflector identifier @ip IPv4 reflector address @ipv6 IPv6 reflector address @port Reflector port @peer_tag Connection peer tag
callConnection id:long ip:string ipv6:string port:int peer_tag:bytes = CallConnection;


//@description Contains call identifier @id Call identifier
callId id:int = CallId;


//@class CallState @description Describes current call state

//@description Call is pending for acception by a user @is_created True, if the call is already created by the server @is_received True, if the call is already received by the other party
callStatePending is_created:Bool is_received:Bool = CallState;

//@description Call is answered and encryption keys are exchanged
callStateExchangingKeys = CallState;

//@description Call is ready to use @protocol Call protocols supported by the peer @connections Available UDP reflectors @config JSON-encoded call config @encryption_key Call encryption key @emojis Encryption key emojis fingerprint
callStateReady protocol:callProtocol connections:vector<callConnection> config:string encryption_key:bytes emojis:vector<string> = CallState;

//@description Call is hanging up after discardCall is called
callStateHangingUp = CallState;

//@description Call has ended successfully @reason A reason, why call has ended @need_rating If true, call rating should be sent to the server @need_debug If true, call debug should be sent to the server
callStateDiscarded reason:CallDiscardReason need_rating:Bool need_debug:Bool = CallState;

//@description Call has ended with an error @error The error. Error with the code 4005000 returned if an outgoing call is missed because of expired timeout
callStateError error:error = CallState;


//@description Describes a call @id Call identifier, not persistent @user_id Peer user identifier @is_outgoing True, if the call is outgoing @state Call state
call id:int user_id:int is_outgoing:Bool state:CallState = Call;


//@description Represents list of animations @animations Animations
animations animations:vector<animation> = Animations;


//@description Represent result for ImportContacts request @user_ids User identifiers of imported contacts in the same order as they was specified in the request. 0 if contact is not yet registered
//@importer_count Number of users which imported corresponding contact. 0 for already registered users or if unavailable
importedContacts user_ids:vector<int> importer_count:vector<int> = ImportedContacts;


//@class InputInlineQueryResult @description Represents one result of the inline query received from the bot

//@description Represents link to an animated gif @id Unique identifier of this result @title Title of the result @thumb_url Url of the static result thumb (jpeg or gif), if exists
//@gif_url Url of the gif-file (file size must not exceed 1MB) @gif_duration Duration of the gif in seconds @gif_width Width of the gif @gif_height Height of the gif
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageAnimation or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultAnimatedGif id:string title:string thumb_url:string gif_url:string gif_duration:int gif_width:int gif_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to an animated (i.e. without sound) H.264/MPEG-4 AVC video @id Unique identifier of this result @title Title of the result @thumb_url Url of the static result thumb (jpeg or gif), if exists
//@mpeg4_url Url of the mp4-file (file size must not exceed 1MB) @mpeg4_duration Duration of the video in seconds @mpeg4_width Width of the video @mpeg4_height Height of the video
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageAnimation or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultAnimatedMpeg4 id:string title:string thumb_url:string mpeg4_url:string mpeg4_duration:int mpeg4_width:int mpeg4_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to an article or web page @id Unique identifier of this result @url Url of the result, if exists @hide_url True, if url must be not shown @title Title of the result
//@param_description Short description of the result @thumb_url Url of the result thumb, if exists @thumb_width Thumb width, if known @thumb_height Thumb height, if known
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultArticle id:string url:string hide_url:Bool title:string description:string thumb_url:string thumb_width:int thumb_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to a mp3 audio file @id Unique identifier of this result @title Title of the audio @performer Performer of the audio
//@audio_url Url of the audio file @audio_duration Audio duration in seconds
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageAudio or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultAudio id:string title:string performer:string audio_url:string audio_duration:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents user contact @id Unique identifier of this result @contact User contact @thumb_url Url of the result thumb, if exists @thumb_width Thumb width, if known @thumb_height Thumb height, if known
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultContact id:string contact:contact thumb_url:string thumb_width:int thumb_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to a file @id Unique identifier of this result @title Title of the result @param_description Short description of the result, if known @document_url Url of the file @mime_type MIME type of the file content, only “application/pdf” and “application/zip” are allowed now
//@thumb_url Url of the file thumb, if exists @thumb_width Width of the thumb @thumb_height Height of the thumb
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageDocument or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultDocument id:string title:string description:string document_url:string mime_type:string thumb_url:string thumb_width:int thumb_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents a game @id Unique identifier of this result @game_short_name Game short name @reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
inputInlineQueryResultGame id:string game_short_name:string reply_markup:ReplyMarkup = InputInlineQueryResult;

//@description Represents a point on the map @id Unique identifier of this result @location Result @title Title of the result @thumb_url Url of the result thumb, if exists @thumb_width Thumb width, if known @thumb_height Thumb height, if known
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultLocation id:string location:location title:string thumb_url:string thumb_width:int thumb_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to a jpeg photo @id Unique identifier of this result @title Title of the result, if known @param_description Short description of the result, if known @thumb_url Url of the photo thumb, if exists
//@photo_url Url of the jpeg photo (photo must not exceed 5MB) @photo_width Width of the photo @photo_height Height of the photo
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessagePhoto or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultPhoto id:string title:string description:string thumb_url:string photo_url:string photo_width:int photo_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to a webp sticker @id Unique identifier of this result @thumb_url Url of the sticker thumb, if exists
//@sticker_url Url of the webp sticker (file with a sticker must not exceed 5MB) @sticker_width Width of the sticker @sticker_height Height of the sticker
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageSticker or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultSticker id:string thumb_url:string sticker_url:string sticker_width:int sticker_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents information about a venue @id Unique identifier of this result @venue Result @thumb_url Url of the result thumb, if exists @thumb_width Thumb width, if known @thumb_height Thumb height, if known
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultVenue id:string venue:venue thumb_url:string thumb_width:int thumb_height:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to a page containing an embedded video player or a video file @id Unique identifier of this result @title Title of the result @param_description Short description of the result, if known
//@thumb_url Url of the video thumb (jpeg), if exists @video_url Url of the embedded video player or video file @mime_type MIME type of the content of video url, only "text/html" or "video/mp4" are allowed now
//@video_width Video width @video_height Video height @video_duration Video duration in seconds
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageVideo or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultVideo id:string title:string description:string thumb_url:string video_url:string mime_type:string video_width:int video_height:int video_duration:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;

//@description Represents link to a opus encoded audio file in ogg container @id Unique identifier of this result @title Title of the voice file
//@voice_url Url of the voice file @voice_duration Voice duration in seconds
//@reply_markup Message reply markup, should be of type replyMarkupInlineKeyboard or null
//@input_message_content Content of the message to be sent, should be of type inputMessageText or inputMessageVoice or InputMessageLocation or InputMessageVenue or InputMessageContact
inputInlineQueryResultVoice id:string title:string voice_url:string voice_duration:int reply_markup:ReplyMarkup input_message_content:InputMessageContent = InputInlineQueryResult;


//@class InlineQueryResult @description Represents one result of the inline query

//@description Represents link to an article or web page @id Unique identifier of this result @url Url of the result, if exists @hide_url True, if url must be not shown @title Title of the result
//@param_description Short description of the result @thumb Result thumb, nullable
inlineQueryResultArticle id:string url:string hide_url:Bool title:string description:string thumb:photoSize = InlineQueryResult;

//@description Represents user contact @id Unique identifier of this result @contact User contact @thumb Result thumb, nullable
inlineQueryResultContact id:string contact:contact thumb:photoSize = InlineQueryResult;

//@description Represents a point on the map @id Unique identifier of this result @location The result @title Title of the result @thumb Result thumb, nullable
inlineQueryResultLocation id:string location:location title:string thumb:photoSize = InlineQueryResult;

//@description Represents information about a venue @id Unique identifier of this result @venue The result @thumb Result thumb, nullable
inlineQueryResultVenue id:string venue:venue thumb:photoSize = InlineQueryResult;

//@description Represents information about a game @id Unique identifier of this result @game The result
inlineQueryResultGame id:string game:game = InlineQueryResult;

//@description Represents an animation cached on the telegram server @id Unique identifier of this result @animation The animation @title Animation title
inlineQueryResultAnimation id:string animation:animation title:string = InlineQueryResult;

//@description Represents an audio cached on the telegram server @id Unique identifier of this result @audio The audio
inlineQueryResultAudio id:string audio:audio = InlineQueryResult;

//@description Represents a document cached on the telegram server @id Unique identifier of this result @document The document @title Document title @param_description Document description
inlineQueryResultDocument id:string document:document title:string description:string = InlineQueryResult;

//@description Represents a photo cached on the telegram server @id Unique identifier of this result @photo The photo @title Title of the result, if known @param_description Short description of the result, if known
inlineQueryResultPhoto id:string photo:photo title:string description:string = InlineQueryResult;

//@description Represents a sticker cached on the telegram server @id Unique identifier of this result @sticker The sticker
inlineQueryResultSticker id:string sticker:sticker = InlineQueryResult;

//@description Represents a video cached on the telegram server @id Unique identifier of this result @video The video @title Title of the video @param_description Description of the video
inlineQueryResultVideo id:string video:video title:string description:string = InlineQueryResult;

//@description Represents a voice cached on the telegram server @id Unique identifier of this result @voice The voice @title Title of the voice file
inlineQueryResultVoice id:string voice:voice title:string = InlineQueryResult;


//@description Represents results of the inline query. Use sendInlineQueryResultMessage to send the result of the query @inline_query_id Unique identifier of the inline query @next_offset Offset for the next request. If it is empty, there is no more results @results Results of the query
//@switch_pm_text If non-empty, this text should be shown on the button, which opens private chat with the bot and sends bot start message with parameter switch_pm_parameter @switch_pm_parameter Parameter for the bot start message
inlineQueryResults inline_query_id:long next_offset:string results:vector<InlineQueryResult> switch_pm_text:string switch_pm_parameter:string = InlineQueryResults;


//@class CallbackQueryPayload @description Represents payload of a callback query

//@description Payload from a general callback button @data Data that was attached to the callback button
callbackQueryPayloadData data:bytes = CallbackQueryPayload;

//@description Payload from a game callback button @game_short_name Short name of the game that was attached to the callback button
callbackQueryPayloadGame game_short_name:string = CallbackQueryPayload;


//@description Contains answer of a bot to a callback query @text Text of the answer @show_alert If true, an alert should be shown to the user instead of a toast @url URL to be open
callbackQueryAnswer text:string show_alert:Bool url:string = CallbackQueryAnswer;


//@description Contains result of a custom request @result JSON-serialized result
customRequestResult result:string = CustomRequestResult;


//@description Contains one row of the game high scores table @position Position in the high score table @user_id User identifier @score User score
gameHighScore position:int user_id:int score:int = GameHighScore;

//@description Contains list of game high scores @scores List of game high scores
gameHighScores scores:vector<gameHighScore> = GameHighScores;


//@class ChatEventAction @description Represents a chat event

//@description A message was edited @old_message Message before edit @new_message Message after edit
chatEventMessageEdited old_message:message new_message:message = ChatEventAction;

//@description A message was deleted @message Deleted message
chatEventMessageDeleted message:message = ChatEventAction;

//@description A message was pinned @message Pinned message
chatEventMessagePinned message:message = ChatEventAction;

//@description A message was unpinned
chatEventMessageUnpinned = ChatEventAction;

//@description New chat member joined
chatEventMemberJoined = ChatEventAction;

//@description A chat member left
chatEventMemberLeft = ChatEventAction;

//@description New chat member invited @user_id New chat member user identifier @status New chat member status
chatEventMemberInvited user_id:int status:ChatMemberStatus = ChatEventAction;

//@description A chat member promoted to/demoted from administrators @user_id Chat member user identifier @old_status Old chat member status @new_status New chat member status
chatEventMemberPromoted user_id:int old_status:ChatMemberStatus new_status:ChatMemberStatus = ChatEventAction;

//@description A chat member restricted/unrestircted or banned/unbanned @user_id Chat member user identifier @old_status Old chat member status @new_status New chat member status
chatEventMemberRestricted user_id:int old_status:ChatMemberStatus new_status:ChatMemberStatus = ChatEventAction;

//@description Chat title changed @old_title Old title value @new_title New title value
chatEventTitleChanged old_title:string new_title:string = ChatEventAction;

//@description Chat description changed @old_description Old description value @new_description New description value
chatEventDescriptionChanged old_description:string new_description:string = ChatEventAction;

//@description Chat username changed @old_username Old username value @new_username New username value
chatEventUsernameChanged old_username:string new_username:string = ChatEventAction;

//@description Chat photo changed @old_photo Old photo value @new_photo New photo value
chatEventPhotoChanged old_photo:chatPhoto new_photo:chatPhoto = ChatEventAction;

//@description Supergroup channel chat anyone_can_invite setting was toggled @anyone_can_invite New value of anyone_can_invite
chatEventInvitesToggled anyone_can_invite:Bool = ChatEventAction;

//@description Broadcast channel chat sign_messages setting was toggled @sign_messages New value of sign_messages
chatEventSignMessagesToggled sign_messages:Bool = ChatEventAction;

//@description Represents a chat event @id Chat event identifier @date Date of the event, unix time @user_id Identifier of a user who made the action @action Action done by the user
chatEvent id:long date:int user_id:int action:ChatEventAction = ChatEvent;

//@description Contains list of chat events @events The events
chatEvents events:vector<chatEvent> = ChatEvents;

//@description Represents a set of filters used to obtain a chat event log
//@message_edits True, if message edits should be returned
//@message_deletions True, if message deletions should be returned
//@message_pins True, if message pins should be returned
//@member_joins True, if chat member joins should be returned
//@member_leaves True, if chat member leaves should be returned
//@member_invites True, if chat member invites should be returned
//@member_promotions True, if chat member promotions/demotions should be returned
//@member_restrictions True, if chat member restrictions/unrestrictions including bans/unbans should be returned
//@info_changes True, if changes of chat information should be returned
//@setting_changes True, if changes of chat settings should be returned
chatEventLogFilters message_edits:Bool message_deletions:Bool message_pins:Bool member_joins:Bool member_leaves:Bool member_invites:Bool member_promotions:Bool member_restrictions:Bool info_changes:Bool setting_changes:Bool = ChatEventLogFilters;


//@class DeviceToken @description Represents a token for push notifications

//@description Token for APNS @token The token, may be empty to unregister device
deviceTokenApns token:string = DeviceToken;

//@description Token for GCM @token The token, may be empty to unregister device
deviceTokenGcm token:string = DeviceToken;

//@description Token for MPNS @token The token, may be empty to unregister device
deviceTokenMpns token:string = DeviceToken;

//@description Token for simple push @token The token, may be empty to unregister device
deviceTokenSimplePush token:string = DeviceToken;

//@description Token for Ubuntu Push Service @token The token, may be empty to unregister device
deviceTokenUbuntuPhone token:string = DeviceToken;

//@description Token for Blackberry Push Service @token The token, may be empty to unregister device
deviceTokenBlackberry token:string = DeviceToken;


//@description Contains information about one wallpaper @id Unique persistent wallpaper identifier @sizes Available variants of wallpaper of different sizes. These photos can be only downloaded and can't be sent in a message @color Main color of wallpaper in RGB24, should be treated as background color if no photos are specified
wallpaper id:int sizes:vector<photoSize> color:int = Wallpaper;

//@description Contains list of wallpapers @wallpapers List of wallpapers
wallpapers wallpapers:vector<wallpaper> = Wallpapers;


//@description Contatins list of hashtags @hashtags List of hashtags
hashtags hashtags:vector<string> = Hashtags;


//@class OptionValue @description Represents value of an option

//@description Boolean option @value Value of an option
optionValueBoolean value:Bool = OptionValue;

//@description Unknown option or option having default value
optionValueEmpty = OptionValue;

//@description Integer option @value Value of an option
optionValueInteger value:int = OptionValue;

//@description String option @value Value of an option
optionValueString value:string = OptionValue;


//@class PrivacyRule @description Represents one rule for managing privacy settings

//@description Rule to allow all users
privacyRuleAllowAll = PrivacyRule;

//@description Rule to allow all user contacts
privacyRuleAllowContacts = PrivacyRule;

//@description Rule to allow specified users @user_ids User identifiers
privacyRuleAllowUsers user_ids:vector<int> = PrivacyRule;

//@description Rule to disallow all users
privacyRuleDisallowAll = PrivacyRule;

//@description Rule to disallow all user contacts
privacyRuleDisallowContacts = PrivacyRule;

//@description Rule to disallow all specified users @user_ids User identifiers
privacyRuleDisallowUsers user_ids:vector<int> = PrivacyRule;

//@description List of privacy rules. Rules are matched in the specified order. First matched rule defines privacy setting for a given user. If no rule matches action is not allowed @rules List of rules
privacyRules rules:vector<PrivacyRule> = PrivacyRules;

//@class PrivacyKey @description Describes available privacy settings

//@description Privacy key for managing visibility of the user status
privacyKeyUserStatus = PrivacyKey;

//@description Privacy key for managing ability of invitation of the user to chats
privacyKeyChatInvite = PrivacyKey;

//@description Privacy key for managing ability to call the user
privacyKeyCall = PrivacyKey;


//@description Contains information about period of inactivity, after which the account of currently logged in user will be automatically deleted @days Number of days of inactivity before account deletion, should be from 30 and up to 366
accountTtl days:int = AccountTtl;


//@description Contains information about one session in some application used by the user @id Session identifier @is_current True, if it is current session @app_id Application identifier, provided by the application @app_name Name of the application, provided by the application
//@app_version Version of the application, provided by the application @is_official_app True, if the application is an official application or uses the app_id of some official application @device_model Model of a device application is runned on, provided by the application @platform Operating system application is runned on, provided by the application
//@system_version Version of operating system application is runned on, provided by the application @log_in_date Date the user has logged in, unix time @last_active_date Date the session was used last time, unix time @ip An ip address from which session was created in a human-readable format
//@country Two-letter country code from which session was created based on the ip @region Region code from which session was created based on the ip
session id:long is_current:Bool app_id:int app_name:string app_version:string is_official_app:Bool device_model:string platform:string system_version:string log_in_date:int last_active_date:int ip:string country:string region:string = Session;

//@description Contains list of sessions @sessions List of sessions
sessions sessions:vector<session> = Sessions;


//@description Contains information about chat report spam state @can_report_spam If true, prompt with "Report spam" action should be shown to the user
chatReportSpamState can_report_spam:Bool = ChatReportSpamState;

//@class ChatReportReason @description Describes a reason, why a chat is reported

//@description The chat contains spam messages
chatReportReasonSpam = ChatReportReason;

//@description The chat contains violent messages
chatReportReasonViolence = ChatReportReason;

//@description The chat contains pornography messages
chatReportReasonPornography = ChatReportReason;

//@description Other reason provided by the user @text Report text
chatReportReasonOther text:string = ChatReportReason;


//@description Contains public HTTPS link to a message in a public channel @url The link
publicMessageLink url:string = PublicMessageLink;


//@class FileType @description Represents type of a file

//@description Data is not a file
fileTypeNone = FileType;

//@description File is an animation
fileTypeAnimation = FileType;

//@description File is an audio
fileTypeAudio = FileType;

//@description File is a document
fileTypeDocument = FileType;

//@description File is a photo
fileTypePhoto = FileType;

//@description File is a profile photo
fileTypeProfilePhoto = FileType;

//@description File sent to a secret chat
fileTypeSecret = FileType;

//@description File is a sticker
fileTypeSticker = FileType;

//@description File is a thumbnail of another file
fileTypeThumb = FileType;

//@description File type is yet unknown
fileTypeUnknown = FileType;

//@description File is a video
fileTypeVideo = FileType;

//@description File is a video note
fileTypeVideoNote = FileType;

//@description File is a voice audio
fileTypeVoice = FileType;

//@description File is a wallpaper
fileTypeWallpaper = FileType;

//@description File is a thumbnail of a file from a secret chat
fileTypeSecretThumb = FileType;


//@description Contains storage usage statistics for the specific file type @file_type The file type @size Total size of files @count Total number of files
storageStatisticsByFileType file_type:FileType size:int53 count:int = StorageStatisticsByFileType;

//@description Contains storage usage statistics for the specific chat @chat_id Chat identifier, 0 if none @size Total size of files @count Total number of files @by_file_type Statistics splitted by file types
storageStatisticsByChat chat_id:int53 size:int53 count:int by_file_type:vector<storageStatisticsByFileType> = StorageStatisticsByChat;

//@description Contains exact storage usage statistics splitted by chats and file types @size Total size of files @count Total number of files @by_chat Statistics splitted by chats
storageStatistics size:int53 count:int by_chat:vector<storageStatisticsByChat> = StorageStatistics;

//@description Contains approximate storage usage statistics, not containing files of Unknown type @files_size Approximate total size of files @files_count Approximate number of files @database_size Size of database
storageStatisticsFast files_size:int53 files_count:int database_size:int53 = StorageStatisticsFast;


//@class NetworkType @description Represents type of a network

//@description Network is not available
networkTypeNone = NetworkType;

//@description Mobile network
networkTypeMobile = NetworkType;

//@description Mobile roaming network
networkTypeMobileRoaming = NetworkType;

//@description Wi-Fi network
networkTypeWiFi = NetworkType;

//@description Other network type, for example, Ethernet network
networkTypeOther = NetworkType;


//@class NetworkStatisticsEntry @description Contains statistics about network usage

//@description Contains information about total received and sent files data @file_type Type of a file the data is part of @network_type Type of a network the data was sent through. Call setNetworkType to maintain actual network type
//@sent_bytes Total number of sent bytes @received_bytes Total number of received bytes
networkStatisticsEntryFile file_type:FileType network_type:NetworkType sent_bytes:int53 received_bytes:int53 = NetworkStatisticsEntry;

//@description Contains information about total received and sent calls data @network_type Type of a network the data was sent through. Call setNetworkType to maintain actual network type
//@sent_bytes Total number of sent bytes @received_bytes Total number of received bytes @duration Total calls duration in seconds
networkStatisticsEntryCall network_type:NetworkType sent_bytes:int53 received_bytes:int53 duration:double = NetworkStatisticsEntry;

//@description Full list of available network statistics entries @since_date Date since which statistics are collected, unix time @entries Network statistics entries
networkStatistics since_date:int entries:vector<NetworkStatisticsEntry> = NetworkStatistics;


//@class ConnectionState @description Describes current state of the connection to Telegram servers

//@description Waiting for network to be available. Use SetNetworkType to change available network type
connectionStateWaitingForNetwork = ConnectionState;

//@description Establishing connection to set up proxy server
connectionStateConnectingToProxy = ConnectionState;

//@description Establishing connection to Telegram servers
connectionStateConnecting = ConnectionState;

//@description Downloading date received, while client was offline
connectionStateUpdating = ConnectionState;

//@description There is a working connection to the Telegram servers
connectionStateReady = ConnectionState;


//@class TopChatCategory @description Represents categories of chats for which list of frequently used chats can be retrieved

//@description The category containing private chats with non-bot users
topChatCategoryUsers = TopChatCategory;

//@description The category containing private chats with bot users
topChatCategoryBots = TopChatCategory;

//@description The category containing group chats and supergroup channel chats
topChatCategoryGroups = TopChatCategory;

//@description The category containing broadcast channel chats
topChatCategoryChannels = TopChatCategory;

//@description The category containing chats with inline bots sorted by their usage in the inline mode
topChatCategoryInlineBots = TopChatCategory;

//@description The category containing chats frequently used for calls
topChatCategoryCalls = TopChatCategory;


//@description Contains some text @text The text
text text:string = Text;


//@class Proxy @description Contains information about a proxy server

//@description An empty proxy
proxyEmpty = Proxy;

//@description A SOCKS5 proxy @server Proxy server ip address @port Proxy server port @username Username to log in @password Password to log in
proxySocks5 server:string port:int username:string password:string = Proxy;


//@description Description of a sticker which should be added to a sticker set @png_sticker Png image with the sticker, must be up to 512 kilobytes in size and fit in 512x512 square @emojis Emojis corresponding to the sticker @mask_position Position where the mask should be placed, nullable
inputSticker png_sticker:InputFile emojis:string mask_position:maskPosition = InputSticker;


//@class Update @description Contains notifications about data changes

//@description User authorization state has changed @auth_state New authorization state
updateAuthState auth_state:AuthState = Update;

//@description New message received, maybe outcoming message sent from other device @message New message @disable_notification If true, notification about the message should be disabled @contains_mention True, if the message contains mention of the current user
updateNewMessage message:message disable_notification:Bool contains_mention:Bool = Update;

//@description Message send request has reached Telegram server. It doesn't mean that message send will be successful or even that message send request will be processed. Update will not come, unless option "use_quick_ack" is set to true. The update may come many times for the same message
//@chat_id Chat identifier of sent message @message_id Temporary message identifier
updateMessageSendAcknowledged chat_id:int53 message_id:int53 = Update;

//@description Message is successfully sent @message Information about sent message. Usually only message identifier, date and content are changed, but almost any other fields can also change @old_message_id Previous temporary message identifier
updateMessageSendSucceeded message:message old_message_id:int53 = Update;

//@description Message fails to send. Be aware that some being sent messages can be irrecoverably deleted and updateDeleteMessages will come instead of this update
//@message Information about failed to send message @old_message_id Previous temporary message identifier @error_code Error code @error_message Error message
updateMessageSendFailed message:message old_message_id:int53 error_code:int error_message:string = Update;

//@description Message content has changed @chat_id Chat identifier @message_id Message identifier @new_content New message content
updateMessageContent chat_id:int53 message_id:int53 new_content:MessageContent = Update;

//@description Message was edited. Changes in the message content will come in a separate updateMessageContent @chat_id Chat identifier @message_id Message identifier @edit_date Date the message was edited, unix time @reply_markup New message reply markup, nullable
updateMessageEdited chat_id:int53 message_id:int53 edit_date:int reply_markup:ReplyMarkup = Update;

//@description View count of the message has changed @chat_id Chat identifier @message_id Message identifier @views New value of view count
updateMessageViews chat_id:int53 message_id:int53 views:int = Update;

//@description Message content was opened. It makes voice messages listened, video note messages viewed and runs ttl timer @chat_id Chat identifier @message_id Message identifier
updateOpenMessageContent chat_id:int53 message_id:int53 = Update;

//@description New chat has been loaded/created. This update is guaranteed to come before chat identifier is returned to the client. Chat field changes will be reported through separate updates @chat The chat
updateNewChat chat:chat = Update;

//@description Title of the chat was changed @chat_id Chat identifier @title New chat title
updateChatTitle chat_id:int53 title:string = Update;

//@description Chat photo was changed @chat_id Chat identifier @photo New chat photo, nullable
updateChatPhoto chat_id:int53 photo:chatPhoto = Update;

//@description Top message of the chat has changed. If top_message is null then top message in the chat became unknown. Some new unknown messages might be added to the chat in that case @chat_id Chat identifier @top_message New top message of the chat, nullable
updateChatTopMessage chat_id:int53 top_message:message = Update;

//@description Order of the chat in the chat list has changed @chat_id Chat identifier @order New value of the order
updateChatOrder chat_id:int53 order:long = Update;

//@description Chat was pinned or unpinned @chat_id Chat identifier @is_pinned New value of is_pinned
updateChatIsPinned chat_id:int53 is_pinned:Bool = Update;

//@description Some incoming messages was read @chat_id Chat identifier @last_read_inbox_message_id Identifier of last read incoming message @unread_count Number of unread messages left in chat
updateChatReadInbox chat_id:int53 last_read_inbox_message_id:int53 unread_count:int = Update;

//@description Some outcoming messages was read @chat_id Chat identifier @last_read_outbox_message_id Identifier of last read outgoing message
updateChatReadOutbox chat_id:int53 last_read_outbox_message_id:int53 = Update;

//@description Notification settings for some chats was updated @scope Kinds of chats for which notification settings was updated @notification_settings New notification settings
updateNotificationSettings scope:NotificationSettingsScope notification_settings:notificationSettings = Update;

//@description Default chat reply markup has changed. It can happen because new message with reply markup has come or old reply markup was hidden by user
//@chat_id Chat identifier @reply_markup_message_id Identifier of the message from which reply markup need to be used or 0 if there is no default custom reply markup in the chat
updateChatReplyMarkup chat_id:int53 reply_markup_message_id:int53 = Update;

//@description Chat draft has changed. Be aware that the update may come in the currently open chat with the old content of the draft. If the user has changed the content of the draft, the update shouldn't be applied @chat_id Chat identifier @draft_message New chat draft_message, nullable
updateChatDraftMessage chat_id:int53 draft_message:draftMessage = Update;

//@description Some messages was deleted @chat_id Chat identifier @message_ids Identifiers of deleted message
updateDeleteMessages chat_id:int53 message_ids:vector<int53> = Update;

//@description User activity in the chat has changed @chat_id Chat identifier @user_id Identifier of user doing action @action Action description
updateUserChatAction chat_id:int53 user_id:int action:ChatAction = Update;

//@description User went online/offline @user_id User identifier @status New user status
updateUserStatus user_id:int status:UserStatus = Update;

//@description Some data about a user has been changed. This update is guaranteed to come before user identifier is returned to the client, if library knows anything about the user @user New data about the user
updateUser user:user = Update;

//@description Some data about a group has been changed. This update is guaranteed to come before group identifier is returned to the client, if library knows anything about the group @group New data about the group
updateGroup group:group = Update;

//@description Some data about a channel has been changed. This update is guaranteed to come before channel identifier is returned to the client, if library knows anything about the channel @channel New data about the channel
updateChannel channel:channel = Update;

//@description Some data about a secret chat has been changed. This update is guaranteed to come before secret chat identifier is returned to the client, if library knows anything about the secret chat @secret_chat New data about the secret chat
updateSecretChat secret_chat:secretChat = Update;

//@description Some data from userFull has been changed @user_id User identifier @user_full New full information about the user
updateUserFull user_id:int user_full:userFull = Update;

//@description Some data from groupFull has been changed @group_id Group identifier @group_full New full information about the group
updateGroupFull group_id:int group_full:groupFull = Update;

//@description Some data from channelFull has been changed @channel_id Channel identifier @channel_full New full information about the channel
updateChannelFull channel_id:int channel_full:channelFull = Update;

//@description Service notification from the server. Upon receiving client should show popup with content of the notification @type Type of the notification @content Notification content
updateServiceNotification type:string content:MessageContent = Update;

//@description DEPRECATED. Use updateFile instead. File is partly downloaded/uploaded @file_id File identifier @size Total file size (0 means unknown) @ready Number of bytes already downloaded/uploaded. Negative number means that download/upload has failed and was terminated
updateFileProgress file_id:int size:int ready:int = Update;

//@description Information about a file was updated @file New data about a file
updateFile file:file = Update;

//@description File generation process need to be started by the client @generation_id Unique identifier for the generation process @original_path Path to a file from which new file is generated, may be empty
//@destination_path Path to a file which should be created and to which new file should be generated @conversion String specifying conversion applied to the original file
updateFileGenerationStart generation_id:long original_path:string destination_path:string conversion:string = Update;

//@description Informs that a file is being generated @file_id File identifier @size Expected size of the generated file @ready Number of bytes already generated. Negative number means that generation has failed and was terminated
updateFileGenerationProgress file_id:int size:int ready:int = Update;

//@description DEPRECATED. Use updateFile instead. File generation is finished @file Generated file
updateFileGenerationFinish file:file = Update;

//@description Information about a call was updated @call New data about a call
updateCall call:call = Update;

//@description Some privacy settings has changed @key Privacy key @rules New privacy rules
updatePrivacy key:PrivacyKey rules:privacyRules = Update;

//@description Some option changed its value @name Option name @value New option value
updateOption name:string value:OptionValue = Update;

//@description List of installed sticker sets was updated @is_masks True, if list of installed mask sticker sets was updated @sticker_set_ids New list of installed ordinary sticker sets
updateInstalledStickerSets is_masks:Bool sticker_set_ids:vector<long> = Update;

//@description List of trending sticker sets was updated or some of them was viewed @sticker_sets New list of trending sticker sets
updateTrendingStickerSets sticker_sets:stickerSets = Update;

//@description List of recently used stickers was updated @is_attached True, if the list of stickers attached to photo or video files was updated, otherwise the list of sent stickers is updated @sticker_ids New list of file identifiers of recently used stickers
updateRecentStickers is_attached:Bool sticker_ids:vector<int> = Update;

//@description List of saved animations was updated @animation_ids New list of file identifiers of saved animations
updateSavedAnimations animation_ids:vector<int> = Update;

//@description Connection state has changed @state New connection state
updateConnectionState state:ConnectionState = Update;

//@description Bots only. New incoming inline query @id Unique query identifier @sender_user_id Identifier of the user who sent the query @user_location User location, provided by the client, nullable @query Text of the query @offset Offset of the first entry to return
updateNewInlineQuery id:long sender_user_id:int user_location:location query:string offset:string = Update;

//@description Bots only. User has chosen a result of the inline query @sender_user_id Identifier of the user who sent the query @user_location User location, provided by the client, nullable @query Text of the query @result_id Identifier of the chosen result @inline_message_id Identifier of the sent inline message, if known
updateNewChosenInlineResult sender_user_id:int user_location:location query:string result_id:string inline_message_id:string = Update;

//@description Bots only. New incoming callback query @id Unique query identifier @sender_user_id Identifier of the user who sent the query @chat_id Identifier of the chat, in which the query was sent
//@message_id Identifier of the message, from which the query is originated @chat_instance Identifier, uniquely corresponding to the chat a message was sent to @payload Query payload
updateNewCallbackQuery id:long sender_user_id:int chat_id:int53 message_id:int53 chat_instance:long payload:CallbackQueryPayload = Update;

//@description Bots only. New incoming callback query from message sent via bot @id Unique query identifier @sender_user_id Identifier of the user who sent the query @inline_message_id Identifier of the inline message, from which the query is originated
//@chat_instance Identifier, uniquely corresponding to the chat a message was sent to @payload Query payload
updateNewInlineCallbackQuery id:long sender_user_id:int inline_message_id:string chat_instance:long payload:CallbackQueryPayload = Update;

//@description Bots only. New incoming shipping query. Only for invoices with flexible price @id Unique query identifier @sender_user_id Identifier of the user who sent the query @invoice_payload Invoice payload @shipping_address User shipping address
updateNewShippingQuery id:long sender_user_id:int invoice_payload:string shipping_address:shippingAddress = Update;

//@description Bots only. New incoming pre-checkout query. Contains full information about checkout @id Unique query identifier @sender_user_id Identifier of the user who sent the query @currency Currency for goods price @total_amount Goods total price in minimal quantity of the currency
//@invoice_payload Invoice payload @shipping_option_id Identifier of a choosed by user shipping option, may be empty if not applicable @order_info Information about the order, nullable
updateNewPreCheckoutQuery id:long sender_user_id:int currency:string total_amount:int53 invoice_payload:bytes shipping_option_id:string order_info:orderInfo = Update;

//@description Bots only. New incoming event @event JSON-serialized event
updateNewCustomEvent event:string = Update;

//@description Bots only. New incoming query @id Query identifier @data JSON-serialized query data @timeout Query timeout
updateNewCustomQuery id:long data:string timeout:int = Update;


//@description Tests only. A simple object containing just a number @value The number
testInt value:int = TestInt;
//@description Tests only. A simple object containing just a string @value The string
testString value:string = TestString;
//@description Tests only. A simple object containing just a bytes @value The bytes
testBytes value:bytes = TestBytes;
//@description Tests only. A simple object containing just a vector of numbers @value The vector of numbers
testVectorInt value:vector<int> = TestVectorInt;
//@description Tests only. A simple object containing just a vector of objects containing a number @value The vector of objects
testVectorIntObject value:vector<testInt> = TestVectorIntObject;
//@description Tests only. A simple object containing just a vector of strings @value The vector of strings
testVectorString value:vector<string> = TestVectorString;
//@description Tests only. A simple object containing just a vector of objects containing a string @value The vector of objects
testVectorStringObject value:vector<testString> = TestVectorStringObject;

---functions---

//@description Returns current authorization state, offline request
getAuthState = AuthState;

//@description Sets user's phone number and sends authentication code to the user. Works only when getAuthState returns authStateWaitPhoneNumber. If phone number is not recognized or another error has happened, returns an error. Otherwise returns authStateWaitCode
//@phone_number User's phone number in any reasonable format @allow_flash_call Pass True, if code can be sent via flash call to the specified phone number @is_current_phone_number Pass true, if the phone number is used on the current device. Ignored if allow_flash_call is False
setAuthPhoneNumber phone_number:string allow_flash_call:Bool is_current_phone_number:Bool = AuthState;

//@description Resends authentication code to the user. Works only when getAuthState returns authStateWaitCode and next_code_type of result is not null. Returns authStateWaitCode on success
resendAuthCode = AuthState;

//@description Checks authentication code. Works only when getAuthState returns authStateWaitCode. Returns authStateWaitPassword or authStateOk on success @code Verification code from SMS, Telegram message, phone call or flash call
//@first_name User first name, if user is yet not registered, 1-255 characters @last_name Optional user last name, if user is yet not registered, 0-255 characters
checkAuthCode code:string first_name:string last_name:string = AuthState;

//@description Checks password for correctness. Works only when getAuthState returns authStateWaitPassword. Returns authStateOk on success @password Password to check
checkAuthPassword password:string = AuthState;

//@description Requests to send password recovery code to email. Works only when getAuthState returns authStateWaitPassword. Returns authStateWaitPassword on success
requestAuthPasswordRecovery = AuthState;

//@description Recovers password with recovery code sent to email. Works only when getAuthState returns authStateWaitPassword. Returns authStateOk on success @recovery_code Recovery code to check
recoverAuthPassword recovery_code:string = AuthState;

//@description Logs out user. If force == false, begins to perform soft log out, returns authStateLoggingOut after completion. If force == true then succeeds almost immediately without cleaning anything at the server, but returns error with code 401 and description "Unauthorized"
//@force If true, just delete all local data. Session will remain in list of active sessions
resetAuth force:Bool = AuthState;

//@description Check bot's authentication token to log in as a bot. Works only when getAuthState returns authStateWaitPhoneNumber. Can be used instead of setAuthPhoneNumber and checkAuthCode to log in. Returns authStateOk on success @token Bot token
checkAuthBotToken token:string = AuthState;


//@description Returns current state of two-step verification
getPasswordState = PasswordState;

//@description Changes user password. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and password change will not be applied until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed
//@old_password Old user password @new_password New user password, may be empty to remove the password @new_hint New password hint, can be empty @set_recovery_email Pass True, if recovery email should be changed @new_recovery_email New recovery email, may be empty
setPassword old_password:string new_password:string new_hint:string set_recovery_email:Bool new_recovery_email:string = PasswordState;

//@description Returns set up recovery email. This method can be used to verify a password provided by the user @password Current user password
getRecoveryEmail password:string = RecoveryEmail;

//@description Changes user recovery email. If new recovery email is specified, then error EMAIL_UNCONFIRMED is returned and email will not be changed until email confirmation. Application should call getPasswordState from time to time to check if email is already confirmed. -If new_recovery_email coincides with the current set up email succeeds immediately and aborts all other requests waiting for email confirmation @password Current user password @new_recovery_email New recovery email
setRecoveryEmail password:string new_recovery_email:string = PasswordState;

//@description Requests to send password recovery code to email
requestPasswordRecovery = PasswordRecoveryInfo;

//@description Recovers password with recovery code sent to email @recovery_code Recovery code to check
recoverPassword recovery_code:string = PasswordState;

//@description Creates new temporary password for payments processing @password Persistent user password @valid_for Time before temporary password will expire, seconds. Should be between 60 and 86400
createTemporaryPassword password:string valid_for:int = TemporaryPasswordState;

//@description Returns information about current temporary password
getTemporaryPasswordState = TemporaryPasswordState;


//@description Handles DC_UPDATE push service notification. Can be called before authorization @dc Value of 'dc' paramater of the notification @addr Value of 'addr' parameter of the notification
processDcUpdate dc:string addr:string = Ok;


//@description Returns current logged in user
getMe = User;

//@description Returns information about a user by its identifier, offline request if current user is not a bot @user_id User identifier
getUser user_id:int = User;

//@description Returns full information about a user by its identifier @user_id User identifier
getUserFull user_id:int = UserFull;

//@description Returns information about a group by its identifier, offline request if current user is not a bot @group_id Group identifier
getGroup group_id:int = Group;

//@description Returns full information about a group by its identifier @group_id Group identifier
getGroupFull group_id:int = GroupFull;

//@description Returns information about a channel by its identifier, offline request if current user is not a bot @channel_id Channel identifier
getChannel channel_id:int = Channel;

//@description Returns full information about a channel by its identifier, cached for at most 1 minute @channel_id Channel identifier
getChannelFull channel_id:int = ChannelFull;

//@description Returns information about a secret chat by its identifier, offline request @secret_chat_id Secret chat identifier
getSecretChat secret_chat_id:int = SecretChat;

//@description Returns information about a chat by its identifier, offline request if current user is not a bot @chat_id Chat identifier
getChat chat_id:int53 = Chat;

//@description Returns information about a message @chat_id Identifier of the chat, message belongs to @message_id Identifier of the message to get
getMessage chat_id:int53 message_id:int53 = Message;

//@description Returns information about messages. If message is not found, returns null on the corresponding position of the result @chat_id Identifier of the chat, messages belongs to @message_ids Identifiers of the messages to get
getMessages chat_id:int53 message_ids:vector<int53> = Messages;

//@description Returns information about a file, offline request @file_id Identifier of the file to get
getFile file_id:int = File;

//@description Returns information about a file by its persistent id, offline request. May be used to register a URL as a file for further uploading or sending as message @persistent_file_id Persistent identifier of the file to get @file_type File type, if known
getFilePersistent persistent_file_id:string file_type:FileType = File;

//@description Returns list of chats in the right order, chats are sorted by (order, chat_id) in decreasing order. For example, to get list of chats from the beginning, the offset_order should be equal 2^63 - 1 @offset_order Chat order to return chats from @offset_chat_id Chat identifier to return chats from
//@limit Maximum number of chats to be returned. There may be less than limit chats returned even the end of the list is not reached
getChats offset_order:long offset_chat_id:int53 limit:int = Chats;

//@description Searches public chat by its username. Currently only private and channel chats can be public. Returns chat if found, otherwise some error is returned @username Username to be resolved
searchPublicChat username:string = Chat;

//@description Searches public chats by prefix of their username. Currently only private and channel (including supergroup) chats can be public. Returns meaningful number of results. Returns nothing if length of the searched username prefix is less than 5. Excludes private chats with contacts from the results @username_prefix Prefix of the username to search
searchPublicChats username_prefix:string = Chats;

//@description Searches for specified query in the title and username of known chats, offline request. Returns chats in the order of them in the chat list @query Query to search for, if query is empty, returns up to 20 recently found chats @limit Maximum number of chats to be returned
searchChats query:string limit:int = Chats;

//@description Returns a list of frequently used chats. Supported only if chat info database is enabled @category Category of chats to return @limit Maximum number of chats to be returned, at most 30
getTopChats category:TopChatCategory limit:int = Chats;

//@description Delete a chat from a list of frequently used chats. Supported only if chat info database is enabled @category Category of frequently used chats @chat_id Chat identifier
deleteTopChat category:TopChatCategory chat_id:int53 = Ok;

//@description Adds chat to the list of recently found chats. The chat is added to the beginning of the list. If the chat is already in the list, at first it is removed from the list @chat_id Identifier of the chat to add
addRecentlyFoundChat chat_id:int53 = Ok;

//@description Deletes chat from the list of recently found chats @chat_id Identifier of the chat to delete
deleteRecentlyFoundChat chat_id:int53 = Ok;

//@description Clears list of recently found chats
deleteRecentlyFoundChats = Ok;

//@description Returns list of common chats with an other given user. Chats are sorted by their type and creation date @user_id User identifier @offset_chat_id Chat identifier to return chats from, use 0 for the first request @limit Maximum number of chats to be returned, up to 100
getCommonChats user_id:int offset_chat_id:int53 limit:int = Chats;

//@description Returns list of created public chats
getCreatedPublicChats = Chats;


//@description Returns messages in a chat. Automatically calls openChat. Returns result in reverse chronological order, i.e. in order of decreasing message.message_id. Offline request if only_local is true @chat_id Chat identifier
//@from_message_id Identifier of the message near which we need a history, you can use 0 to get results from the beginning, i.e. from oldest to newest
//@offset Specify 0 to get results exactly from from_message_id or negative offset to get specified message and some newer messages
//@limit Maximum number of messages to be returned, should be positive and can't be greater than 100. If offset is negative, limit must be greater than -offset. There may be less than limit messages returned even the end of the history is not reached
//@only_local Return only locally available messages without sending network requests
getChatHistory chat_id:int53 from_message_id:int53 offset:int limit:int only_local:Bool = Messages;

//@description Deletes all messages in the chat. Can't be used for channel chats @chat_id Chat identifier @remove_from_chat_list Pass true, if chat should be removed from the chat list
deleteChatHistory chat_id:int53 remove_from_chat_list:Bool = Ok;

//@description Searches for messages with given words in the chat. Returns result in reverse chronological order, i. e. in order of decreasing message_id. Doesn't work in secret chats with non-empty query (searchSecretMessages should be used instead) or without enabled message database @chat_id Chat identifier to search in
//@query Query to search for @sender_user_id If not 0, only messages sent by the specified user will be returned. Doesn't supported in secret chats @from_message_id Identifier of the message from which we need a history, you can use 0 to get results from the beginning
//@limit Maximum number of messages to be returned, can't be greater than 100. There may be less than limit messages returned even the end of the history is not reached @filter Filter for content of searched messages
searchChatMessages chat_id:int53 query:string sender_user_id:int from_message_id:int53 limit:int filter:SearchMessagesFilter = Messages;

//@description Searches for messages in all chats except secret chats. Returns result in reverse chronological order, i. e. in order of decreasing (date, chat_id, message_id)
//@query Query to search for
//@offset_date Date of the message to search from, you can use 0 or any date in the future to get results from the beginning
//@offset_chat_id Chat identifier of the last found message or 0 for the first request
//@offset_message_id Message identifier of the last found message or 0 for the first request
//@limit Maximum number of messages to be returned, at most 100
searchMessages query:string offset_date:int offset_chat_id:int53 offset_message_id:int53 limit:int = Messages;

//@description Searches for messages in secret chats. Returns result in reverse chronological order @chat_id Identifier of a chat to search in. Specify 0 to search in all secret chats @query Query to search for. If empty, searchChatMessages should be used instead
//@from_search_id Identifier from the result of previous request, use 0 to get results from the beginning @limit Maximum number of messages to be returned, can't be greater than 100 @filter Filter for content of searched messages
searchSecretMessages chat_id:int53 query:string from_search_id:long limit:int filter:SearchMessagesFilter = FoundMessages;

//@description Searches for call messages. Returns result in reverse chronological order, i. e. in order of decreasing message_id @from_message_id Identifier of the message from which to search, you can use 0 to get results from beginning
//@limit Maximum number of messages to be returned, can't be greater than 100. There may be less than limit messages returned even the end of the history is not reached filter @only_missed If true, return only messages with missed calls
searchCallMessages from_message_id:int53 limit:int only_missed:Bool = Messages;

//@description Returns public HTTPS link to a message. Available only for messages in public channels @chat_id Identifier of the chat, message belongs to @message_id Identifier of the message
getPublicMessageLink chat_id:int53 message_id:int53 = PublicMessageLink;

//@description Sends a message. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message @chat_id Chat to send message @reply_to_message_id Identifier of a message to reply to or 0
//@disable_notification Pass true, to disable notification about the message, doesn't works in secret chats @from_background Pass true, if the message is sent from background
//@reply_markup Bots only. Markup for replying to message @input_message_content Content of a message to send
sendMessage chat_id:int53 reply_to_message_id:int53 disable_notification:Bool from_background:Bool reply_markup:ReplyMarkup input_message_content:InputMessageContent = Message;

//@description Invites bot to a chat (if it is not in the chat) and send /start to it. Bot can't be invited to a private chat other than chat with the bot. Bots can't be invited to broadcast channel chats and secret chats. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message
//@bot_user_id Identifier of the bot @chat_id Identifier of the chat @parameter Hidden parameter sent to bot for deep linking (https://api.telegram.org/bots#deep-linking)
sendBotStartMessage bot_user_id:int chat_id:int53 parameter:string = Message;

//@description Sends result of the inline query as a message. Returns sent message. UpdateChatTopMessage will not be sent, so returned message should be used to update chat top message. Always clears chat draft message @chat_id Chat to send message @reply_to_message_id Identifier of a message to reply to or 0
//@disable_notification Pass true, to disable notification about the message, doesn't works in secret chats @from_background Pass true, if the message is sent from background
//@query_id Identifier of the inline query @result_id Identifier of the inline result
sendInlineQueryResultMessage chat_id:int53 reply_to_message_id:int53 disable_notification:Bool from_background:Bool query_id:long result_id:string = Message;

//@description Forwards previously sent messages. Returns forwarded messages in the same order as message identifiers passed in message_ids. If message can't be forwarded, null will be returned instead of the message. UpdateChatTopMessage will not be sent, so returned messages should be used to update chat top message
//@chat_id Identifier of a chat to forward messages @from_chat_id Identifier of a chat to forward from @message_ids Identifiers of messages to forward
//@disable_notification Pass true, to disable notification about the message, doesn't works if messages are forwarded to secret chat @from_background Pass true, if the message is sent from background
forwardMessages chat_id:int53 from_chat_id:int53 message_ids:vector<int53> disable_notification:Bool from_background:Bool = Messages;

//@description Changes current ttl setting in a secret chat and sends corresponding message @chat_id Chat identifier @ttl New value of ttl in seconds
sendChatSetTtlMessage chat_id:int53 ttl:int = Message;

//@description Sends notification about screenshot taken in a chat. Works only in private and secret chats @chat_id Chat identifier
sendChatScreenshotTakenNotification chat_id:int53 = Ok;

//@description Deletes messages @chat_id Chat identifier @message_ids Identifiers of messages to delete @revoke Pass true to try to delete sent messages for all chat members (may fail if messages are too old). Is always true for Channels and SecretChats
deleteMessages chat_id:int53 message_ids:vector<int53> revoke:Bool = Ok;

//@description Deletes all messages in the chat sent by the specified user. Works only in supergroup channel chats, needs can_delete_messages administrator privileges @chat_id Chat identifier @user_id User identifier
deleteMessagesFromUser chat_id:int53 user_id:int = Ok;


//@description Edits text of text or game message. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side
//@chat_id Chat the message belongs to @message_id Identifier of the message @reply_markup Bots only. New message reply markup @input_message_content New text content of the message. Should be of type InputMessageText
editMessageText chat_id:int53 message_id:int53 reply_markup:ReplyMarkup input_message_content:InputMessageContent = Message;

//@description Edits message content caption. Non-bots can edit message in a limited period of time. Returns edited message after edit is complete server side
//@chat_id Chat the message belongs to @message_id Identifier of the message @reply_markup Bots only. New message reply markup @caption New message content caption, 0-200 characters
editMessageCaption chat_id:int53 message_id:int53 reply_markup:ReplyMarkup caption:string = Message;

//@description Bots only. Edits message reply markup. Returns edited message after edit is complete server side
//@chat_id Chat the message belongs to @message_id Identifier of the message @reply_markup New message reply markup
editMessageReplyMarkup chat_id:int53 message_id:int53 reply_markup:ReplyMarkup = Message;

//@description Bots only. Edits text of an inline text or game message sent via bot @inline_message_id Inline message identifier @reply_markup New message reply markup @input_message_content New text content of the message. Should be of type InputMessageText
editInlineMessageText inline_message_id:string reply_markup:ReplyMarkup input_message_content:InputMessageContent = Ok;

//@description Bots only. Edits caption of an inline message content sent via bot @inline_message_id Inline message identifier @reply_markup New message reply markup @caption New message content caption, 0-200 characters
editInlineMessageCaption inline_message_id:string reply_markup:ReplyMarkup caption:string = Ok;

//@description Bots only. Edits reply markup of an inline message sent via bot @inline_message_id Inline message identifier @reply_markup New message reply markup
editInlineMessageReplyMarkup inline_message_id:string reply_markup:ReplyMarkup = Ok;


//@description Sends inline query to a bot and returns its results. Returns error with code 502 if bot fails to answer the query before query timeout expires. Unavailable for bots @bot_user_id Identifier of the bot send query to
//@chat_id Identifier of the chat, where the query is sent @user_location User location, only if needed @query Text of the query @offset Offset of the first entry to return
getInlineQueryResults bot_user_id:int chat_id:int53 user_location:location query:string offset:string = InlineQueryResults;

//@description Bots only. Sets result of an inline query @inline_query_id Identifier of the inline query @is_personal Does result of the query can be cached only for specified user
//@results Results of the query @cache_time Allowed time to cache results of the query in seconds @next_offset Offset for the next inline query, pass empty string if there is no more results
//@switch_pm_text If non-empty, this text should be shown on the button, which opens private chat with the bot and sends bot start message with parameter switch_pm_parameter @switch_pm_parameter Parameter for the bot start message
answerInlineQuery inline_query_id:long is_personal:Bool results:vector<InputInlineQueryResult> cache_time:int next_offset:string switch_pm_text:string switch_pm_parameter:string = Ok;


//@description Sends callback query to a bot and returns answer to it. Returns error with code 502 if bot fails to answer the query before query timeout expires. Unavailable for bots @chat_id Identifier of the chat with a message @message_id Identifier of the message, from which the query is originated @payload Query payload
getCallbackQueryAnswer chat_id:int53 message_id:int53 payload:CallbackQueryPayload = CallbackQueryAnswer;

//@description Bots only. Sets result of a callback query @callback_query_id Identifier of the callback query @text Text of the answer @show_alert If true, an alert should be shown to the user instead of a toast @url Url to be opened @cache_time Allowed time to cache result of the query in seconds
answerCallbackQuery callback_query_id:long text:string show_alert:Bool url:string cache_time:int = Ok;


//@description Bots only. Sets result of a shipping query @shipping_query_id Identifier of the shipping query @shipping_options Available shipping options @error_message Error message, empty on success
answerShippingQuery shipping_query_id:long shipping_options:vector<shippingOption> error_message:string = Ok;

//@description Bots only. Sets result of a pre checkout query @pre_checkout_query_id Identifier of the pre-checkout query @error_message Error message, empty on success
answerPreCheckoutQuery pre_checkout_query_id:long error_message:string = Ok;


//@description Bots only. Updates game score of the specified user in the game @chat_id Chat a message with the game belongs to @message_id Identifier of the message @edit_message True, if message should be edited @user_id User identifier @score New score
//@force Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table
setGameScore chat_id:int53 message_id:int53 edit_message:Bool user_id:int score:int force:Bool = Message;

//@description Bots only. Updates game score of the specified user in the game @inline_message_id Inline message identifier @edit_message True, if message should be edited @user_id User identifier @score New score
//@force Pass True to update the score even if it decreases. If score is 0, user will be deleted from the high scores table
setInlineGameScore inline_message_id:string edit_message:Bool user_id:int score:int force:Bool = Ok;

//@description Bots only. Returns game high scores and some part of the score table around of the specified user in the game @chat_id Chat a message with the game belongs to @message_id Identifier of the message @user_id User identifie
getGameHighScores chat_id:int53 message_id:int53 user_id:int = GameHighScores;

//@description Bots only. Returns game high scores and some part of the score table around of the specified user in the game @inline_message_id Inline message identifier @user_id User identifier
getInlineGameHighScores inline_message_id:string user_id:int = GameHighScores;


//@description Deletes default reply markup from chat. This method needs to be called after one-time keyboard or ForceReply reply markup has been used. UpdateChatReplyMarkup will be send if reply markup will be changed @chat_id Chat identifier
//@message_id Message identifier of used keyboard
deleteChatReplyMarkup chat_id:int53 message_id:int53 = Ok;


//@description Sends notification about user activity in a chat @chat_id Chat identifier @action Action description
sendChatAction chat_id:int53 action:ChatAction = Ok;


//@description Chat is opened by the user. Many useful activities depends on chat being opened or closed. For example, in channels all updates are received only for opened chats @chat_id Chat identifier
openChat chat_id:int53 = Ok;

//@description Chat is closed by the user. Many useful activities depends on chat being opened or closed. @chat_id Chat identifier
closeChat chat_id:int53 = Ok;

//@description Messages are viewed by the user. Many useful activities depends on message being viewed. For example, marking messages as read, incrementing of view counter, updating of view counter, removing of deleted messages in channels @chat_id Chat identifier @message_ids Identifiers of viewed messages
viewMessages chat_id:int53 message_ids:vector<int53> = Ok;

//@description Message content is opened, for example the user has opened a photo, a video, a document, a location or a venue or have listened to an audio or a voice message. You will receive updateOpenMessageContent if something has changed @chat_id Chat identifier of the message @message_id Identifier of the message with opened content
openMessageContent chat_id:int53 message_id:int53 = Ok;


//@description Returns existing chat corresponding to the given user @user_id User identifier
createPrivateChat user_id:int = Chat;

//@description Returns existing chat corresponding to the known group @group_id Group identifier
createGroupChat group_id:int = Chat;

//@description Returns existing chat corresponding to the known channel @channel_id Channel identifier
createChannelChat channel_id:int = Chat;

//@description Returns existing chat corresponding to the known secret chat @secret_chat_id SecretChat identifier
createSecretChat secret_chat_id:int = Chat;

//@description Creates new group chat and send corresponding messageGroupChatCreate, returns created chat @user_ids Identifiers of users to add to the group @title Title of new group chat, 1-255 characters
createNewGroupChat user_ids:vector<int> title:string = Chat;

//@description Creates new channel chat and send corresponding messageChannelChatCreate, returns created chat @title Title of new channel chat, 1-255 characters @is_supergroup True, if supergroup chat should be created @param_description Channel description, 0-255 characters
createNewChannelChat title:string is_supergroup:Bool description:string = Chat;

//@description Creates new secret chat, returns created chat @user_id Identifier of a user to create secret chat with
createNewSecretChat user_id:int = Chat;

//@description Creates new channel supergroup chat from existing group chat and send corresponding messageChatMigrateTo and messageChatMigrateFrom. Deactivates group @chat_id Group chat identifier
migrateGroupChatToChannelChat chat_id:int53 = Chat;


//@description Changes chat title. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Title will not change before request to the server completes
//@chat_id Chat identifier @title New title of the chat, 1-255 characters
changeChatTitle chat_id:int53 title:string = Ok;

//@description Changes chat photo. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Photo will not change before request to the server completes
//@chat_id Chat identifier @photo New chat photo. You can use zero InputFileId to delete chat photo. Files accessible only by HTTP URL are not acceptable
changeChatPhoto chat_id:int53 photo:InputFile = Ok;

//@description Changes chat draft message @chat_id Chat identifier @draft_message New draft message, nullable
changeChatDraftMessage chat_id:int53 draft_message:draftMessage = Ok;

//@description Changes chat pinned state. You can pin up to getOption("pinned_chat_count_max") non-secret chats and the same number of secret chats @chat_id Chat identifier @is_pinned New value of is_pinned
toggleChatIsPinned chat_id:int53 is_pinned:Bool = Ok;

//@description Changes client data associated with a chat @chat_id Chat identifier @client_data New value of client_data
setChatClientData chat_id:int53 client_data:string = Ok;

//@description Adds new member to chat. Members can't be added to private or secret chats. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server
//@chat_id Chat identifier @user_id Identifier of the user to add @forward_limit Number of previous messages from chat to forward to new member, ignored for channel chats
addChatMember chat_id:int53 user_id:int forward_limit:int = Ok;

//@description Adds many new members to the chat. Currently, available only for channels. Can't be used to join the channel. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server
//@chat_id Chat identifier @user_ids Identifiers of the users to add
addChatMembers chat_id:int53 user_ids:vector<int> = Ok;

//@description Changes status of the chat member, need appropriate privileges. This function is currently not suitable for adding new members to the chat, use addChatMember instead. Status will not be changed until chat state will be synchronized with the server. Status will not be changed if application is killed before it can send request to the server
//@chat_id Chat identifier @user_id Identifier of the user to edit status @status New status of the member in the chat
changeChatMemberStatus chat_id:int53 user_id:int status:ChatMemberStatus = Ok;

//@description Returns information about one participant of the chat @chat_id Chat identifier @user_id User identifier
getChatMember chat_id:int53 user_id:int = ChatMember;

//@description Searches for the specified query in the first name, last name and username among members of the specified chat. Requires administrator rights in broadcast channels @chat_id Chat identifier @query Query to search for @limit Maximum number of users to be returned
searchChatMembers chat_id:int53 query:string limit:int = ChatMembers;


//@description Changes list or order of pinned chats @chat_ids New list of pinned chats
setPinnedChats chat_ids:vector<int53> = Ok;


//@description Asynchronously downloads file from cloud. Updates updateFile will notify about download progress and successful download @file_id Identifier of file to download
//@priority Priority of download, 1-32. The higher priority, the earlier file will be downloaded. If priorities of two files are equal then the last one for which downloadFile is called will be downloaded first
downloadFile file_id:int priority:int = Ok;

//@description Stops file downloading. If file is already downloaded, does nothing @file_id Identifier of file to cancel download
cancelDownloadFile file_id:int = Ok;

//@description Asynchronously uploads file to the cloud without sending it in a message. Updates updateFile will notify about upload progress and successful upload. The file will not have persistent identifier until it will be sent in a message @file File to upload @file_type File type
//@priority Priority of upload, 1-32. The higher priority, the earlier file will be uploaded. If priorities of two files are equal then the first one for which uploadFile is called will be uploaded first
uploadFile file:InputFile file_type:FileType priority:int = File;

//@description Stops file uploading. Works only for files uploaded using uploadFile. For other files the behavior is undefined @file_id Identifier of file to cancel upload
cancelUploadFile file_id:int = Ok;

//@description Next part of a file was generated
//@generation_id Identifier of the generation process
//@size Full size of file in bytes, 0 if unknown.
//@local_size Number of bytes already generated. Negative number means that generation has failed and should be terminated
setFileGenerationProgress generation_id:long size:int local_size:int = Ok;

//@description Finishes file generation @generation_id Identifier of the generation process
finishFileGeneration generation_id:long = Ok;

//@description Deletes a file from TDLib file cache @file_id Identifier of the file to delete
deleteFile file_id:int = Ok;


//@description Generates new chat invite link, previously generated link is revoked. Available for group and channel chats. In groups can be called only by creator, in channels requires appropriate rights @chat_id Chat identifier
exportChatInviteLink chat_id:int53 = ChatInviteLink;

//@description Checks chat invite link for validness and returns information about the corresponding chat @invite_link Invite link to check. Should begin with "https://t.me/joinchat/", "https://telegram.me/joinchat/" or "https://telegram.dog/joinchat/"
checkChatInviteLink invite_link:string = ChatInviteLinkInfo;

//@description Imports chat invite link, adds current user to a chat if possible. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server
//@invite_link Invite link to import. Should begin with "https://t.me/joinchat/", "https://telegram.me/joinchat/" or "https://telegram.dog/joinchat/"
importChatInviteLink invite_link:string = Ok;


//@description Creates new call @user_id Identifier of user to call @protocol Description of supported by the client call protocols
createCall user_id:int protocol:callProtocol = CallId;

//@description Accepts incoming call @call_id Call identifier @protocol Description of supported by the client call protocols
acceptCall call_id:int protocol:callProtocol = Ok;

//@description Discards a call @call_id Call identifier @is_disconnected True, if users was disconnected @duration Call duration in seconds @connection_id Identifier of a connection used during the call
discardCall call_id:int is_disconnected:Bool duration:int connection_id:long = Ok;

//@description Sends call rating @call_id Call identifier @rating Call rating, 1-5 @comment Optional user comment if rating is less than 5
rateCall call_id:int rating:int comment:string = Ok;

//@description Sends call debug information @call_id Call identifier @debug Debug information in application specific format
debugCall call_id:int debug:string = Ok;



//@description Adds user to black list @user_id User identifier
blockUser user_id:int = Ok;

//@description Removes user from black list @user_id User identifier
unblockUser user_id:int = Ok;

//@description Returns users blocked by the current user @offset Number of users to skip in result, must be non-negative @limit Maximum number of users to return, can't be greater than 100
getBlockedUsers offset:int limit:int = Users;


//@description Adds new contacts/edits existing contacts, contacts user identifiers are ignored @contacts List of contacts to import/edit
importContacts contacts:vector<contact> = ImportedContacts;

//@description Searches for specified query in the first name, last name and username of the known user contacts @query Query to search for, can be empty to return all contacts @limit Maximum number of users to be returned
searchContacts query:string limit:int = Users;

//@description Deletes users from contacts list @user_ids Identifiers of users to be deleted
deleteContacts user_ids:vector<int> = Ok;


//@description Returns profile photos of the user. Result of this query may be outdated: some photos may be already deleted @user_id User identifier @offset Photos to skip, must be non-negative @limit Maximum number of photos to be returned, can't be greater than 100
getUserProfilePhotos user_id:int offset:int limit:int = UserProfilePhotos;


//@description Returns stickers from installed ordinary sticker sets corresponding to the given emoji @emoji String representation of emoji. If empty, returns all known stickers @limit Maximum number of stickers to return
getStickers emoji:string limit:int = Stickers;

//@description Returns list of installed sticker sets @is_masks Pass true to return mask sticker sets, pass false to return ordinary sticker sets
getInstalledStickerSets is_masks:Bool = StickerSets;

//@description Returns list of archived sticker sets @is_masks Pass true to return mask stickers sets, pass false to return ordinary sticker sets @offset_sticker_set_id Identifier of the sticker set from which return the result @limit Maximum number of sticker sets to return
getArchivedStickerSets is_masks:Bool offset_sticker_set_id:long limit:int = StickerSets;

//@description Returns list of trending sticker sets
getTrendingStickerSets = StickerSets;

//@description Returns list of sticker sets attached to a file, currently only photos and videos can have attached sticker sets @file_id File identifier
getAttachedStickerSets file_id:int = StickerSets;

//@description Returns information about sticker set by its identifier @set_id Identifier of the sticker set
getStickerSet set_id:long = StickerSet;

//@description Searches sticker set by its short name @name Name of the sticker set
searchStickerSet name:string = StickerSet;

//@description Installs/uninstalls or enables/archives sticker set @set_id Identifier of the sticker set @is_installed New value of is_installed @is_archived New value of is_archived. A sticker set can't be installed and archived simultaneously
changeStickerSet set_id:long is_installed:Bool is_archived:Bool = Ok;

//@description Informs that some trending sticker sets are viewed by the user @sticker_set_ids Identifiers of viewed trending sticker sets
viewTrendingStickerSets sticker_set_ids:vector<long> = Ok;

//@description Changes the order of installed sticker sets @is_masks Pass true to change mask sticker sets order, pass false to change ordinary sticker sets order @sticker_set_ids Identifiers of installed sticker sets in the new right order
reorderInstalledStickerSets is_masks:Bool sticker_set_ids:vector<long> = Ok;

//@description Returns list of recently used stickers @is_attached Pass true to return stickers and masks recently attached to photo or video files, pass false to return recently sent stickers
getRecentStickers is_attached:Bool = Stickers;

//@description Manually adds new sticker to the list of recently used stickers. New sticker is added to the beginning of the list. If the sticker is already in the list, at first it is removed from the list
//@is_attached Pass true to add the sticker to the list of stickers recently attached to photo or video files, pass false to add the sticker to the list of recently sent stickers @sticker Sticker file to add
addRecentSticker is_attached:Bool sticker:InputFile = Stickers;

//@description Removes a sticker from the list of recently used stickers @is_attached Pass true to remove the sticker from the list of stickers recently attached to photo or video files, pass false to remove the sticker from the list of recently sent stickers @sticker Sticker file to delete
deleteRecentSticker is_attached:Bool sticker:InputFile = Ok;

//@description Clears list of recently used stickers @is_attached Pass true to clear list of stickers recently attached to photo or video files, pass false to clear the list of recently sent stickers
clearRecentStickers is_attached:Bool = Ok;

//@description Returns emojis corresponding to a sticker @sticker Sticker file identifier
getStickerEmojis sticker:InputFile = StickerEmojis;


//@description Returns saved animations
getSavedAnimations = Animations;

//@description Manually adds new animation to the list of saved animations. New animation is added to the beginning of the list. If the animation is already in the list, at first it is removed from the list. Only non-secret video animations with MIME type "video/mp4" can be added to the list
//@animation Animation file to add. Only known to server animations (i. e. successfully sent via message) can be added to the list
addSavedAnimation animation:InputFile = Ok;

//@description Removes animation from the list of saved animations @animation Animation file to delete
deleteSavedAnimation animation:InputFile = Ok;


//@description Returns up to 20 recently used inline bots in the order of the last usage
getRecentInlineBots = Users;


//@description Searches for recently used hashtags by their prefix @prefix Hashtag prefix to search for @limit Maximum number of hashtags to return
searchHashtags prefix:string limit:int = Hashtags;

//@description Deletes a hashtag from the list of recently used hashtags @hashtag The hashtag to delete
deleteRecentHashtag hashtag:string = Ok;


//@description Returns web page preview by text of the message. Do not call this function to often. Returns error 404 if web page has no preview @message_text Message text
getWebPagePreview message_text:string = WebPage;

//@description Returns web page instant view if available. Returns error 404 if web page has no instant view @url Web page URL @force_full If true, full web page instant view will be returned
getWebPageInstantView url:string force_full:Bool = WebPageInstantView;


//@description Returns notification settings for a given scope @scope Scope to return information about notification settings
getNotificationSettings scope:NotificationSettingsScope = NotificationSettings;

//@description Changes notification settings for a given scope @scope Scope to change notification settings
//@notification_settings New notification settings for given scope
setNotificationSettings scope:NotificationSettingsScope notification_settings:notificationSettings = Ok;

//@description Resets all notification settings to the default value. By default the only muted chats are supergroups, sound is set to 'default' and message previews are showed
resetAllNotificationSettings = Ok;


//@description Uploads new profile photo for logged in user. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. If something changes, updateUser will be sent @photo Profile photo to set. inputFileId and inputFilePersistentId may be unsupported
setProfilePhoto photo:InputFile = Ok;

//@description Deletes profile photo. If something changes, updateUser will be sent @profile_photo_id Identifier of profile photo to delete
deleteProfilePhoto profile_photo_id:long = Ok;

//@description Changes first and last names of logged in user. If something changes, updateUser will be sent @first_name New value of user first name, 1-255 characters @last_name New value of optional user last name, 0-255 characters
changeName first_name:string last_name:string = Ok;

//@description Changes about information of logged in user @about New value of userFull.about, 0-70 characters without line feeds
changeAbout about:string = Ok;

//@description Changes username of logged in user. If something changes, updateUser will be sent @username New value of username. Use empty string to remove username
changeUsername username:string = Ok;

//@description Changes user's phone number and sends authentication code to the new user's phone number. Returns authStateWaitCode with information about sent code on success
//@phone_number New user's phone number in any reasonable format @allow_flash_call Pass True, if code can be sent via flash call to the specified phone number @is_current_phone_number Pass true, if the phone number is used on the current device. Ignored if allow_flash_call is False
changePhoneNumber phone_number:string allow_flash_call:Bool is_current_phone_number:Bool = AuthState;

//@description Resends authentication code sent to change user's phone number. Wotks only if in previously received authStateWaitCode next_code_type was not null. Returns authStateWaitCode on success
resendChangePhoneNumberCode = AuthState;

//@description Checks authentication code sent to change user's phone number. Returns authStateOk on success @code Verification code from SMS, phone call or flash call
checkChangePhoneNumberCode code:string = AuthState;


//@description Returns all active sessions of logged in user
getActiveSessions = Sessions;

//@description Terminates another session of logged in user @session_id Session identifier
terminateSession session_id:long = Ok;

//@description Terminates all other sessions of logged in user
terminateAllOtherSessions = Ok;


//@description Gives or revokes all members of the group administrator rights. Needs creator privileges in the group @group_id Identifier of the group @everyone_is_administrator New value of everyone_is_administrator
toggleGroupAdministrators group_id:int everyone_is_administrator:Bool = Ok;


//@description Changes username of the channel. Needs creator privileges in the channel @channel_id Identifier of the channel @username New value of username. Use empty string to remove username
changeChannelUsername channel_id:int username:string = Ok;

//@description Gives or revokes right to invite new members to all current members of the channel. Needs appropriate rights in the channel. Available only for supergroups @channel_id Identifier of the channel @anyone_can_invite New value of anyone_can_invite
toggleChannelInvites channel_id:int anyone_can_invite:Bool = Ok;

//@description Enables or disables sender signature on sent messages in the channel. Needs appropriate rights in the channel. Not available for supergroups @channel_id Identifier of the channel @sign_messages New value of sign_messages
toggleChannelSignMessages channel_id:int sign_messages:Bool = Ok;

//@description Changes information about the channel. Needs appropriate rights in the channel @channel_id Identifier of the channel @param_description New channel description, 0-255 characters
changeChannelDescription channel_id:int description:string = Ok;

//@description Pins a message in a supergroup channel chat. Needs appropriate rights in the channel @channel_id Identifier of the channel @message_id Identifier of the new pinned message @disable_notification True, if there should be no notification about the pinned message
pinChannelMessage channel_id:int message_id:int53 disable_notification:Bool = Ok;

//@description Removes pinned message in the supergroup channel. Needs appropriate rights in the channel @channel_id Identifier of the channel
unpinChannelMessage channel_id:int = Ok;

//@description Reports some supergroup channel messages from a user as spam messages @channel_id Channel identifier @user_id User identifier @message_ids Identifiers of messages sent in the supergroup by the user, the list should be non-empty
reportChannelSpam channel_id:int user_id:int message_ids:vector<int53> = Ok;

//@description Returns information about channel members or banned users. Can be used only if channel_full->can_get_members == true. Administrator privileges may be additionally needed for some filters @channel_id Identifier of the channel
//@filter Kind of channel users to return, defaults to channelMembersRecent @offset Number of channel users to skip @limit Maximum number of users be returned, can't be greater than 200
getChannelMembers channel_id:int filter:ChannelMembersFilter offset:int limit:int = ChatMembers;

//@description Deletes channel along with all messages in corresponding chat. Releases channel username and removes all members. Needs creator privileges in the channel. Channels with more than 1000 members can't be deleted @channel_id Identifier of the channel
deleteChannel channel_id:int = Ok;


//@description Closes secret chat @secret_chat_id Secret chat identifier
closeSecretChat secret_chat_id:int = Ok;


//@description Returns list of service actions taken by chat members and administrators in the last 48 hours, available only in channels. Requires administrator rights. Returns result in reverse chronological order, i. e. in order of decreasing event_id
//@chat_id Chat identifier @query Search query to filter events @from_event_id Identifier of an event from which to return result, you can use 0 to get results from the latest events @limit Maximum number of events to return, can't be greater than 100
//@filters Types of events to return, defaults to all @user_ids User identifiers, which events to return, defaults to all users
getChatEventLog chat_id:int53 query:string from_event_id:long limit:int filters:chatEventLogFilters user_ids:vector<int> = ChatEvents;


//@description Returns invoice payment form. The method should be called when user presses inlineKeyboardButtonBuy @chat_id Chat identifier of the Invoice message @message_id Message identifier
getPaymentForm chat_id:int53 message_id:int53 = PaymentForm;

//@description Validates order information provided by the user and returns available shipping options for flexible invoice @chat_id Chat identifier of the Invoice message @message_id Message identifier @order_info Order information, provided by the user @allow_save True, if order information can be saved
validateOrderInfo chat_id:int53 message_id:int53 order_info:orderInfo allow_save:Bool = ValidatedOrderInfo;

//@description Sends filled payment form to the bot for the final verification @chat_id Chat identifier of the Invoice message @message_id Message identifier @order_info_id Identifier returned by ValidateOrderInfo or empty string @shipping_option_id Identifier of a chosen shipping option, if applicable
//@credentials Credentials choosed by user for payment
sendPaymentForm chat_id:int53 message_id:int53 order_info_id:string shipping_option_id:string credentials:InputCredentials = PaymentResult;

//@description Returns information about successful payment @chat_id Chat identifier of the PaymentSuccessful message @message_id Message identifier
getPaymentReceipt chat_id:int53 message_id:int53 = PaymentReceipt;

//@description Returns saved order info if any
getSavedOrderInfo = OrderInfo;

//@description Deletes saved order info
deleteSavedOrderInfo = Ok;

//@description Deletes saved credentials for all payments provider bots
deleteSavedCredentials = Ok;


//@description Returns user that can be contacted to get support
getSupportUser = User;

//@description Returns background wallpapers
getWallpapers = Wallpapers;


//@description Registers current used device for receiving push notifications @device_token Device token
registerDevice device_token:DeviceToken = Ok;


//@description Changes privacy settings @key Privacy key @rules New privacy rules
setPrivacy key:PrivacyKey rules:privacyRules = Ok;

//@description Returns current privacy settings @key Privacy key
getPrivacy key:PrivacyKey = PrivacyRules;


//@description Returns value of an option by its name. See list of available options on https://core.telegram.org/tdlib/options. Can be called before authorization
//@name Name of the option
getOption name:string = OptionValue;

//@description Sets value of an option. See list of available options on https://core.telegram.org/tdlib/options. Only writable options can be set. Can be called before authorization
//@name Name of the option @value New value of the option
setOption name:string value:OptionValue = Ok;


//@description Changes period of inactivity, after which the account of currently logged in user will be automatically deleted @ttl New account TTL
changeAccountTtl ttl:accountTtl = Ok;

//@description Returns period of inactivity, after which the account of currently logged in user will be automatically deleted
getAccountTtl = AccountTtl;

//@description Deletes the account of currently logged in user, deleting from the server all information associated with it. Account's phone number can be used to create new account, but only once in two weeks @reason Optional reason of account deletion
deleteAccount reason:string = Ok;


//@description Returns current chat report spam state @chat_id Chat identifier
getChatReportSpamState chat_id:int53 = ChatReportSpamState;

//@description Reports chat as a spam chat or as not a spam chat. Can be used only if ChatReportSpamState.can_report_spam is true. After this request ChatReportSpamState.can_report_spam became false forever @chat_id Chat identifier @is_spam_chat If true, chat will be reported as a spam chat, otherwise it will be marked as not a spam chat
changeChatReportSpamState chat_id:int53 is_spam_chat:Bool = Ok;

//@description Reports chat to Telegram moderators. Can be used only for a channel chat or a private chat with a bot, because all other chats can't be checked by moderators @chat_id Chat identifier @reason Reason, the chat is reported
reportChat chat_id:int53 reason:ChatReportReason = Ok;


//@description Returns storage usage statistics @chat_limit Maximum number of chats with biggest storage usage for which separate statistics should be returned. All other chats will be grouped in entries with chat_id == 0. If chat info database is not used, chat_limit is ignored and is always set to 0
getStorageStatistics chat_limit:int = StorageStatistics;

//@description Quickly returns approximate storage usage statistics
getStorageStatisticsFast = StorageStatisticsFast;

//@description Optimizes storage usage, i.e. deletes some files and return new storage usage statistics. Secret thumbnails can't be deleted
//@size Limit on total size of files after deletion. Pass -1 to use default limit
//@ttl Limit on time passed since last access time (or creation time on some filesystems) to a file. Pass -1 to use default limit
//@count Limit on total count of files after deletion. Pass -1 to use default limit
//@immunity_delay Number of seconds after creation of a file, it can't be delited. Pass -1 to use default value
//@file_types If not empty, only files with given types are considered. By default, all types except thumbnails, profile photos, stickers and wallpapers are deleted
//@chat_ids If not empty, only files from the given chats are considered. Use 0 as chat identifier to delete files not belonging to any chat, for example profile photos
//@exclude_chat_ids If not empty, files from the given chats are exluded. Use 0 as chat identifier to exclude all files not belonging to any chat, for example profile photos
//@chat_limit Same as in getStorageStatistics. Affects only returned statistics
optimizeStorage size:int53 ttl:int count:int immunity_delay:int file_types:vector<FileType> chat_ids:vector<int53> exclude_chat_ids:vector<int53> chat_limit:int = StorageStatistics;


//@description Sets current network type. Can be called before authorization. Call to this method forces reopening of all network connections mitigating delay in switching between different networks, so it should be called whenever network is changed even network type remains the same. Network type is used to check if library can use network at all and for collecting detailed network data usage statistics @type New network type, defaults to networkTypeNone
setNetworkType type:NetworkType = Ok;

//@description Returns network data usage statistics. Can be called before authorization @only_current If true, returns only data for the current library launch
getNetworkStatistics only_current:Bool = NetworkStatistics;

//@description Adds specified data to data usage statistics. Can be called before authorization @entry Network statistics entry with a data to add to statistics
addNetworkStatistics entry:NetworkStatisticsEntry = Ok;

//@description Resets all network data usage statistics to zero. Can be called before authorization
resetNetworkStatistics = Ok;


//@description Bots only. Informs server about number of pending bot updates if they aren't processed for a long time @pending_update_count Number of pending updates @error_message Last error's message
setBotUpdatesStatus pending_update_count:int error_message:string = Ok;


//@description Bots only. Uploads a png image with a sticker. Returns uploaded file @user_id Sticker file owner @png_sticker Png image with the sticker, must be up to 512 kilobytes in size and fit in 512x512 square
uploadStickerFile user_id:int png_sticker:InputFile = File;

//@description Bots only. Creates new sticker set. Returns created sticker set @user_id Sticker set owner @title Sticker set title, 1-64 characters @name Sticker set name. Can contain only english letters, digits and underscores. Should end on *"_by_<bot username>"*. *<bot_username>* is case insensitive, 1-64 characters
//@is_masks True, is stickers are masks @stickers List of stickers to add to the set
createNewStickerSet user_id:int title:string name:string is_masks:Bool stickers:vector<inputSticker> = StickerSet;

//@description Bots only. Adds new sticker to a set. Returns the sticker set @user_id Sticker set owner @name Sticker set name @sticker Sticker to add to the set
addStickerToSet user_id:int name:string sticker:inputSticker = StickerSet;

//@description Bots only. Changes position of a sticker in the set it belongs to. Sticker set should be created by the bot @sticker The sticker @position New sticker position in the set, zero-based
setStickerPositionInSet sticker:InputFile position:int = Ok;

//@description Bots only. Deletes a sticker from the set it belongs to. Sticker set should be created by the bot @sticker The sticker
deleteStickerFromSet sticker:InputFile = Ok;


//@description Bots only. Sends custom request @method Method name @parameters JSON-serialized method parameters
sendCustomRequest method:string parameters:string = CustomRequestResult;

//@description Bots only. Answers a custom query @custom_query_id Identifier of a custom query @data JSON-serialized answer to the query
answerCustomQuery custom_query_id:long data:string = Ok;


//@description Returns Ok after specified amount of the time passed. Can be called before authorization @seconds Number of seconds before that function returns
setAlarm seconds:double = Ok;


//@description Returns invite text for invitation of new users
getInviteText = Text;

//@description Returns terms of service. Can be called before authorization
getTermsOfService = Text;


//@description Sets proxy server for network requests. Can be called before authorization @proxy The proxy to use. You can specify null to remove proxy server
setProxy proxy:Proxy = Ok;

//@description Returns current set up proxy. Can be called before authorization
getProxy = Proxy;


//@description Test request. Does nothing
testCallEmpty = Ok;
//@description Test request. Returns back received string @x String to return
testCallString x:string = TestString;
//@description Test request. Returns back received bytes @x Bytes to return
testCallBytes x:bytes = TestBytes;
//@description Test request. Returns back received vector of numbers @x Vector of numbers to return
testCallVectorInt x:vector<int> = TestVectorInt;
//@description Test request. Returns back received vector of objects containing a number @x Vector of objects to return
testCallVectorIntObject x:vector<testInt> = TestVectorIntObject;
//@description Test request. Returns back received vector of strings @x Vector of strings to return
testCallVectorString x:vector<string> = TestVectorString;
//@description Test request. Returns back received vector of objects containing a string @x Vector of objects to return
testCallVectorStringObject x:vector<testString> = TestVectorStringObject;
//@description Test request. Returns squared received number @x Number to square
testSquareInt x:int = TestInt;
//@description Test request. Sends simple network request to telegram servers
testNetwork = Ok;
//@description Test request. Forces updates.getDifference call to telegram servers
testGetDifference = Ok;
//@description Test request. Does nothing, ensures that the Update object is used
testUseUpdate = Update;
//@description Test request. Does nothing, ensures that the Error object is used
testUseError = Error;
