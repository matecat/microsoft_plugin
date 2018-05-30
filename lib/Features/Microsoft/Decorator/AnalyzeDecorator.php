<?php

namespace Features\Microsoft\Decorator;

use AbstractDecorator;
use Features\Microsoft\Utils\Routes;

class AnalyzeDecorator extends AbstractDecorator {
    /**
     * @var \PHPTALWithAppend
     */
    protected $template;

    public function decorate() {
        $this->template->enable_outsource = false;
    }


}