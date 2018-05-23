<?php

namespace Features\Microsoft\Decorator;

use AbstractDecorator;
use Features\Microsoft\Utils\Routes;

class ManageDecorator extends AbstractDecorator {
    /**
     * @var \PHPTALWithAppend
     */
    protected $template;

    public function decorate() {
        $this->template->append( 'css_resources', Routes::staticBuild( '/microsoft-build.css' ) );
    }


}