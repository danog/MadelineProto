

class User():
  me = None
  ''' current connected user '''

  friends = []
  ''' current connected user's friends '''

  def __init__(self, uid):
    self.uid = uid
