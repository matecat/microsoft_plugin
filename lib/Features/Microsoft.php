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
use Features\Microsoft\Utils\Constants\Revise;
use Klein\Klein;
use Features;
use \Features\Outsource\Traits\Translated as TranslatedTrait;

class Microsoft extends BaseFeature {

    use TranslatedTrait;

    const FEATURE_CODE = "microsoft";

    public static $dependencies = [
            Features::PROJECT_COMPLETION,
            Features::TRANSLATION_VERSIONS,
            Features::QACHECK_GLOSSARY
    ];

    public static function loadRoutes( Klein $klein ) {
        //route( '/job/[:id_job]/[:password]/sign_off', 'GET', 'Features\Microsoft\Controller\SignOffController', 'signedOffCallback' );
        route( '/job/[:id_job]/[:password]/hts', 'GET', 'Features\Microsoft\Controller\TranslatedConnectorController', 'sendJob' );
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

    public function afterTMAnalysisCloseProject( $project_id , $_analyzed_report) {
        $this->setSuccessMailSender( new ConfirmedQuotationEmail( self::getPluginBasePath() . '/Features/Microsoft/View/Emails/confirmed_quotation.html' ) );
        $this->setFailureMailSender( new ErrorQuotationEmail( self::getPluginBasePath() . '/Features/Microsoft/View/Emails/error_quotation.html' ) );
        $this->requestProjectQuote( $project_id, $_analyzed_report );
    }

    /**
     * @param $iceLockArray array
     *
     * <code>
     *  [
     *      'approved'         => @$translation_row [ 4 ][ 'attr' ][ 'approved' ],
     *      'locked'           => 0,
     *      'match_type'       => 'ICE',
     *      'eq_word_count'    => 0,
     *      'status'           => $status,
     *      'suggestion_match' => null,
     *      'trans-unit'       => $translation_row[ 4 ],
     *  ]
     * </code>
     *
     * @return array $iceLockArray
     */
    public function setICESLockFromXliffValues( $iceLockArray ) {
        $match_quality = (int)str_replace( "%", "", $iceLockArray[ 'trans-unit' ][ 'alt-trans' ][ 'attr' ][ 'match-quality' ] );

        if ( $match_quality >= 100 && $iceLockArray['trans-unit']['target']['attr']['state'] == "final" ) {
            $iceLockArray[ 'locked' ] = 1;
            $iceLockArray[ 'status' ] = \Constants_TranslationStatus::STATUS_APPROVED;
        }

        return $iceLockArray;

    }

    public function filterDifferentSourceAndTargetIsTranslated( $originalValue, $projectStructure, $xliff_trans_unit ) {
        $match_quality = (int)str_replace( "%", "", $xliff_trans_unit[ 'alt-trans' ][ 'attr' ][ 'match-quality' ] );
        if ( $match_quality >= 100 ) {
            return $originalValue;
        }
        return false;
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

    public function filterSegmentFilter( Features\SegmentFilter\Model\FilterDefinition $filterDefinition, \Chunks_ChunkStruct $chunk ) {
        if ( $filterDefinition->sampleType() == 'regular_intervals' ) {
            $filterDefinition->setCustomCondition("  (st.match_type != 'ICE' or st.locked != 1) ", [] );
        }
    }

    public function overrideReviseJobQA( $jobQA, $id_job, $password_job, $job_words ) {
        return [
                new \Revise_JobQA(
                        $id_job,
                        $password_job,
                        $job_words,
                        new Revise()
                ), new Revise()
        ];
    }

    /**
     * Because of a bug in filters we force languages conversion to it-IT when isCJK
     * @param array $array
     *
     * @return array
     */
    public function overrideConversionRequest( Array $array ){
        if( \CatUtils::isCJK( $array[ 'target' ] ) ){
            $array[ 'target' ] = 'it-IT';
        }
        return $array;
    }

}