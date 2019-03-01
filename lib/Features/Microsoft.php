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
use Exceptions\ValidationError;
use Features;
use Features\Microsoft\Model\Analysis\CustomPayableRates;
use Features\Microsoft\Utils\Constants\Revise;
use Features\Microsoft\Utils\Metadata;
use Features\Microsoft\View\API\JSON\MicrosoftUrlsDecorator;
use Features\Traits\PhManagementTagTrait;
use Klein\Klein;

class Microsoft extends BaseFeature {

    use PhManagementTagTrait;

    const FEATURE_CODE = "microsoft";

    protected $logger_name = self::FEATURE_CODE;

    public static $dependencies = [
            Features::PROJECT_COMPLETION,
            Features::TRANSLATION_VERSIONS,
            Features::QACHECK_GLOSSARY,
            Features::REVIEW_EXTENDED
    ];

    public static function loadRoutes( Klein $klein ) {
        route( '/job/[:id_job]/[:password]/hts', 'GET', 'Features\Microsoft\Controller\TranslatedConnectorController', 'sendJob' );
    }

    /**
     * @param ProjectUrls $formatted
     *
     * @return MicrosoftUrlsDecorator|\Features\ReviewExtended\View\API\JSON\ProjectUrlsDecorator
     */
    public static function projectUrls( ProjectUrls $formatted ) {
        $projectUrlsDecorator = new MicrosoftUrlsDecorator( $formatted->getData() );

        return $projectUrlsDecorator;
    }

    /**
     * @param $projectStructure
     *
     * @throws \Exception
     */
    public function postProjectCommit( $projectStructure ) {

        $config = self::getConfig();
        $mh     = new \MultiCurlHandler();
        $hashes = [ ];

        foreach ( $projectStructure[ 'target_language' ] as $k=>$target_lang ) {

            $curl_additional_params = [
                    CURLOPT_HEADER         => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_USERAGENT      => \INIT::MATECAT_USER_AGENT . \INIT::$BUILD_NUMBER,
                    CURLOPT_CONNECTTIMEOUT => 10, // a timeout to call itself should not be too much higher :D
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_HTTPHEADER     => [
                            'Authorization: Basic ' . $config[ 'one_policheck_pass' ], //key1
                            'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS     => json_encode( [
                            "projectid"        => $projectStructure[ 'array_jobs' ]['job_list'][$k] . "-" . $projectStructure[ 'array_jobs' ]['job_pass'][$k] ,
                            "partnerid"        => $config[ 'one_policheck_user' ],
                            "sourceLocale"     => $projectStructure[ 'source_language' ],
                            "targetLocale"     => $target_lang,
                            "spellcheck"       => false,
                            "data"             => [ ],
                            "isEmpty"          => true,
                            "policheckEnabled" => true
                    ] )
            ];
            $hashes[] = $mh->createResource( $config[ 'one_policheck_url' ], $curl_additional_params );
        }

