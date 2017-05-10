<?php
/**
 * 终端组管理控制器
 * @author Skeam TJ
 * 
 */
class EndpointsGroupsAction extends CommonAction {
	
	/**
	 * 终端组列表
	 */
	public function index() {
		$playPlanModel = D("PlayPlan");//播放计划
		$powerPlanModel = D("PowerPlan");//开关机计划
		
		import('ORG.Util.Page');
	
		// 构建查询条件
		$where = array(array('grouptype'=>$_GET['et']));
		/* $name = I('get.name', '', 'strip_tags');
		if (!empty($name)) {
			$where['groupname'] = array('like', '%' . utf82gbk($name) . '%');
		} */
	
		// 分页获取数据
		$epgModel = D('EndpointsGroups');
		/* $totals = $epgModel->where($where)->count();
		$Page = new Page($totals, 10);
		$show = $Page->show();
	
		$groups = $epgModel->where($where)->limit($Page->firstRow, $Page->listRows)->select(); */
		$groups = $epgModel->where($where)->order('level asc, id asc')->select();
		if ($groups) {
			foreach ($groups as $k=> &$g) {
				$g['groupname'] = gbk2utf8($g['groupname']);
				
				$childrenGrps = array();
				$childrenGrps = $epgModel->where("parent_classid='".$g['groupclassid']."'")->find();
				if ($childrenGrps!=false){
					//有下级组时，显示不允许指定播放计划
					$g['tplname']="--";
				}else{
					if (trim($g['tplclassid']) != '') {
						if ($_GET['et'] == 'x86') {
							$g['tplname'] = D('Tpls')->where(array('tplclassid'=>$g['tplclassid']))->getField('tplname');
						} else if ($_GET['et'] == 'azad') {
							$g['tplname'] = D('TBAPlaylists')->where(array('pl_classid'=>$g['tplclassid']))->getField('pl_name');
						} else {
						
						}
					} else {
						$g['tplname'] = '未指定';
					}				
				}
				

				
				//注意：只有终极分组才能指定播放计划，有下级分组的不允许指定
				$childrenGrps = array();
				$childrenGrps = $epgModel->where("parent_classid='".$g['groupclassid']."'")->find();
				if ($childrenGrps!=false){
					//有下级组时，显示不允许指定播放计划
					$groups[$k]['playPlanName']="--";
				}else{
					//无下级组时
					if ($g['PlayPlanId']){
						//$playPlanModel = D("PlayPlan");
						$tmpDatas = $playPlanModel->where("Id=".$g['PlayPlanId'])->find();
						if ($tmpDatas){
							$g['playPlanName']=$tmpDatas['Name'];
						}else{
							$g['playPlanName']='计划已删除';
						}
						
					}else{
						$g['playPlanName']='未指定';
					}					
				}
				
				//注意：只有终极分组才能指定播放计划，有下级分组的不允许指定
			//	$childrenGrps = array();
			//	$childrenGrps = $epgModel->where("parent_classid='".$g['groupclassid']."'")->find();
				if ($childrenGrps!=false){
					//有下级组时，显示不允许指定播放计划
					$groups[$k]['powerPlanName']="--";
				}else{
					//无下级组时
					if ($g['PowerPlanId']){
						//$playPlanModel = D("PlayPlan");
						$tmpDatas = $powerPlanModel->where("id=".$g['PowerPlanId'])->find();
						if ($tmpDatas){
							$g['powerPlanName']=$tmpDatas['Name'];
							$g['powerPlanType']=$tmpDatas['Type'];
							$g['powerPlanCanPublish']=1;
						}else{
							$g['powerPlanName']='计划已删除';
						}
						
					}else{
						$g['powerPlanName']='未指定';
					}					
				}
				
			}
			
			$treeGrps = array();
			$epgModel->sortNodes($treeGrps, $groups);
		}
		/* $this->assign('page', $show); */
		$this->assign('groups', $treeGrps);
		$this->display();
	}
	
