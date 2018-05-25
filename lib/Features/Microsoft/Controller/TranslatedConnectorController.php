<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 23/05/2018
 * Time: 11:11
 */

namespace Features\Microsoft\Controller;

use API\V2\KleinController;
use API\V2\Validators\JobPasswordValidator;
use API\V2\Json\ProjectUrls;
use DataAccess\ShapelessConcreteStruct;
use Features\Microsoft\Utils\Email\ConfirmedQuotationEmail;
use Features\Microsoft\Utils\Email\ErrorQuotationEmail;
use Features\Microsoft;
use \Features\Outsource\Traits\Translated as TranslatedTrait;

class TranslatedConnectorController extends KleinController {

    use TranslatedTrait;

    protected $job;
    protected $project;

    protected function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = ( new \Jobs_JobDao() )->read( $jobValidator->getJob()->id )[ 0 ];
            $this->project = $this->job->getProject();
        } );

        $this->appendValidator( $jobValidator );
    }

    public function sendJob() {

        /**
         * @var $projectData ShapelessConcreteStruct[]
         */
        $projectData = ( new \Projects_ProjectDao() )->setCacheTTL( 60 * 60 * 24 )->getProjectData( $this->project->id, $this->project->password );
        $formatted   = new ProjectUrls( $projectData );

        //Let the Feature Class decide about Urls
        $formatted = Microsoft::projectUrls( $formatted );

        $this->config = Microsoft::getConfig();

        $eq_word = \Jobs_JobDao::getEQWord( $this->job );

        $this->setSuccessMailSender( new ConfirmedQuotationEmail( Microsoft::getPluginBasePath() . '/Features/Microsoft/View/Emails/confirmed_quotation.html' ) );
        $this->setFailureMailSender( new ErrorQuotationEmail( Microsoft::getPluginBasePath() . '/Features/Microsoft/View/Emails/error_quotation.html' ) );
        $response = $this->requestJobQuote( $this->job, $eq_word, $this->project, $formatted );
        if ( !empty( $response ) ) {
            $this->response->body( "ok - " . $this->getExternalProjectId() );
        } else {
            $this->response->body( "ko" );
        }

    }
}