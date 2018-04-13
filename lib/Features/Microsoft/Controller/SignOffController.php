<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 13/04/2018
 * Time: 11:06
 */

namespace Features\Microsoft\Controller;

use API\V2\KleinController;
use MultiCurlHandler;
use API\V2\Validators\JobPasswordValidator;
use INIT;

class SignOffController extends KleinController {

    protected function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = $jobValidator->getJob();
            $this->project = $this->job->getProject();
        } );

        $this->appendValidator( $jobValidator );
    }

    public function signedOffCallback() {

        $download_links = [
                'download'       => \Routes::downloadOriginal( $this->job->id, $this->job->password ),
                'xliff'          => \Routes::downloadXliff( $this->job->id, $this->job->password ),
                'quality_report' => \Routes::qualityReport( $this->job->id, $this->job->password ),
        ];

        $mh = new MultiCurlHandler;

        $curlOptions   = [
                CURLOPT_HEADER         => 0,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_USERAGENT      => INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $download_links
        ];
        $responseToken = $mh->createResource( "", $curlOptions );
        $mh->multiExec();

        $response = $mh->getSingleContent( $responseToken );

        sleep( 1 );

    }
}