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

    protected static $langPair2MTpayableRates = [

            "en-US" => [
                    "nl-NL" => [
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
                            'MT'          => 80
                    ],
                    "fr-FR" => [
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
                            'MT'          => 80
                    ],
                    "de-DE" => [
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
                    ],
                    "it-IT" => [
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
                            'MT'          => 80
                    ],
                    "ja-JP" => [
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
                            'MT'          => 90
                    ],
                    "ko-KR" => [
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
                            'MT'          => 90
                    ],
                    "pt-BR" => [
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
                            'MT'          => 80
                    ],
                    "ru-RU" => [
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
                            'MT'          => 90
                    ],
                    "es-ES" => [
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
                            'MT'          => 80
                    ],
                    "zh-CN" => [
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
                            'MT'          => 90
                    ],
                    "zh-TW" => [
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
                            'MT'          => 90
                    ],
                    "sv-SE" => [
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
                    ]
            ]
    ];

    public static function getPayableRates( $source, $target ) {
        self::$langPair2MTpayableRates[ 'en-GB' ] = self::$langPair2MTpayableRates[ 'en-US' ];

        return parent::getPayableRates( $source, $target );
    }


}