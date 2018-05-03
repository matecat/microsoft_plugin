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

    public function reviseUrl( $record ) {

        $reviewChunk = ChunkReviewDao::findOneChunkReviewByIdJobAndPassword(
                $record[ 'jid' ], $record[ 'jpassword' ]
        );

        return \Routes::revise(
                $record[ 'name' ],
                $record[ 'jid' ],
                ( !empty( $reviewChunk ) ? $reviewChunk->review_password : $record[ 'jpassword' ] ),
                $record[ 'source' ],
                $record[ 'target' ]
        );

    }

}