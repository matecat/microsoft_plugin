<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 18/02/2019
 * Time: 16:10
 */

namespace Features\Microsoft\Utils;


class Metadata {

    const PROJECT_TYPE_METADATA_KEY = 'project_type' ;

    public static $keys = array(
            self::PROJECT_TYPE_METADATA_KEY
    );

    const TRANSLATE_TYPE = 'Translate' ;
    const REVIEW_TYPE    = 'Review' ;

    public static $valid_project_types = [
            self::TRANSLATE_TYPE,
            self::REVIEW_TYPE
    ];

    /**
     * This function is to be used to filter both postInput from UI and
     * JSON string received from APIs.
     *
     * @return array
     */
    public static function getInputFilter() {
        return array(
               self::PROJECT_TYPE_METADATA_KEY => array(
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                )
        );
    }
}
