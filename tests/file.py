from classes.file import File
from os.path import exists

f = File('text.txt')
assert f.write_bytes(b'testing bytes i/o'), 17
assert f.read_bytes(), b'testing bytes i/o'
f.open() # does it open any text editor on your system?
f.remove()
assert exists('text.txt'), False