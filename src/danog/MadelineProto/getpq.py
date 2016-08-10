#!/usr/bin/env python
from __future__ import print_function
import prime
import sys
import json

def eprint(*args, **kwargs):
    print(*args, file=sys.stderr, **kwargs)


pq = prime.primefactors(long(sys.argv[1]))

sys.stdout.write(json.dumps(pq))
sys.stdout.flush()
