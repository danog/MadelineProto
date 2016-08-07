
class Telepy():
  def __init__(self):
    # Deal with py2 and py3 differences
    try: # this only works in py2.7
      import configparser
    except ImportError:
      import ConfigParser as configparser
    import mtproto

    self._config = configparser.ConfigParser()
    # Check if credentials is correctly loaded (when it doesn't read anything it returns [])
    if not self._config.read('credentials'):
      print("File 'credentials' seems to not exist.")
      exit(-1)
    ip = self._config.get('App data', 'ip_address')
    port = self._config.getint('App data', 'port')

    self._session = mtproto.Session(ip, port)
    self._session.create_auth_key()

    self._salt = future_salts = self._session.method_call('get_future_salts', num=3)
