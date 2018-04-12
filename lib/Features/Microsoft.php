<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 09/04/2018
 * Time: 14:57
 */

namespace Features;

use Features\Microsoft\Utils\Email\ConfirmedQuotationEmail;
use Features\Microsoft\Utils\Email\ErrorQuotationEmail;
use Klein\Klein;
use \Features\Outsource\Traits\Translated;

class Microsoft extends BaseFeature {

    use Translated;

    const FEATURE_CODE = "microsoft";

    public static function loadRoutes( Klein $klein ) {

    }


    public function afterTMAnalysisCloseProject( $project_id ) {
        $this->setSuccessMailSender( new ConfirmedQuotationEmail( self::getPluginBasePath() . '/Features/Microsoft/View/Emails/confirmed_quotation.html' ) );
        $this->setFailureMailSender( new ErrorQuotationEmail( self::getPluginBasePath() . '/Features/Microsoft/View/Emails/error_quotation.html' ) );
        $this->requestQuote( $project_id );
    }
}