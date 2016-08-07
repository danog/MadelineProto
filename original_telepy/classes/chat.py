
class Chat():
  def __init__(self):
    self._users = [] # users in this chatroom
  def add_user(self, user):
    self._users += user