	/**
	 * 添加终端组
	 */
	public function add() {
		if (IS_POST) {
			$this->saveData();
		} else {
			// 获取模板信息
			$tpls = array();
		    if ($_GET['et'] == 'x86') {
    			
		        $tpls = D('Tpls')->field(array('tplclassid, tplname'))->where(array('bevalid'=>1))->select();
		        $adsList = D('Ads')->field(array('adstitle', 'dir_resourceid'))->select();
    			$this->assign('adsList', $adsList);
		        
		    } else if ($_GET['et'] == 'azad' || $_GET['et'] == 'azt') {
		        
		        $plLists =  D('TBAPlaylists')->field(array('pl_classid, pl_name'))->where(array('status'=>1))->select();
		        foreach ($plLists as $pl) {
		            $tpls[] = array('tplclassid'=>$pl['pl_classid'], 'tplname'=>$pl['pl_name']);
		        }
		        
		    } else {
		        
		    }
			
			$epgModel = D('EndpointsGroups');
			$pGrps = $epgModel->where(array('grouptype'=>$_GET['et']))->order('level asc, id asc')->select();
			if ($pGrps) {
			
				$treeGrps = array();
				$epgModel->sortNodes($treeGrps, $pGrps);
				foreach ($treeGrps as &$grp) {
				    $grp['has_sub_ends'] = D('Endpoint')->where(array('touchEndPoint_GroupClassId'=>$grp['groupclassid']))->count();
				}
				$this->assign('pGrps', $treeGrps);
				
			}
			
			// 获取终端类型
			/* $epSorts = D('EndpointsType')->getField('typecode, typename');
			$this->assign('epSorts', $epSorts); */
			
			//播放计划
			$playPlanModel = D("PlayPlan");
			$playPlans = $playPlanModel->where($map)->field("Id,Name,PlanType,TplType,Resolution")->order("id DESC")->select(); 
			$this->assign('playPlans', $playPlans);
			
			//开关机计划
			$powerPlanModel = D("PowerPlan");//开关机计划
			$map=array();
			$powerPlans = $powerPlanModel->where($map)->field("Id,Name")->order("Id DESC")->select(); 
			$this->assign('powerPlans', $powerPlans);	
			
			$this->assign('tpls', $tpls);
			$this->display('edit');
		}
	}
	
	/**
	 * 修改终端组信息
	 */
	public function edit() {
		
		if (IS_POST) {
			
			$this->saveData();
        //    $this->refreshList();//zjh 注释掉本句，因为修改提交后，若TServer未启动会报错（董工要求禁止调用WebServerse）
		} else {
			
			$id = I('get.id', 0, 'int');
			
			if (!$id)
				$this->error('非法请求！');
			
			// 获取组信息
			$epgModel = D('EndpointsGroups');
			$groupi = $epgModel->where(array('id'=>$id))->find();
			if ($groupi) {
				$groupi['groupname'] = gbk2utf8($groupi['groupname']);
				$childrenGrps = $epgModel->getChildrenGrps($groupi['groupclassid']);
				$this->assign('childrenGrps', $childrenGrps);
				
				$realChildrenGrps = array();
				foreach ($childrenGrps as $child) {
					if ($child !== $groupi['groupclassid']) {
						array_push($realChildrenGrps, $child);
					}
				}
				$this->assign('realChildrenGrps', $realChildrenGrps);
			}
			
			// 获取模板信息
			$tpls = array();
		    if ($_GET['et'] == 'x86') {
    			
		        $tpls = D('Tpls')->field(array('tplclassid, tplname'))->where(array('bevalid'=>1))->select();
		        $adsList = D('Ads')->field(array('adstitle', 'dir_resourceid'))->select();
		        $this->assign('adsList', $adsList);
		        
		        $groupi['adsclassid'] = D('Ads')->where(array('touchendpoint_groupclassid'=>array('like', '%,' . $groupi['groupclassid'] . ',%')))->getField('dir_resourceid');
    			
		    } else if ($_GET['et'] == 'azad' || $_GET['et'] == 'azt') {
		        
		        $plLists =  D('TBAPlaylists')->field(array('pl_classid, pl_name'))->where(array('status'=>1))->select();
		        foreach ($plLists as $pl) {
		            $tpls[] = array('tplclassid'=>$pl['pl_classid'], 'tplname'=>$pl['pl_name']);
		        }
		        
		    } else {
		        
		    }
			$this->assign('tpls', $tpls);
			
			$pGrps = $epgModel->where(array('grouptype'=>$_GET['et'], 'level'=>array('elt', 3)))->order('level asc, id asc')->select();
			if ($pGrps) {
			
				$treeGrps = array();
				$epgModel->sortNodes($treeGrps, $pGrps);
				foreach ($treeGrps as &$grp) {
				    $grp['has_sub_ends'] = D('Endpoint')->where(array('touchEndPoint_GroupClassId'=>$grp['groupclassid']))->count();
				}
				$this->assign('pGrps', $treeGrps);
			}
			
			// 获取终端类型
			/* $epSorts = D('EndpointsType')->getField('typecode, typename');
			$this->assign('epSorts', $epSorts); */
			
			//播放计划
			$playPlanModel = D("PlayPlan");
			$map=array();
			$map['TplType'] = $groupi['grouptype'];//x86,azt,azad
			$playPlans = $playPlanModel->where($map)->field("Id,Name,PlanType,TplType,Resolution")->order("id DESC")->select(); 
			$this->assign('playPlans', $playPlans);
			
			//开关机计划
			$powerPlanModel = D("PowerPlan");//开关机计划
			$map=array();
			$powerPlans = $powerPlanModel->where($map)->field("id,Name,Type")->order("id DESC")->select(); 
			$this->assign('powerPlans', $powerPlans);	
			
			$this->assign('epg', $groupi);
			$this->display();
		}
	}
	
