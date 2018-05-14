<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 09/04/2018
 * Time: 14:57
 */

namespace Features;

use API\V2\Json\ProjectUrls;
use Features\Microsoft\Utils\Email\ConfirmedQuotationEmail;
use Features\Microsoft\Utils\Email\ErrorQuotationEmail;
use Features\Microsoft\View\API\JSON\MicrosoftUrlsDecorator;
use Features\Microsoft\Model\Analysis\CustomPayableRates;
use Klein\Klein;
use Features;
use \Features\Outsource\Traits\Translated as TranslatedTrait;

class Microsoft extends BaseFeature {

    use TranslatedTrait;

    const FEATURE_CODE = "microsoft";

    public static $dependencies = [
            Features::PROJECT_COMPLETION,
            Features::TRANSLATION_VERSIONS,
            Features::REVIEW_IMPROVED,
            Features::QACHECK_GLOSSARY
    ];

    public static function loadRoutes( Klein $klein ) {
        //route( '/job/[:id_job]/[:password]/sign_off', 'GET', 'Features\Microsoft\Controller\SignOffController', 'signedOffCallback' );
    }

    public static function projectUrls( ProjectUrls $formatted ) {
        $projectUrlsDecorator = new MicrosoftUrlsDecorator( $formatted->getData());

        return $projectUrlsDecorator;
    }

    public function filterProjectCompletionDisplayButton($displayButton, Features\ProjectCompletion\Decorator\CatDecorator $decorator) {
        if ( $decorator->getController()->isRevision() ) {
            return false;
        }
        return $displayButton ;
    }

    public function afterTMAnalysisCloseProject( $project_id ) {
        $this->setSuccessMailSender( new ConfirmedQuotationEmail( self::getPluginBasePath() . '/Features/Microsoft/View/Emails/confirmed_quotation.html' ) );
        $this->setFailureMailSender( new ErrorQuotationEmail( self::getPluginBasePath() . '/Features/Microsoft/View/Emails/error_quotation.html' ) );
        $this->requestQuote( $project_id );
    }

    /**
     * Send the alt-trans to MyMemory
     *
     * @param $boolean
     *
     * @return bool
     */
    public function doNotManageAlternativeTranslations( $boolean ){
        return false;
    }

    /**
     *
     * Payable Rates customization hook
     *
     * @param $payableRates
     * @param $SourceLang
     * @param $TargetLang
     *
     * @return array
     */
    public function filterPayableRates( $payableRates, $SourceLang, $TargetLang ){
        return CustomPayableRates::getPayableRates( $SourceLang, $TargetLang );
    }

    /**
     * Entry point for project data validation for this feature.
     *
     * @param $projectStructure
     */
    public function validateProjectCreation( $projectStructure )  {
        //override Revise Improved qa Model
        $qa_mode_file = realpath( self::getPluginBasePath() . "/../qa_model.json" );
        ReviewImproved::loadAndValidateModelFromJsonFile( $projectStructure, $qa_mode_file );
    }

    public function glossaryMatchPattern($default_pattern) {
        return $default_pattern."i"; // regex with case insensitive only for microsoft
    }

}