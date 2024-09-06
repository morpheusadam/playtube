<?php


namespace PayFast;

use Exception;

class PayFastBase
{

    /**
     * @param $name
     * @param $arguments
     * @throws Exception
     */
    public function __call($name, $arguments) {
        if (!method_exists($this, $name)) {
            throw new \RuntimeException('Unknown method '.$name);
        }
    }

}
