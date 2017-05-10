<?php

class EmptyAction extends CommonAction {

    public function index() {
        $this->display('Public:empty');
    }
    
}