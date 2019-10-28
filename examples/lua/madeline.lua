function madeline_update_callback (data)
  var_dump(data)
  if (data._ == "updateNewMessage" and data.message._ == "message" and data.message.to_id._ == "peerUser" and data.message.to_id.user_id == get_self().id) then -- if it's a new (non channel/supergroup) message, if it's sent to a private chat with me
    if (data.message.message == "ping") then
      messages.sendMessage({peer=data.message.from_id, message="pong", reply_to_msg_id=data.message.id})
    elseif (data.message.message == "PING") then
      messages.sendMessage({peer=data.message.from_id, message="**PONG**", parse_mode="markdown", reply_to_msg_id=data.message.id})
    end     
  end
end
