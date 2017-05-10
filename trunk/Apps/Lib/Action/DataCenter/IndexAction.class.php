<?php
/**
 * 数据管理中心首页
 * @author Skeam TJ
 *
 */
class IndexAction extends CommonAction {
	
    public function index () {
    	
        $this->display();
    }
    
    public function topFrame () {
    	
    	$this->display();
    }
    
    public function leftFrame () {
    	
    	$this->display();
    }
    
    public function toggleFrame () {
    	
    	$this->display();
    }
    
    public function rightFrame () {
    	
    	// 系统信息
    	$sysinfo = sysinfo();
    	$this->assign('sysinfo', $sysinfo);
    	
    	$this->display();
    }
}