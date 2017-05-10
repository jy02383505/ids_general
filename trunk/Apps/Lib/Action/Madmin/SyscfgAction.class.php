<?php
/**
 * 系统设置控制器
 * @author Skeam TJ
 *
 */
class SyscfgAction extends CommonAction {
	
	// 基本设置
	public function basicCfg() {
		
		// 实例化模型
		$syscfgModel = M('syscfg');
		$syscfgInfo = $syscfgModel->field(array('b_sitename','b_sitetitle','b_sitelogo'))->find(1);

		// 如果是POST请求，则保存更新数据
		if (IS_POST) {
			
			$siteName = I('post.sitename', '', 'strip_tags,stripslashes');
			$siteTitle = I('post.sitetitle', '', 'strip_tags,stripslashes');
			$siteLogo = I('post.cover_image');
			
			// 数据验证
			
			// 创建数据
			$data['b_sitename'] = utf82gbk(addslashes($siteName));
			$data['b_sitetitle'] = utf82gbk(addslashes($siteTitle));
			$data['b_sitelogo'] = utf82gbk($siteLogo);
			
			if ($syscfgInfo) {  // update
				$data['id'] = 1;
				$func = 'save';
			} else { // insert
				$func = 'add';
			}
			
			$result = $syscfgModel->$func($data);
			
			if ($result !== false) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！[原因]：' . $syscfgModel->getError());
			}
			
		} else {
			if ($syscfgInfo) {
				$syscfgInfo['b_sitename'] = gbk2utf8(stripslashes($syscfgInfo['b_sitename']));
				$syscfgInfo['b_sitetitle'] = gbk2utf8(stripslashes($syscfgInfo['b_sitetitle']));
				$syscfgInfo['b_sitelogo'] = gbk2utf8($syscfgInfo['b_sitelogo']);
				
				foreach (glob(rtrim(C('UPLOAD_COMM_PATH'), '/') . '/logo*') as $file) {
				    if (is_file($file) && basename($file) != $syscfgInfo['b_sitelogo']) {
				        @unlink($file);
				    }
				}
			}
			//dump($syscfgInfo);
			$this->assign('syscfg', $syscfgInfo);
			$this->display();
		}
		
	}
	
	// 高级设置
	public function advancedCfg() {
		
		// 实例化模型
		$syscfgModel = M('syscfg');
		$syscfgInfo = $syscfgModel->field(array('a_copyright','a_aftersales','a_medialib','a_main_upfile','a_data_upfile'))->find(1);
		// dump($syscfgInfo);
		// 如果是POST请求，则保存更新数据
		if (IS_POST) {
			
			$copyright = I('post.copyright', '', 'stripslashes');
			if (strpos($copyright, '©') !== false) {
				$copyright = str_replace('©', '&copy;', $copyright);
			}
			$aftersales = I('post.aftersales', '', 'stripslashes');
			$medialib = I('post.medialib', '', 'strip_tags');
			$mainUpfile = I('post.main_upfile', '', 'strip_tags');
			$dataUpfile = I('post.data_upfile', '', 'strip_tags');
			
			// 数据验证
			
			// 创建数据
			$data['a_copyright'] = utf82gbk(addslashes(htmlspecialchars($copyright)));
			$data['a_aftersales'] = utf82gbk(addslashes(htmlspecialchars($aftersales)));
			$data['a_medialib'] = utf82gbk($medialib);
			$data['a_main_upfile'] = utf82gbk($mainUpfile);
			$data['a_data_upfile'] = utf82gbk($dataUpfile);
			
			if ($syscfgInfo) {  // update
				$data['id'] = 1;
				$func = 'save';
			} else { // insert
				$func = 'add';
			}
			
			$result = $syscfgModel->$func($data);
			
			if ($result !== false) {
				$this->success('操作成功！');
			} else {
				$this->success('操作失败！[原因]：' . $syscfgModel->getDBError());
			}
			
		} else {
			if ($syscfgInfo) {
				$syscfgInfo['a_copyright'] = gbk2utf8(stripslashes($syscfgInfo['a_copyright']));
				$syscfgInfo['a_aftersales'] = gbk2utf8(stripslashes($syscfgInfo['a_aftersales']));
				$syscfgInfo['a_medialib'] = gbk2utf8($syscfgInfo['a_medialib']);
				$syscfgInfo['a_main_upfile'] = gbk2utf8($syscfgInfo['a_main_upfile']);
				$syscfgInfo['a_data_upfile'] = gbk2utf8($syscfgInfo['a_data_upfile']);
			}
			//dump($syscfgInfo);
			$this->assign('syscfg', $syscfgInfo);
			$this->display();
		}
		
	}
	
	// 安全设置
	public function securityCfg() {
		// 实例化模型
		$syscfgModel = M('syscfg');
		$syscfgInfo = $syscfgModel->field(array('s_login_vcode'))->find(1);
		
		// 如果是POST请求，则保存更新数据
		if (IS_POST) {
				
			$loginVcode = I('post.loginvcode', 0, 'int');
				
			// 数据验证
				
			// 创建数据
			$data['s_login_vcode'] = $loginVcode;
				
			if ($syscfgInfo) {  // update
				$data['id'] = 1;
				$func = 'save';
			} else { // insert
				$func = 'add';
			}
				
			$result = $syscfgModel->$func($data);
				
			if ($result !== false) {
				$this->success('操作成功！');
			} else {
				$this->success('操作失败！[原因]：' . $syscfgModel->getError());
			}
				
		} else {
			$this->assign('syscfg', $syscfgInfo);
			$this->display();
		}
		
	}
	
	/**
	 * 修改管理员密码
	 */
	public function modifyLoginPass() {
	
		if (IS_POST) {
			$uid = $_SESSION[C('USER_AUTH_KEY')];
			$oldPass = I('post.old_pass');
			$newPass = I('post.new_pass');
			$cfmNewPass = I('post.cfm_new_pass');
				
			if ($newPass != $cfmNewPass) {
				$this->error('操作失败！[原因]：两次输入新密码不一致！');
			}
				
			if ($oldPass == '' || $newPass == '') {
				$this->error('密码不能为空！');
			} else {
				$usersModel = M('Users');
				$uDBPass = $usersModel->where(array('id'=>$uid))->getField('password');
				if ($uDBPass == md5($oldPass)) {
					$result = $usersModel->where(array('id'=>$uid))->setField('password', md5($newPass));
					if ($result) {
						$this->success('操作成功！');
					} else {
						$this->error('操作成功！[原因]：' . $usersModel->getError());
					}
				} else {
					$this->error('操作失败！[原因]：原始登录密码错误！');
				}
			}
		} else {
			$this->display();
		}
	
	}
	
	/**
	 * 模块列表
	 */
	public function nodesList() {
	
		$nodeModel = D('Node');
		//$where = array('node_id'=>array('not in', array('91f7b348-3d99-debd-a703-ac7ce6e03649')));
		$nid = I('get.nid', 0, 'int');
		if ($nid)
			$where['pid'] = $nid;
		$nodes = $nodeModel->where($where)->order('pid asc')->select();
		if ($nodes) {
			foreach ($nodes as &$nod) {
				$nod['title'] = gbk2utf8($nod['title']);
				$nod['remark'] = gbk2utf8($nod['remark']);
			}
	
			$treeNodes = array();
			$nodeModel->sortNodes($treeNodes, $nodes, $nid ? $nid : 1);
			// dump($treeNodes);
			$this->assign('nodes', $treeNodes);
		}
		
		// 获取顶级模块列表
		$where2['status'] = 1;
		$where2['pid'] = 1;
		//$where2['node_id'] = array('not in', array('91f7b348-3d99-debd-a703-ac7ce6e03649'));
		$parentNodes = $nodeModel->where($where2)->field(array('id', 'title','level'))->select();
		if ($parentNodes) {
			foreach ($parentNodes as &$nod) {
				$nod['title'] = gbk2utf8($nod['title']);
				/* if ($nod['level'] > 1)
					$nod['space'] = str_repeat('&nbsp;&nbsp;', ($nod['level']-1)*2) . '|--------'; */
			}
		}
			
		$this->assign('pnodes', $parentNodes);
		$this->display();
	}
	
	/**
	 * 添加模块
	 */
	public function addNode() {
		if (IS_POST) {
			$this->saveNode();
		} else {
	
			// 获取顶级模块列表
			$nodeModel = D('Node');
			$parentNodes = $nodeModel->where(array('status'=>1, 'pid'=>array('in', '0, 1')))->field(array('id', 'title','level'))->select();
			if ($parentNodes) {
				foreach ($parentNodes as &$nod) {
					$nod['title'] = gbk2utf8($nod['title']);
					if ($nod['level'] > 1)
							$nod['space'] = str_repeat('&nbsp;&nbsp;', ($nod['level']-1)*2) . '|--------';
				}
			}
	
			$this->assign('pnodes', $parentNodes);
			$this->display('editNode');
		}
	}
	
	/**
	 * 编辑模块
	 */
	public function editNode() {
		if (IS_POST) {
			$this->saveNode();
		} else {
			$id = I('get.nid', 0, 'int');
	
			if ($id) {
	
				$nodeModel = D('Node');
				$node = $nodeModel->find($id);
	
				if ($node) {
					$node['title'] = gbk2utf8($node['title']);
					$node['remark'] = gbk2utf8($node['remark']);
	
					// 获取顶级模块列表
					$parentNodes = $nodeModel->where(array('status'=>1, 'pid'=>array('in', '0, 1')))->field(array('id', 'title','level'))->select();
					if ($parentNodes) {
						foreach ($parentNodes as &$nod) {
							$nod['title'] = gbk2utf8($nod['title']);
							if ($nod['level'] > 1)
								$nod['space'] = str_repeat('&nbsp;&nbsp;', ($nod['level']-1)*2) . '|--------';
						}
					}
					
					$this->assign('pnodes', $parentNodes);
					$this->assign('nod', $node);
				} else {
					$this->error('非法操作！');
				}
	
			} else {
				$this->error('非法操作！');
			}
	
			$this->display();
		}
	}
	
	private function saveNode() {
		$id = I('post.id', 0, 'int');
		$pid = I('post.pid', 0, 'int');
		$name = I('post.name', '', 'strip_tags');
		$title = I('post.title', '', 'strip_tags');
		$status = I('post.status', 0, 'int');
		$remark = I('post.remark', '', 'strip_tags');
	
		if (empty($name) || empty($title)) {
			$this->error('请检查必填项！');
		}
	
		// 实例化数据模型
		$nodModel = M('Node');
	
		$data['pid'] = $pid;
		$data['name'] = $name;
		$data['title'] = utf82gbk($title);
		$data['status'] = $status;
		$data['remark'] = utf82gbk($remark);
		$data['display'] = 1;//zjh add
		if ($pid == 0) {
			$data['level'] = 1;
		} else {
			$data['level'] = $nodModel->where(array('id'=>$pid))->getField('level') *1 + 1;
		}
		$data['sort'] = 0;
	
		$func = '';
		if ($id) {
			$func = 'save';
			$data['id'] = $id;
		} else {
			$func = 'add';
			$data['node_id'] = generateUniqueID();
		}
	
		$result = $nodModel->$func($data);
	
		if ($result !== false) {
			$this->success('操作成功！', U(MODULE_NAME . '/nodesList'));
		} else {
			$this->error('操作失败！[原因]：' . $nodModel->getError());
		}
	}
	
	/**
	 * 删除模块
	 */
	public function delNode() {
		$id = I('get.nid', 0, 'int');
	
		if ($id) {
	
			$nodeModel = M('Node');
	
			// 存在子模块的模块不能删除
			$subNodesCounts = $nodeModel->where(array('pid'=>$id))->count();
	
			if ($subNodesCounts > 0) {
				$this->error('包含子模块的模块不能删除！');
			}
	
			$result = $nodeModel->delete($id);
			if ($result !== false) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！[原因]：' . $nodeModel->getError());
			}
		} else {
			$this->error('非法请求！');
		}
	}
	
	public function initData() {
		$filePath = 'Data/nodesData.sql';
		
		if (file_exists($filePath)) {
			@unlink($filePath);
		}
		
		$nodes = M('Node')->select();
		if ($nodes) {
			file_put_contents($filePath, utf82gbk('-- Super Admin Data') . PHP_EOL, FILE_APPEND);
			$nodeInsSql = "INSERT INTO tb_users (account,password,last_login_time,status) VALUES ('admin','" . md5(1) . "'," . time() . ",1);" . PHP_EOL;
			file_put_contents($filePath, $nodeInsSql, FILE_APPEND);
			file_put_contents($filePath, utf82gbk('-- Nodes Data') . PHP_EOL, FILE_APPEND);
			foreach ($nodes as $node) {
				$nodeInsSql = "INSERT INTO tb_node (name,title,status,remark,sort,pid,level,node_id) VALUES ('" . $node['name'] . "','" . $node['title'] . "'," . $node['status'] . ",'" . $node['remark'] . "'," . $node['sort'] . "," . $node['pid'] . "," . $node['level'] . ",'" . $node['node_id'] . "');" . PHP_EOL;
				file_put_contents($filePath, $nodeInsSql, FILE_APPEND);
			}
		}
	}
	
	public function dataAPIs() {
		$dataAPIModel = M('Dataapis');
		$apiList = $dataAPIModel->field(array('id', 'api_unicode', 'api_title', 'api_status'))->select();
		$this->assign('apiList', $apiList);
		$this->display();
	}
	
	public function createAPI() {
		if (IS_POST) {
			$this->saveAPIData();			
		} else {
			$apiUnicode = generateUniqueID();
			$this->assign('apiUnicode', $apiUnicode);
			$this->display();
		}
	}
	
	public function editAPI() {
		if (IS_POST) {
			$this->saveAPIData();			
		} else {
			$apiID = I('get.id', 0, 'int');
			if (!$apiID) {
				$this->error('非法操作！');
			}
			
			$dataAPIModel = M('Dataapis');
			$apiInfo = $dataAPIModel->where(array('id'=>$apiID))->find();
			if (!$apiInfo) {
				$this->error('非法操作！');
			}
			
			if (!empty($apiInfo['api_params'])) {
				$apiParams = json_decode(stripslashes($apiInfo['api_params']), true);
				if (!empty($apiParams['requestURL'])) {
					$this->assign('requestURL', str_replace('http://', '', $apiParams['requestURL']));
				} 
				
				if (!empty($apiParams['params'])) {
					$this->assign('params', $apiParams['params']);
				} 
			}
			
			$this->assign('apiUnicode', $apiInfo['api_unicode']);
			$this->assign('apiInfo', $apiInfo);
			$this->display('createAPI');
		}
	}
	
	private function saveAPIData() {
		
		$data = array();
		$apiID = I('post.id', 0, 'int');
		$data['api_title'] = trim(I('post.api_title'));
		$data['api_unicode'] = trim(I('post.api_unicode'));
		$data['api_desc'] = trim(I('post.api_desc'));
		$data['api_status'] = I('post.api_status', 0, 'int');
		
		if (empty($data['api_unicode'])) {
			$this->error('系统数据错误！唯一标识（ID）未生成，请刷新页面重试！');
		}
		
		if (empty($data['api_title'])) {
			$this->error('数据接口名称不能为空！');
		}
		
		// 接口参数处理
		$requestURL = 'http://' . str_replace('http://', '', I('post.requestURL'));
		$params = array();
		if (!empty($_POST['paramLabels']) && !empty($_POST['paramNames'])) {
			$paloop = min(count($_POST['paramLabels']), count($_POST['paramNames']));
			for ($i=0; $i<$paloop; $i++) {
				if (trim($_POST['paramLabels'][$i]) != '' && trim($_POST['paramNames'][$i]) != '') {
					array_push($params, array('paramLabel'=>$_POST['paramLabels'][$i], 'paramName'=>$_POST['paramNames'][$i], 'paramType'=>$_POST['paramTypes'][$i]));
				}
			}
		}
		
		if (!empty($params)) {
			foreach ($params as &$pa) {
				if ($pa['paramType'] == 'text') continue;
				
				if (!empty($_POST[$pa['paramName'] . 'Keys']) && !empty($_POST[$pa['paramName'] . 'Values'])) {
					
					$options = array();
					$oploop = min(count($_POST[$pa['paramName'] . 'Keys']), count($_POST[$pa['paramName'] . 'Values']));
					for ($j=0; $j<$oploop; $j++) {
						if (trim($_POST[$pa['paramName'] . 'Keys'][$j]) != '' && trim($_POST[$pa['paramName'] . 'Values'][$j]) != '') {
							array_push($options, array('optionKey'=>$_POST[$pa['paramName'] . 'Keys'][$j], 'optionValue'=>$_POST[$pa['paramName'] . 'Values'][$j], 'isDef'=>$_POST[$pa['paramName'] . 'Def'][$j]));
						}
					}
					$pa['options'] = $options;
				}
				
			}
		}
		
		$opacData = array('requestURL'=>$requestURL, 'params'=>$params);
		$data['api_params'] = json_encode($opacData);
		
		$dataAPIModel = M('Dataapis');
		if ($apiID) {
			$data['id'] = $apiID;
			$func = 'save';
		} else {
			$func = 'add';
		}
		
		$result = $dataAPIModel->$func($data);
		
		if ($result !== false) {
			$this->success('操作成功！', U('Syscfg/dataAPIs'));
		} else {
			$this->error('操作失败！[原因]：' . $dataAPIModel->getError());
		}
	}
	
	public function delAPI() {
		$apiID = I('get.id', 0, 'int');
		if (!$apiID) {
			$this->error('非法操作！');
		}
			
		$dataAPIModel = M('Dataapis');
		$apiInfo = $dataAPIModel->where(array('id'=>$apiID))->find();
		if (!$apiInfo) {
			$this->error('非法操作！');
		}
			
		if ($dataAPIModel->where(array('id'=>$apiID))->delete()) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！[原因]：' . $dataAPIModel->getError());
		}
	}
	
	public function ads() {
	
	    $adsModel = D('Ads');
	    
	    // 加载数据分页类
	    import('ORG.Util.Page');
	    
	    // 数据分页
	    $totals = $adsModel->count();
	    $Page = new Page($totals, 12);
	    $show = $Page->show();
	    $this->assign('page', $show);
	    
	    $ads = $adsModel->limit($Page->firstRow. ',' .$Page->listRows)->select();
	
	    $this->assign('ads', $ads);
	    $this->display();
	}
	
	public function addAdsRes() {
	    
	    $adsModel = D('Ads');
	    if (IS_POST) {
	        $adId = I('post.id', 0, 'int');
	        $adTitle = I('post.adstitle');
	        $adSizetype = I('post.ads_sizetype');
	        $adDelay = I('post.adsdelay', 0, 'int');
	        
	        if (empty($adTitle))
	            $this->error('操作失败！[原因]：广告标题不能为空！');
	        
	        $data['adstitle'] = $adTitle;
	        $data['ads_sizetype'] = $adSizetype;
	        $data['adsdelay'] = $adDelay > 0 ? $adDelay : 10;
	        
	        $dbResult = '';
	        if ($adId > 0) {
	            $dbResult = $adsModel->where(array('id'=>$adId))->save($data);
	        } else {
	            $data['dir_resourceid'] = generateUniqueID();
	            $dbResult = $adsModel->add($data);
	            $adId = $adsModel->getLastInsID();
	        }
	        
	        if ($dbResult !== false) {
	            $this->success('操作成功！', U('Syscfg/addAdsRes', array('id'=>$adId)));
	        } else {
	            $this->error('操作失败！[原因]：' . $adsModel->getError());
	        }
	        
	    } else {
	        
	        $adId = I('get.id', 0, 'int');
	        if ($adId > 0) {
	            $adInfo = $adsModel->where(array('id'=>$adId))->find();
	            if ($adInfo) {
	                $adsResList = D('MediaLib')->where(array('resourceid'=>$adInfo['dir_resourceid']))->select();
	                foreach ($adsResList as &$adItem) {
	                    $adItem['filepath'] = str_replace('\\', '/', $adItem['filepath']);
	                }
	                $this->assign('adsResList', $adsResList);
	                $this->assign('adInfo', $adInfo);
	            }
	        }
	        
    	    $this->display();
	    }
	}
	
	public function delAdsRes() {
	    
        $adId = I('get.id', 0, 'int');
        if (!$adId) {
            $this->error('非法操作！');
        }
        
        $adsModel = D('Ads');
        $where = array('id'=>$adId);
        $adInfo = $adsModel->where($where)->find();
        if (!$adInfo) {
            $this->error('非法操作！');
        }
        
        // 检测删除条件
        
        $delAdResult = $adsModel->where($where)->delete();
        if ($delAdResult !== false) {
            
            // 删除媒体资源目录
            $mediaLibModel = D('MediaLib');
            $re = $mediaLibModel->where(array('resourceid'=>$adInfo['dir_resourceid']))->delete();
            $baseDir = '/ads/' . $adInfo['dir_resourceid'];
            $originDir = rtrim(C('UPLOAD_ROOT_PATH'), '/') . $baseDir;
            if (is_dir($originDir) && rtrim($originDir, '/') != rtrim(C('UPLOAD_ROOT_PATH'), '/')) {
                deldir($originDir);
            }
            $thumbDir = rtrim(C('UPLOAD_THUMB_PATH'), '/') . $baseDir;
            if (is_dir($thumbDir) && rtrim($thumbDir, '/') != rtrim(C('UPLOAD_THUMB_PATH'), '/')) {
                deldir($thumbDir);
            }
            $this->success('操作成功!');
        } else {
            $this->error('操作失败！[原因]：' . $adsModel->getError());
        }
	    
	}
	
	/**
	 * 系统日志列表
	*/
	public function logList(){
		$logType = trim(I("request.logType"));
		$userid = I("request.userid",0,"int");
		
		$this->assign('userid', $userid);
		$this->assign('logType', $logType);
		
		$map = array();
		if (!empty($logType)){
			$map['type'] = $logType;
		}
		if ($userid){
			$map['userid'] = $userid;
		}
		
		$model = D("Log");
		$types = $model->getLogType();
		$this->assign('types', $types);//日志类型
		
		$userModel = M('Users');
		$users = $userModel->field("id,account")->order("id DESC")->select();
		$this->assign('users', $users);
		
		
        // 加载数据分页类
        import('ORG.Util.Page');
				
        // 数据分页
        $totals = $model->where($map)->count();
        $Page = new Page($totals, 10);
        $show = $Page->show();
        $this->assign('page', $show);	
		//var_dump($map);
		$datas = $model->where($map)->field("id,userid,account,title,disc,type,ip,convert(VARCHAR(24),createtime,120) as createtime")->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select(); 
		
		$this->assign('datas', $datas);
		$this->display("logList");
	}
	
	/**
	 * 删除系统日志
	*/
	public function delLog(){
		//暂不提供
	}
}