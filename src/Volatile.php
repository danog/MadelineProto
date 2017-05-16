<?php

if (!extension_loaded('pthreads')) {
    class Volatile extends Threaded
    {
        public function __set($offset, $value)
        {
            if ($offset === null) {
                $offset = count($this->data);
            }

            if (is_array($value)) {
                $safety =
                    new self();
                $safety->merge(
                    $this->convertToVolatile($value));
                $value = $safety;
            }

            return $this->data[$offset] = $value;
        }
    }
}