	private function saveData() {
		
		// 检查请求方式
		if (!IS_POST){
			$this->error('非法操作！');
		}
		// 过滤用户提交数据
		$id = I('post.id', 0, 'int');
		$gname = trim(I('post.groupname'));
		$tplID = trim(I('post.tplclassid'));
		$adsClassID = trim(I('post.adsclassid'));
		$endType = trim(I('post.endType'));
		$parentClassid = trim(I('post.parent_classid'));
		$PlayPlanId = intval(I('post.PlayPlanId'));
		$PowerPlanId = intval(I('post.PowerPlanId'));
		
		if (empty($gname)) {
			$this->error('组名称必填！');
		}
		
		$tendModel = D('Endpoint');//终端
		$taskModel = D('EPTask');//终端任务
		
		// 构建提交数据
		$epgModel = D('EndpointsGroups');
		$data = array();
		$data['groupname'] = utf82gbk($gname);
		$data['grouptype'] = $endType;
		$data['tplclassid'] = $tplID;
		$data['parent_classid'] = $parentClassid;
		$data['PlayPlanId'] = $PlayPlanId;//播放计划
		$data['PowerPlanId'] = $PowerPlanId;//开关机计划
		
		if ($parentClassid == '') {
			$data['level'] = 1;
		} else {
			$data['level'] = $epgModel->where(array('groupclassid'=>$parentClassid))->getField('level') + 1;
		}
		
		$func = '';
		if ($id) {
			$func = 'save';
			$data['id'] = $id;
			$oldGroupInfo = $epgModel->where(array('id'=>$id))->find();//终端组原信息，用于判断变更
			
			//开关机计划变更
			if ($oldGroupInfo['PowerPlanId'] <> $PowerPlanId ){
				//找到本终端组所有组端，循环下发任务
				
				//构造参数commandParam1
				/*
				$powerPlanModel = D("PowerPlan");//开关机计划
				$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划时间段
				$powerPlan = $powerPlanModel->where("Id=".$PowerPlanId)->find();
				if ($powerPlan){
					$tms = $powerPlanDetailModel->where("PowerPlanId=".$PowerPlanId)->order("OnTime ASC")->select();
					if ($tms){
						$a = array();
						foreach ($tms as $k=>$v){
							$a[] = $v['OnTime'].",".$v['OffTime'];
						}
						if (!empty($a)){
							$commandParam1 = implode(";",$a);//08:00:00,12:00:00;14:30:00,18:30:00 加到TB_EndPointTask表
						}
					}
					//var_dump($commandParam1);
				}
				
				// 获取终端组数据
				$ends = $tendModel->where(array('touchEndPoint_GroupClassId'=>$oldGroupInfo['groupclassid']))->order('tid desc')->select();
				if ($ends) {
					//var_dump($ends);exit;
					foreach ($ends as $k=>$v){
						if (!$taskModel->addEPTask($v['touchMainId'], 'Command_PowerOnOffTime', $commandParam1, '指定新的开关机计划')) {
							$failed = 0;
							$errMsg += '请求发送失败<br>';
						}
					}
					echo $errMsg;
				}
				*/
				
			}
		} else {
			$func = 'add';
			$data['groupclassid'] = generateUniqueID();
		}
		
		$epgRe = $epgModel->$func($data);
		if ($epgRe !== false) {
			if ($id) {
				if ($oldGroupInfo['level']*1 != $data['level']) {
					$epgModel->upChildrenLevel($epgModel->where(array('id'=>$id))->getField('groupclassid'), $data['level']);
				}				
				/* if (trim($oldGroupInfo['tplclassid']) != $tplID) {
					$childrenGrps = $epgModel->getChildrenGrps($oldGroupInfo['groupclassid']);
					$epgModel->where(array('groupclassid'=>array('in', $childrenGrps)))->setField('tplclassid', $tplID);
				} */
			}
			
			if (!empty($parentClassid)) {
				$epgModel->where(array('groupclassid'=>$parentClassid))->setField('tplclassid', '');
			}
			
			// 广告设置处理
			if (!empty($adsClassID)) {
			    
    			$adsModel = D('Ads');
    			$needOper = true;
    			if ($id) {
    			    
    			    $adsInfo = $adsModel->field(array('id', 'dir_resourceid', 'touchendpoint_groupclassid'))->where(array('touchendpoint_groupclassid'=>array('like', '%,' . $oldGroupInfo['groupclassid'] . ',%')))->find();
    			    if ($adsInfo) {
    			        if ($adsInfo['dir_resourceid'] != $adsClassID) {
    			            $tepGrpClassId = str_replace(',' . $oldGroupInfo['groupclassid'] . ',', ',', $adsInfo['touchendpoint_groupclassid']);
        			        $adsModel->where(array('id'=>$adsInfo['id']))->setField('touchendpoint_groupclassid', $tepGrpClassId);
    			        } else {
    			            $needOper = false;
    			        }
    			    }
    			    
    			} 
    			
    			if ($needOper) {
    			    
    			    $grpClassId = $id ? $oldGroupInfo['groupclassid'] : $data['groupclassid'];
    			    $adsGrpInfo = $adsModel->where(array('dir_resourceid'=>$adsClassID))->getField('touchendpoint_groupclassid');
    			    $adsGrpInfo = trim($adsGrpInfo, ',');
    			    $adsModel->where(array('dir_resourceid'=>$adsClassID))->setField('touchendpoint_groupclassid', $adsGrpInfo != '' ? ',' . $adsGrpInfo . ',' . $grpClassId . ',' : ',' . $grpClassId . ',');
    			    
    			}
    			
			}
			
			$this->success('操作成功', U('EndpointsGroups/index', array('et'=>$endType, 'isUpTree'=>1)));
		} else {
			$this->error('操作失败！[原因]：' . $epgModel->getError());
		}
	}
    
    
    public function refreshList() {
		
		set_time_limit(0); 
		
		// 命令列表
		$commandList  = array(
            'RefreshList'                  =>	array('commandName'=>'Command_RefreshList','commandNote'=>'请求刷新终端列表')
		);
		
		// 表单提交参数
		$cmdKey = trim('RefreshList');	// 该参数应对应命令列表的键值
		
		if ( empty($cmdKey)) {
			echo json_encode(array('stat'=>0, 'msg'=>'[ RequestData ] 网络数据错误，请刷新页面重试……'));
			exit();
		}
		
		if (!in_array($cmdKey, array_keys($commandList))) {
			echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
			exit();
		}
		
		$params = null;
		$commandName = $commandList[$cmdKey]['commandName'];
		$commandNote = $commandList[$cmdKey]['commandNote'];
		$errMsg = $commandList[$cmdKey]['commandNote'] . '失败...Caused by WSDL.';
			
		//  请求TServer的服务端口
		try {
			$options = array(
					'exceptions'=>true,
					'cache_wsdl'=>WSDL_CACHE_NONE
			);
			$clients = @new SoapClient('http://'.C("TSERVER_IP").':'.C("SOAP_PORT").'?wsdl', $options);//'http://localhost:5249?wsdl'
			//$clients = @new SoapClient('http://192.168.1.253:5249/?wsdl', $options);
			
		} catch (Exception $e) {
			//echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
			echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
			exit();
		} catch(SoapFault $f) {
			// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
			echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
			exit();
		}
		
		
		/**
		 * 操作指定组：参数touchMainId为空，参数commandParam2为终端组CLASSID
		 * 操作指定终端：参数touchMainId为终端编号，参数commandParam2为空
		 * 所有组：参数touchMainId为空，参数commandParam2为all
		 */
				$EPModel = D('Endpoint');
				
				if ($cmdKey == 'RefreshList') {
				    
					try {
						
						$this->clearHistoryTask();
						$params = array('touchMainId'=>'','commandName'=>$commandName,'commandParam1'=>'','commandParam2'=>'','commandNote'=>$commandNote);
						$ss = $clients->EndpointTask($params);
						
					} catch (Exception $e) {
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					} catch(SoapFault $f) {
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					}
					
				} 
				//echo json_encode(array('stat'=>1));
				//exit();
	}
    
    
    
	
	/**
	 * 删除终端组
	 */
	public function del() {
		$id = I('get.id', 0, 'int');
		
		if (!$id) {
			$this->error('非法操作！');
		}
		
		$epgModel = D('EndpointsGroups');
		$egpInfo = $epgModel->where(array('id'=>$id))->find();
		if (!$egpInfo) {
			$this->error('非法操作！');
		}
		
		// 检查该终端组是否为空，非空的终端组不允许直接删除
		$subEpgs = $epgModel->where(array('parent_classid'=>$egpInfo['groupclassid']))->count();
		
		$tendModel = D('Endpoint');
		$egpEnds = $tendModel->where(array('touchEndPoint_GroupClassId'=>$egpInfo['groupclassid']))->count();
		if ($subEpgs >0 || $egpEnds > 0) {
			$this->error('该终端组非空，不允许删除！');
		}
		
		// 删除终端组
		$delRe = $epgModel->where(array('id'=>$id))->delete();
		if ($delRe !== false) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！[原因]：' . $epgModel->getError());
		}
	}
}