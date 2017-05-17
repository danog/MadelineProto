<?php

if (!extension_loaded('pthreads')) {
    class Volatile extends Threaded
    {
        public function __set($offset, $value)
        {
            if ($offset === null) {
                $offset = count((array) $this);
            }

            if (is_array($value)) {
                $safety =
                    new self();
                $safety->merge(
                    $this->convertToVolatile($value));
                $value = $safety;
            }

            return $this->{$offset} = $value;
        }
    }
}
