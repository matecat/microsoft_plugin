<?php

/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 3/21/16
 * Time: 12:05 PM
 */
namespace Features\Microsoft\Utils;

class Routes {


    public static function staticSrc( $file, $options=array() ) {
        $host = \Routes::pluginsBase( $options );
        return $host . "/microsoft/static/src/$file" ;
    }

    public static function staticBuild( $file, $options=array() ) {
        $host = \Routes::pluginsBase( $options );
        return $host . "/microsoft/static/build/$file" ;
    }
}