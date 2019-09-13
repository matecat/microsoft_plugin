<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 20/12/2017
 * Time: 12:08
 */

namespace Features\Microsoft\View\API\JSON;


use Chunks_ChunkStruct;
use \Features\ReviewExtended\View\API\JSON\ProjectUrlsDecorator;
use LQA\ChunkReviewDao;


class MicrosoftUrlsDecorator extends ProjectUrlsDecorator {

    public function reviseUrl( $record ) {

        $reviewChunk = ( new ChunkReviewDao() )->findChunkReviews(
                new Chunks_ChunkStruct( [ 'id' => $record[ 'jid' ], 'password' => $record[ 'jpassword' ] ] )
        )[ 0 ];

        return \Routes::revise(
                $record[ 'name' ],
                $record[ 'jid' ],
                ( !empty( $reviewChunk ) ? $reviewChunk->review_password : $record[ 'jpassword' ] ),
                $record[ 'source' ],
                $record[ 'target' ]
        );

    }

}