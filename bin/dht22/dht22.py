#!/usr/bin/env python
import sys
try:
    import Adafruit_DHT as dht
except ImportError:
    sys.stdout.write('Adafruit_DHT library not found!')
    sys.exit(1)

h, t = dht.read_retry(dht.DHT22, 4)
sys.stdout.write("%0.1f,%0.1f" % (t, h))
#print 'Temp={0:0.1f}*C Humidity={1:0.1f}%'.format(t,h)

