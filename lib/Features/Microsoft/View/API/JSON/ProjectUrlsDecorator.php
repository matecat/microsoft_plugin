<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 20/12/2017
 * Time: 12:08
 */

namespace Features\Microsoft\View\API\JSON;


use \Features\ReviewExtended\View\API\JSON\ProjectUrlsDecorator as UrlDecorator;
use LQA\ChunkReviewDao;


class ProjectUrlsDecorator extends UrlDecorator {

    protected function generateChunkUrls( $record){

        $reviewChunk = ChunkReviewDao::findOneChunkReviewByIdJobAndPassword(
                $record[ 'jid' ], $record[ 'jpassword' ]
        );

        if ( !array_key_exists( $record['jpassword'], $this->chunks ) ) {
            $this->chunks[ $record['jpassword'] ] = 1 ;
            $this->jobs[ $record['jid'] ][ 'chunks' ][] = array(
                    'password'      => $record['jpassword'],
                    'translate_url' => $this->translateUrl( $record ),
                    'revise_url'    => $this->reviseUrl( $record ),
            );
        }

    }

}