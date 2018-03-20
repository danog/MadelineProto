# Avoiding FLOOD_WAITs

If you make too many requests to telegram, you might get FLOOD_WAITed for a while.  
To avoid these flood waits, you must calculate the flood wait rate.  

Calculate it by making N of method calls until you get a FLOOD_WAIT_X   
 
```
floodwaitrate = time it took you to make the method calls + X   
```

Use sleep to execute max N calls in `floodwaitrate` seconds, this way you won't get flood waited!


<button href="https://docs.madelineproto.xyz/docs/LOGGING.html">Next section</button>