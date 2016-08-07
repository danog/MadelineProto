from sys import platform
import os
from subprocess import call
from os.path import exists

class File():
  def __init__(self, path):
    self._path = path

  def write_bytes(self, bytes):
    '''  truncates the file and create new with :param bytes.
     :return number of bytes written'''
    with open(self._path, 'w+b') as file:
      return file.write(bytes)

  def read_bytes(self):
    ''' read the file as bytes. :return b'' on file not exist '''
    if not exists(self._path): return b''
    # buf = b''
    with open(self._path, 'r+b') as file:
      return file.read()
    # return buf

  def open(self):
    '''tries to open with os default viewer'''
    call(('cmd /c start "" "'+ self._path +'"')if os.name is 'nt' else ('open' if platform.startswith('darwin') else 'xdg-open', self._path))

  def remove(self):
    ''' try to remove the file '''
    try:
      os.remove(self._path)
    except FileNotFoundError: pass