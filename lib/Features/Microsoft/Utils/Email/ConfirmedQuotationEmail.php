<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 11/04/2018
 * Time: 18:41
 */

namespace Features\Microsoft\Utils\Email;
use Email\AbstractEmail;
use Features\Microsoft;
use INIT;

class ConfirmedQuotationEmail extends AbstractEmail {

    protected $title = 'Confirmed Quotation';


    public function __construct( $templatePath ) {

        $this->_setLayout( 'skeleton.html' );
        $this->_setTemplateByPath( $templatePath );
    }

    public function send() {
        $config =  Microsoft::getConfig();
        $this->sendTo($config['success_quotation_email_address'], "Translated Team");
    }

    protected function _getTemplateVariables() {
        return [

        ];
    }

    protected function _getLayoutVariables() {
        $vars            = parent::_getLayoutVariables();
        $vars[ 'title' ] = $this->title;

        return $vars;
    }


    protected function _getDefaultMailConf() {
        $mailConf = parent::_getDefaultMailConf();

        $mailConf[ 'from' ]       = INIT::$MAILER_RETURN_PATH;
        $mailConf[ 'sender' ]     = INIT::$MAILER_RETURN_PATH;
        $mailConf[ 'returnPath' ] = INIT::$MAILER_RETURN_PATH;

        return $mailConf;
    }
}
