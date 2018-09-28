<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 09/04/2018
 * Time: 14:57
 */

namespace Features;

use Analysis\Workers\TMAnalysisWorker;
use API\V2\Json\ProjectUrls;
use ArrayObject;
use Features\Microsoft\Utils\Email\ConfirmedQuotationEmail;
use Features\Microsoft\Utils\Email\ErrorQuotationEmail;
use Features\Microsoft\View\API\JSON\MicrosoftUrlsDecorator;
use Features\Microsoft\Model\Analysis\CustomPayableRates;
use Features\Microsoft\Utils\Constants\Revise;
use Features\Traits\PhManagementTagTrait;
use Features\Traits\XliffConversionTrait;
use Klein\Klein;
use Features;
use \Features\Outsource\Traits\Translated as TranslatedTrait;

class Microsoft extends BaseFeature {

    use TranslatedTrait, XliffConversionTrait, PhManagementTagTrait;

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
     * @see \ProjectManager::_insertPreTranslations()
     *
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

        foreach ( $iceLockArray[ 'trans-unit' ][ 'alt-trans' ] as $altTrans ) {

            $match_quality = (int)str_replace( "%", "", $altTrans[ 'attr' ][ 'match-quality' ] );

            if ( $match_quality >= 100 && @$iceLockArray[ 'trans-unit' ][ 'target' ][ 'attr' ][ 'state' ] == "final" ) {
                $iceLockArray[ 'locked' ] = 1;
                $iceLockArray[ 'status' ] = \Constants_TranslationStatus::STATUS_APPROVED;
                break;
            }

        }

        return $iceLockArray;

    }

    /**
     * @see \ProjectManager::__isTranslated()
     * @param $originalValue
     * @param $projectStructure
     * @param $xliff_trans_unit
     *
     * @return bool
     */
    public function filterDifferentSourceAndTargetIsTranslated( $originalValue, $projectStructure, $xliff_trans_unit ) {

        $found = false;

        foreach ( $xliff_trans_unit[ 'alt-trans' ] as $altTrans ) {

            $match_quality = (int)str_replace( "%", "", @$altTrans[ 'attr' ][ 'match-quality' ] );

            if ( $match_quality > 100 && $xliff_trans_unit[ 'target' ][ 'attr' ][ 'state' ] == "final" ) {
                $found = $originalValue;
            }

        }

        return $found;

    }

    /**
     * @see TMAnalysisWorker::_getMatches()
     * @param array $matches
     *
     * @return array
     */
    public function modifyMatches( Array $matches ){
        foreach( $matches as $pos => $match ){

            foreach( $match[ "tm_properties" ] as $_p => $property ){

                if( $property[ 'type' ] != 'x-match-quality' ) {
                    continue;
                }

                /*
                 * Microsoft send alt-trans with the same source of the real source, MyMemory identify these matches as 100% because of src == src
                 * We force these matches to be 99
                 */
                if( (int)str_replace( "%", "", $property[ 'value' ] ) == 99 && (int)str_replace( "%", "", $match[ 'match' ] ) >= 100 ){
                    $matches[ $pos ][ 'match' ] = '99%';
                } elseif(  (int)str_replace( "%", "", $property[ 'value' ] ) < 99 && (int)str_replace( "%", "", $match[ 'match' ] ) == 100  ){
                    $matches[ $pos ][ 'match' ] = 'MT';
                }

            }

        }
        return $matches;
    }

    public function handleTUContextGroups( ArrayObject $projectStructure ){

        foreach ( $projectStructure[ 'context-group' ] as $internal_id => $context_group ) {

            foreach ( $context_group[ 'context_json' ] as $index => $group ) {
                if( $group[ 'attr' ][ 'name' ] == "Microsoft Internal" ){
                    unset( $projectStructure[ 'context-group' ][ $internal_id ][ 'context_json' ][ $index ] );
                }
            }

            if( count( $projectStructure[ 'context-group' ][ $internal_id ][ 'context_json' ] ) == 0 ){
                $projectStructure[ 'context-group' ]->offsetUnset( $internal_id );
            }

        }
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
//    public function overrideConversionRequest( $language ){
//        if( \CatUtils::isCJK( $language ) ){
//            $language = 'it-IT';
//        }
//        return $language;
//    }

//    public function overrideConversionResult( $documentContent, $language ){
//        return preg_replace( '/target-language=".*?"/', "target-language=\"{$language}\"", $documentContent );
//    }


}