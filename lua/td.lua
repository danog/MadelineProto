
chats = {}
function dl_cb (arg, data) var_dump(arg)
  var_dump(data)
end

function tdcli_update_callback (data)
  var_dump(data)
  if (data.ID == "UpdateNewMessage") then
    local msg = data.message_
    local d = data.disable_notification_
    local chat = chats[msg.chat_id_]
    if ((not d) and chat) then
      if msg.content_.ID == "MessageText" then
        var_dump (chat.title_, msg.content_.text_)
      else
        var_dump (chat.title_, msg.content_.ID)
      end
    end
    if msg.content_.ID == "MessageText" then
      if msg.content_.text_ == "ping" then
        tdcli_function ({ID="SendMessage", chat_id_=msg.chat_id_, reply_to_message_id_=msg.id_, disable_notification_=0, from_background_=1, reply_markup_=nil, input_message_content_={ID="InputMessageText", text_="pong", disable_web_page_preview_=1, clear_draft_=0, entities_={}}})
      elseif msg.content_.text_ == "PING" then
        tdcli_function ({ID="SendMessage", chat_id_=msg.chat_id_, reply_to_message_id_=msg.id_, disable_notification_=0, from_background_=1, reply_markup_=nil, input_message_content_={ID="InputMessageText", text_="pong", disable_web_page_preview_=1, clear_draft_=0, entities_={[0]={ID="MessageEntityBold", offset_=0, length_=4}}}})
      end
    end
  elseif (data.ID == "UpdateChat") then
    chat = data.chat_
    chats[chat.id_] = chat
  elseif (data.ID == "UpdateOption" and data.name_ == "my_id") then
    tdcli_function ({ID="GetChats", offset_order_="9223372036854775807", offset_chat_id_=0, limit_=20}, nil, nil)
  end
end
