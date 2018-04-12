<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 12/04/2018
 * Time: 12:09
 */


namespace Features\Microsoft\Utils\Email;
use Email\AbstractEmail;
use Features\Microsoft;
use INIT;

class ErrorQuotationEmail extends AbstractEmail {

    protected $title = 'Quotation gone in error';
    protected $message;


    public function __construct( $templatePath ) {

        $this->_setLayout( 'skeleton.html' );
        $this->_setTemplateByPath( $templatePath );
    }

    public function send() {
        $config =  Microsoft::getConfig();
        $this->sendTo($config['success_quotation_email_address'], "Translated Team");
    }

    public function setErrorMessage($message){
        $this->message = $message;
    }

    protected function _getTemplateVariables() {
        return [
            'message' => $this->message
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
