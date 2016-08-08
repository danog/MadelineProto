#!/usr/bin/env python
import prime
import sys
pq = prime.primefactors(int(sys.argv[1]))
sys.stdout.write(str(pq[0]) + " " + str(pq[1]))
sys.stdout.flush()
