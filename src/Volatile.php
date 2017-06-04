<?php

if (!extension_loaded('pthreads')) {
    class Volatile extends Threaded
    {
        public function __set($offset, $value)
        {
            if ($offset === null) {
                $offset = count((array) $this);
            }

            return $this->{$offset} = $value;
        }
    }
}
