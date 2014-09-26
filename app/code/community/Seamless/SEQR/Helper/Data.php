<?php

class Seamless_SEQR_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Utility function used for converting money values to format used by SEQR.
     *
     * NB: SEQR requires to use not more than two digits before the decimal point. Using more than two digits before
     * decimal point can cause error with code 49.
     *
     * @param number $number Money value
     * @return string Money value in right format.
     */
    public function toFloat($number) {

        return number_format((float) $number, 2, '.', '');
    }
}