        $mh->multiExec();
        $mh->multiCurlCloseAll();
        foreach ( $hashes as $hash ) {
            if ( $mh->hasError( $hash ) ) {
                $info_project = "";
                foreach ( $mh->getOptionRequest( $hash ) as $info ) {
                    if ( is_array( $info ) ) {
                        $info_project .= implode( $info ) . " ";
                    } else {
                        $info_project .= $info . " ";
                    }
                }
                $error = implode( $mh->getError( $hash ) );
                \Log::doLog( "error OnePolicheck: " . $info_project . " error: " . $error );
                $this->getLogger()->error( "error OnePolicheck: " . $info_project . " error: " . $error );
                \Utils::sendErrMailReport( "error OnePolicheck: " . $info_project . " error: " . $error );
            }
        }
    }

    public function filterProjectCompletionDisplayButton($displayButton, Features\ProjectCompletion\Decorator\CatDecorator $decorator) {
        if ( $decorator->getController()->isRevision() ) {
            return false;
        }
        return $displayButton ;
    }

    /**
     * @see \ProjectManager::_insertPreTranslations()
     *
     * @param $structArray array
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
     *      'payable_rates'    => [
     *                              'NO_MATCH'    => 100,
     *                              '50%-74%'     => 100,
     *                              '75%-84%'     => 60,
     *                              '85%-94%'     => 60,
     *                              '95%-99%'     => 60,
     *                              '100%'        => 30,
     *                              '100%_PUBLIC' => 30,
     *                              'REPETITIONS' => 30,
     *                              'INTERNAL'    => 60,
     *                              'MT'          => 80
     *                           ]
     *  ]
     * </code>
     *
     * @param $projectStruct
     *
     * @return array $iceLockArray
     * @throws \Exception
     */
    public function setSegmentTranslationFromXliffValues( $structArray, $projectStruct ) {
        $payableRates = json_decode( $structArray[ 'payable_rates' ], true );
        $wordCount    = \CatUtils::segment_raw_word_count( $structArray[ 'trans-unit' ][ 'source' ][ 'raw-content' ] );

        if ( $projectStruct['metadata']['project_type'] == Metadata::TRANSLATE_TYPE ) {
            $structArray = $this->_setSegmentTranslationFromXliffValuesForTranslate( $structArray, $wordCount, $payableRates );
        }

        elseif ( $projectStruct['metadata']['project_type'] == Metadata::REVIEW_TYPE) {
            $structArray[ 'match_type' ]    = 'ICE';
            $structArray[ 'eq_word_count' ] = $wordCount * $payableRates[ '100%' ] / 100;
            $structArray[ 'status' ]        = \Constants_TranslationStatus::STATUS_DRAFT;
        }

        return $structArray;
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
        if ( $projectStructure['metadata']['project_type'] == Metadata::REVIEW_TYPE ) {
            return true ;
        }

        $found = false;

        foreach ( $xliff_trans_unit[ 'alt-trans' ] as $altTrans ) {
            $match_quality = (int)str_replace( "%", "", @$altTrans[ 'attr' ][ 'match-quality' ] );

            if ( $match_quality > 100 && $xliff_trans_unit[ 'target' ][ 'attr' ][ 'state' ] == "final" ) {
                $found = $originalValue;
            } elseif( $match_quality == 10 ){
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

    /**
     * @see \ProjectManager::insertContextsForFile()
     *
     * @param ArrayObject $projectStructure
     */
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
     * @see \ProjectManager::_createJobs()
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
        ReviewExtended::loadAndValidateModelFromJsonFile( $projectStructure, $qa_mode_file );
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
     *
     * @param string $language
     * @param string $filePath
     *
     * @return string
     */
    public function changeXliffTargetLangCode( $language, $filePath ){

        $fileInfo = \DetectProprietaryXliff::isXliff( null, $filePath );
        if ( isset( $fileInfo[ 0 ] ) ) {
            //this allow xlf converted with matecat filters to be back converted with the CJK language fix
            preg_match( '#tool-id\s*=\s*"matecat-converter#i', $fileInfo[ 0 ], $matches );
            if ( !empty( $matches ) ) {
                if( \CatUtils::isCJK( $language ) ){
                    $language = 'it-IT';
                }
            }
        }

        return $language;

    }

    public function overrideConversionResult( $documentContent, $language ){
        return preg_replace( '/target-language=".*?"/', "target-language=\"{$language}\"", $documentContent );
    }

    /**
     * Override the instance decision to convert or not the normal xlf/xliff files
     *
     * @param $forceXliff
     *
     * @param $_userIsLogged
     *
     * @param $xliffPath
     *
     * @return bool
     */
    public function forceXLIFFConversion( $forceXliff, $_userIsLogged, $xliffPath ) {
        if( !$_userIsLogged ) {
            return $forceXliff;
        }
        $fileInfo = \DetectProprietaryXliff::isXliff( null, $xliffPath );
        if ( isset( $fileInfo[ 0 ] ) ) {
            preg_match( '#tool-id\s*=\s*"mdxliff"#i', $fileInfo[ 0 ], $matches );
            if ( !empty( $matches ) ) {
                return true;
            }
        }
        return false;
    }

    public function filterNewProjectInputFilters( $inputFilter ) {
        return array_merge( $inputFilter, Metadata::getInputFilter() ) ;
    }

    public function filterProjectMetadata( $metadata, $__postInput ) {
        if (empty( $__postInput[ Metadata::PROJECT_TYPE_METADATA_KEY ] ) ){
            $__postInput[ Metadata::PROJECT_TYPE_METADATA_KEY ] = Metadata::TRANSLATE_TYPE ;
        }

        $valid_types = Metadata::$valid_project_types  ;
        $valid_types_string = implode(', ', $valid_types );
        if ( !in_array( $__postInput[ Metadata::PROJECT_TYPE_METADATA_KEY ], $valid_types ) ) {
            throw new ValidationError( "Project type '{$__postInput[ Metadata::PROJECT_TYPE_METADATA_KEY ]}'' is not allowed. Allowed types: [ $valid_types_string ]." );
        }

        $metadata[ Metadata::PROJECT_TYPE_METADATA_KEY ] = $__postInput[ Metadata::PROJECT_TYPE_METADATA_KEY ];

        return $metadata;
    }

    /**
     * @param $structArray
     * @param $wordCount
     * @param $payableRates
     *
     * @return mixed
     */
    protected function _setSegmentTranslationFromXliffValuesForTranslate( $structArray, $wordCount, $payableRates ) {
        foreach ( $structArray[ 'trans-unit' ][ 'alt-trans' ] as $altTrans ) {

            $match_quality = (int)str_replace( "%", "", $altTrans[ 'attr' ][ 'match-quality' ] );

            if ( $match_quality >= 100 && @$structArray[ 'trans-unit' ][ 'target' ][ 'attr' ][ 'state' ] == "final" ) {
                $structArray[ 'locked' ] = 1;
                $structArray[ 'status' ] = \Constants_TranslationStatus::STATUS_APPROVED;
                break;
            } elseif ( $match_quality == 10 ) {
                /**
                 * Standard word count is needed
                 *
                 * @see getProjectSegmentsTranslationSummary
                 */
                $structArray[ 'match_type' ]          = 'MT';
                $structArray[ 'eq_word_count' ]       = $wordCount * $payableRates[ 'MT' ] / 100;
                $structArray[ 'standard_word_count' ] = $wordCount;
                $structArray[ 'status' ]              = \Constants_TranslationStatus::STATUS_DRAFT;
            }
        }

        return $structArray;
    }
}