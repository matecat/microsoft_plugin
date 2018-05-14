<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 14/05/2018
 * Time: 15:10
 */

namespace Features\Microsoft\Model\Analysis;


class CustomPayableRates extends \Analysis_PayableRates {

    public static $DEFAULT_PAYABLE_RATES = [
            'NO_MATCH'    => 100,
            '50%-74%'     => 100,
            '75%-84%'     => 60,
            '85%-94%'     => 60,
            '95%-99%'     => 30,
            '100%'        => 25,
            '100%_PUBLIC' => 25,
            'REPETITIONS' => 25,
            'INTERNAL'    => 60,
            'ICE'         => 0,
            'MT'          => 85
    ];

}