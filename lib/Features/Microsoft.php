<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 09/04/2018
 * Time: 14:57
 */

namespace Features;


use API\V2\Exceptions\AuthenticationError;
use Features;
use Klein\Klein;

class Microsoft extends BaseFeature {

    const FEATURE_CODE = "microsoft";

    public static function loadRoutes( Klein $klein ) {


    }

    public function beginDoAction(){
        //sleep(1);
    }
}