<?php

class SessionhandleAction extends CommonAction {
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function getWeekdayItem() {
        $weekDay = I('post.weekDay');
        
        if (!empty($_SESSION['TBAPlaylist'][$weekDay])) {
            /* 
            $item = null;
            foreach ($_SESSION['TBAPlaylist'][$weekDay] as &$item) {
                $item['programName'] = D('TBAPrograms')->where(array('tplclassid'=>$item['programClassid']))->getField('tplname');
            } 
            */
            
            die(json_encode($_SESSION['TBAPlaylist'][$weekDay]));
        } else {
            die(json_encode(null));
        }
    }
    
    public function getWeekdayProgramItem() {
        $weekDay = I('post.weekDay');
        $itemClassid = I('post.itemClassid');
        
        if (!empty($_SESSION['TBAPlaylist'][$weekDay][$itemClassid])) {
            $programs = D('TBAPrograms')->where(array('bevalid'=>1))->order('id asc')->field('id, tplname, tplclassid')->select();
            die(json_encode(array('stat'=>1, 'data'=>array('item'=>$_SESSION['TBAPlaylist'][$weekDay][$itemClassid], 'programs'=>$programs))));
        } else {
            die(json_encode(array('stat'=>0)));
        }
    }
	
	public function addTBAProgram() {
	    $weekDay = I('post.weekDay');
	    $itemClassID = I('post.itemClassID');
	    $programClassID = I('post.programClassID');
	    $startPlaytime = I('post.startPlaytime');
	    $finishedPlaytime = I('post.finishedPlaytime');
	    
	    $uniqueID = empty($itemClassID) ? $this->getUniID() : $itemClassID;
	    $programName = D('TBAPrograms')->where(array('tplclassid'=>$programClassID))->getField('tplname');
	    $_SESSION['TBAPlaylist'][$weekDay][$uniqueID] = array('programClassid'=>$programClassID, 'programName'=>$programName, 'startPlaytime'=>$startPlaytime, 'finishedPlaytime'=>$finishedPlaytime, 'ctime'=>time());
	    
	    die(json_encode(array('stat'=>1, 'insID'=>$uniqueID)));
	}
	
	public function addMultiTBAProgram() {
	    $sourceWeekday = I('post.sourceWeekday', 0, 'int');
	    $destWeekdays = explode(',', trim(I('post.destWeekdays'), ','));
	    
	    foreach ($destWeekdays as $wd) {
	        $_SESSION['TBAPlaylist'][$wd] = array();
	        foreach ($_SESSION['TBAPlaylist'][$sourceWeekday] as $prog) {
	            $tmp = $this->getUniID();
	            $_SESSION['TBAPlaylist'][$wd][$tmp] = $prog;
	        }
	    }
	    
	    die(json_encode(array('stat'=>1)));
	}
	
	public function delTBAProgram() {
	    $weekDay = I('post.weekDay');
	    $itemClassid = I('post.itemClassid');
	    
	    unset($_SESSION['TBAPlaylist'][$weekDay][$itemClassid]);
	    
	    die(json_encode(array('stat'=>1)));
	}
	
	public function getWeekdayEnabled() {
	    
	    $weekdayEnabled = array();
	    
	    for ($i=1; $i<=7; $i++) {
    	    if (!empty($_SESSION['TBAPlaylist'][$i])) {
    	        $weekdayTxt = '';
    	        switch ($i) {
    	            case 1: $weekdayTxt = '星期一'; break;
    	            case 2: $weekdayTxt = '星期二'; break;
    	            case 3: $weekdayTxt = '星期三'; break;
    	            case 4: $weekdayTxt = '星期四'; break;
    	            case 5: $weekdayTxt = '星期五'; break;
    	            case 6: $weekdayTxt = '星期六'; break;
    	            case 7: $weekdayTxt = '星期日'; break;
    	        }
    	        
    	        $weekdayEnabled[$i] = $weekdayTxt;
    	    }
	    }
	    
	    die(json_encode(array('stat'=>empty($weekdayEnabled) ? 0 : 1, 'data'=>$weekdayEnabled)));
	}
	
	private function getUniID() {
	    $uniqueID = '';
	    
	    if (!empty($_SESSION['TBAPlaylist']['unids'])) {
	        
	        do {
	            $uniqueID = generateUniqueID();
	        } while (in_array($uniqueID, $_SESSION['TBAPlaylist']['unids']));
	       
	    } else {
           $uniqueID = generateUniqueID();
	    }
	    
	    $_SESSION['TBAPlaylist']['unids'][] = $uniqueID;
	    
	    return $uniqueID;
	}
	
}