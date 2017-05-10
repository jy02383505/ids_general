<?php
/**
 * 安卓广告机节目播放表控制器
 * @author Skeam Tj
 *
 */
class PlaylistsAction extends CommonAction {
	
	/**
	 * 所有节目播放表
	 */
	public function index() {

	    $PlaylistsModel = D('TBAPlaylists');
	    $where = array();
	    
	    // 构建搜索条件
	    $plname = I('get.pl_name');
	    !empty($plname) && $where['pl_name'] = array('like', '%' . $plname . '%');
	    
	    // 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $PlaylistsModel->where($where)->count();
		$Page = new Page($totals, 12);
		$show = $Page->show();
		$this->assign('page', $show);
		
		$playlists = $PlaylistsModel->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();
		$this->assign('playlists', $playlists);
		$this->display();
	}
	
	/**
	 * 创建节目播放表
	 */
	public function add() {
	    if (IS_POST) {
	        $this->saveData();
	    } else {
	        $_SESSION['TBAPlaylist'] = null;
	        $programs = D('TBAPrograms')->where(array('bevalid'=>1))->order('id asc')->field('id, tplname, tplclassid')->select();
	        $this->assign('programs', $programs);
	        $this->display('edit');
	    }
	}
	
	/**
	 * 编辑节目播放表
	 */
	public function edit() {
	    if (IS_POST) {
	        $this->saveData();
	    } else {
	        $id = I('get.id', 0, 'int');
	        if (!$id) {
	            $this->error('非法操作！');
	        }
	        
	        $PlaylistsModel = D('TBAPlaylists');
	        $playlistInfo = $PlaylistsModel->where(array('id'=>$id))->find();
	        if (!$playlistInfo) {
	            $this->error('非法操作！');
	        }
	        
	        $_SESSION['TBAPlaylist'] = null;
	        $_SESSION['TBAPlaylist']['unids'] = array();
	        for ($i=1; $i<=7; $i++) {
	            if ($playlistInfo['weekday_' . $i]) {
	                $tmp = json_decode(stripslashes($playlistInfo['weekday_' . $i]), true);
	                $_SESSION['TBAPlaylist']['unids'] = array_merge($_SESSION['TBAPlaylist']['unids'], array_keys($tmp));
	                $_SESSION['TBAPlaylist'][$i] = $tmp;
	            }
	        }
	        $programs = D('TBAPrograms')->where(array('bevalid'=>1))->order('id asc')->field('id, tplname, tplclassid')->select();
	        $this->assign('programs', $programs);
	        $this->assign('plist', $playlistInfo);
	        $this->display();
	    }
	}
	
	private function saveData() {
	    $id = I('post.id', 0, 'int');
	    $plName = trim(I('post.pl_name'));
	    $defProgramClassid = trim(I('post.def_program_classid'));
	    $aDprogramClassid = trim(I('post.ad_program_classid'));
	    $remark = trim(I('post.remark'));
	    $status = I('post.status', 0, 'int');
	    
	    if (empty($plName)) {
	        $this->error('节目表名称不能为空');
	    }
	    
	    $PlaylistsModel = D('TBAPlaylists');
	    $data['pl_name'] = $plName;
	    $data['def_program_classid'] = $defProgramClassid;
	    $data['ad_program_classid'] = $aDprogramClassid;
	    $data['remark'] = $remark;
	    $data['status'] = $status;
	    $data['userid'] = $_SESSION['authId'];
	    $data['mtime'] = time();
	    for ($i=1; $i<=7; $i++) {
	        if (!empty($_SESSION['TBAPlaylist'][$i])) {
	           $data['weekday_' . $i] = json_encode($_SESSION['TBAPlaylist'][$i]);
	        }
	    }
	    
	    if ($id) {
	        $func = 'save';
	        $data['id'] = $id;
	    } else {
	        $func = 'add';
	        $data['pl_classid'] = generateUniqueID();
	        $data['ctime'] = time();
	    }
	    
	    if ($PlaylistsModel->$func($data) !== false) {
	        $_SESSION['TBAPlaylist'] = null;
	        $this->success('操作成功！', U('Playlists/index'));
	    } else {
	        $this->error('操作失败！[原因]：' . $PlaylistsModel->getError());
	    }
	    
	}
	
	/**
	 * 删除节目播放表
	 */
	public function del() {
	    $id = I('get.id', 0, 'int');
	    if (!$id) {
	        $this->error('非法操作！');
	    }
	     
	    $PlaylistsModel = D('TBAPlaylists');
	    $playlistInfo = $PlaylistsModel->where(array('id'=>$id))->find();
	    if (!$playlistInfo) {
	        $this->error('非法操作！');
	    }
	    
	    // 检测该节目是否已被应用，应用中的节目不允许被删除
	    $hasApplied = D('EndpointsGroups')->where(array('tplclassid'=>$playlistInfo['pl_classid']))->count();
	    if ($hasApplied > 0) {
	        $this->error('操作失败！[原因]：节目“' . $playlistInfo['pl_name'] . '”已被应用，不允许删除！');
	    }
	    
	    $delRe = $PlaylistsModel->where(array('id'=>$id))->delete();
	    if ($delRe !== false) {
	        $this->success('操作成功！');
	    } else {
            $this->error('操作失败！[原因]：' . $PlaylistsModel->getError());	        
	    }
	}
	
	/**
	 * 节目列表
	 */
	public function programs() {
	    
	    $programsModel = D('TBAPrograms');
	    $where = array();
	    
	    // 构建搜索条件
	    $tplname = I('get.tplname');
	    !empty($tplname) && $where['tplname'] = array('like', '%' . $tplname . '%');
	     
	    // 加载数据分页类
	    import('ORG.Util.Page');
	    
	    // 数据分页
	    $totals = $programsModel->where($where)->count();
	    $Page = new Page($totals, 12);
	    $show = $Page->show();
	    $this->assign('page', $show);
	    
	    $programs = $programsModel->where($where)->order('id asc')->limit($Page->firstRow. ',' .$Page->listRows)->select();
	    $this->assign('programs', $programs);
	    $this->display();
	    
	}
	
	public function sessionViewer() {
	    //$_SESSION['TBAPlaylist'] = null;
	    dump($_SESSION);
	}
	
}