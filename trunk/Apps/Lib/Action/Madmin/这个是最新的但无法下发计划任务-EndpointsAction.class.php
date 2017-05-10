<?php
/**
 * 终端管理控制器
 * @author Skeam TJ
 * 
 */
class EndpointsAction extends CommonAction {
	
	/**
	 * 终端管理首页
	 */
	public function index() {
		
		$epgModel = D('EndpointsGroups');
		
		// 获取终端组信息
		$groupId = I('get.epgId', 0, 'int');
		if ($groupId) {
			$groupInfo = $epgModel->where(array('id'=>$groupId))->find();
			if ($groupInfo) {
				if (trim($groupInfo['tplclassid']) != '') {
					$tplInfo = D('Tpls')->where(array('tplclassid'=>$groupInfo['tplclassid']))->find();
					if (!empty($tplInfo)) {
						$groupInfo['tplname'] = $tplInfo['tplname'];
						if (trim($tplInfo['binding_program_classid']) != '') {
							$projname = M('Programs')->where(array('program_classid'=>trim($tplInfo['binding_program_classid'])))->getField('program_name');
							$groupInfo['projname'] = $projname ? $projname : '未绑定';
						}
					} else {
						$groupInfo['tplname'] = '未指定';
						$groupInfo['projname'] = '未绑定';
					}
					
				} else {
					$groupInfo['tplname'] = '未指定';
					$groupInfo['projname'] = '未绑定';
				}
				
				$groupInfo['gpath'] = $epgModel->getGroupPath($groupInfo['groupclassid']);
				$this->assign('group', $groupInfo);
			}
		}
		
		// 获取终端组数据
		$groups = $epgModel->where(array('grouptype'=>$_GET['et']))->order('level asc, id asc')->select();
		if ($groups) {
			$treeGrps = array();
			$epgModel->sortNodes($treeGrps, $groups);
			$this->assign('groups', $treeGrps);
		}
		
		$this->display();
	}
	
	/**
	 * 终端列表
	 */
	public function lists() {
		
		// 处理筛选参数
		$groupId = I('get.epgId', 0, 'int');
		$orderby = I('get.orderby');
		$endType = I('get.et');
		$order = I('get.order', 0, 'int');
		
		// 初始化终端过滤器
		$tendsWhere = array();
		!empty($endType) && $tendsWhere['touchEndPointSort'] = $endType;
		$tendsOrder = '';
		switch ($orderby) {
			case 'online' : $tendsOrder = 'touchEndPoint_Online'; break;
			case 'mainid' : $tendsOrder = 'touchMainId'; break;
			default: $tendsOrder = 'touchEndPoint_Online';
		} 
		$tendsOrder .= $order ? ' desc' : ' asc';
		
		// 获取终端组数据
		$epgModel = D('EndpointsGroups');
		$groups = $epgModel->select();
		if ($groups) {
			$groups2 = array();
			foreach ($groups as &$g) {
				$g['groupname'] = gbk2utf8($g['groupname']);
				$groups2[$g['groupclassid']] = $g['groupname'];
		
				// 构建数据筛选条件
				if ($groupId == $g['id']) {
					$tendsWhere['touchEndPoint_GroupClassId'] = array('in', $epgModel->getChildrenGrps($g['groupclassid']));
				}
			}
		}
		
		// 获取终端列表
		$tendModel = D('Endpoint');
		$tends = $tendModel->where($tendsWhere)->order($tendsOrder)->select();
		if ($tends) {
			foreach ($tends as &$t) {
				$t['touchEndPointName'] = gbk2utf8($t['touchEndPointName']);
				$t['touchEndPointStatus'] = gbk2utf8($t['touchEndPointStatus']);
				$t['touchEndPoint_GroupClassName'] = (trim($t['touchEndPoint_GroupClassId']) != '' && isset($groups2[trim($t['touchEndPoint_GroupClassId'])])) ? $groups2[trim($t['touchEndPoint_GroupClassId'])] : '--';
				$t['touchEndPointSortTxt'] = D('EndpointsType')->where(array('typecode'=>$t['touchEndPointSort']))->getField('typename');
			}
		}
		//var_dump(C("SYSTEMNAME"));//school或hospital等，董工加的
		$this->assign('groups', $groups);
		$this->assign('tends', $tends);
		$this->display();
	}
	
	public function preview() {
		
		$id = I('get.tid', 0, 'int');
		
		if ($id) {
			$tendModel = D('Endpoint');
			$tend = $tendModel->where(array('tid'=>$id))->find();
			
			if ($tend) {
				$tend['touchEndPointName'] = gbk2utf8($tend['touchEndPointName']);
				$tend['touchEndPoint_ComputerName'] = gbk2utf8($tend['touchEndPoint_ComputerName']);
				$tend['touchEndPoint_DiskTotalSize'] = gbk2utf8($tend['touchEndPoint_DiskTotalSize']);
				$tend['lastShotSnap'] = basename($tend['lastShotSnap']);
				$tend['groupName'] = D('EndpointsGroups')->getGroupPath($tend['touchEndPoint_GroupClassId']);
				
				// 该终端的任务列表列表
				$tendTasks = D('EPTask')->where(array('touchMainId'=>$tend['touchMainId'], 'isFinished'=>0))->order('taskId desc')->select();
				if ($tendTasks) {
					foreach ($tendTasks as &$item) {
						$item['commandNote'] = gbk2utf8($item['commandNote']);
					}
				}
				
				$this->assign('tendTasks', $tendTasks);
				$this->assign('tend', $tend);			
			} else {
				$this->error('非法操作！');
			}
			
		} else {
			$this->error('非法操作！');
		}
		
		$this->assign('isWrite', 0);
		$this->display('edit');
	}
	
	/**
	 * 添加终端
	 */
	public function addNewEnd() {
		
		if (!IS_POST) {

			// 获取终端组数据
			$epgModel = D('EndpointsGroups');
			$groups = $epgModel->where(array('grouptype'=>$_GET['et']))->order('level asc, id asc')->select();
			if ($groups) {
				$treeGrps = array();
				$epgModel->sortNodes($treeGrps, $groups);
				$this->assign('groups', $treeGrps);
			}
			$this->display();
		
		} else {
			
			$addEndType = trim(I('post.addEndType'));
			$endType = trim(I('post.endType'));
			$groupclassid = trim(I('post.groupclassid'));
			$newEndIDs = trim(I('post.endid'), ',');
			$headerLetter = trim(I('post.header-letter'));
			$enumber = I('post.enumber', 0, 'int');
			
			$newEndIDsArr = array();
			if ($addEndType == 'manu') {
				
				$newEndIDsArr = explode(',', str_replace('，', ',', $newEndIDs));
				if (count($newEndIDsArr) <= 0) {
					$this->error('终端编号输入不正确！');
				}
				
			} else {
				
				if (!$enumber) {
					$this->error('终端添加数量不正确！');
				}
				
				for ($i = 1; $i <= $enumber; $i++) {
					array_push($newEndIDsArr, $headerLetter . ($i >= 10 ? '00' : '000') . $i);
				}
				
			}
			
			$tendModel = D('Endpoint');
			$succCounts = $hasExistsCounts = 0;
			foreach ($newEndIDsArr as $endid) {
				
				// 检查终端ID是否已存在，如果则存在则不重复添加
				$hasExists = $tendModel->where(array('touchMainId'=>$endid))->count();
				if ($hasExists) {
					$hasExistsCounts++;
					continue;
				}
				
				
				if ($tendModel->add(array('touchMainId'=>$endid, 'touchEndPoint_GroupClassId'=>$groupclassid, 'touchEndPointSort'=>$endType))) {
					$succCounts++;
				}
			}
			
			$resultTxt = '本次成功添加 <b>' . $succCounts . '</b> 台终端';
			$resultTxt .= $hasExists > 0 ? '（存在' . $hasExistsCounts . '个重复编号未添加）.' : '.';
			
			$this->success('操作完成！' . $resultTxt . '请检查数据……');
		}
	}
	
	/**
	 * 修改终端
	 */
	public function edit() {
		if (IS_POST) {
			$this->updateEndpoint();
		} else {
			$id = I('get.tid', 0, 'int');
			
			if ($id) {
				$tendModel = D('Endpoint');
				$tend = $tendModel->where(array('tid'=>$id))->find();
				
				if ($tend) {
					$tend['touchEndPointName'] = gbk2utf8($tend['touchEndPointName']);
					$tend['touchEndPoint_ComputerName'] = gbk2utf8($tend['touchEndPoint_ComputerName']);
					$tend['touchEndPoint_DiskTotalSize'] = gbk2utf8($tend['touchEndPoint_DiskTotalSize']);
					$tend['lastShotSnap'] = basename($tend['lastShotSnap']);
					//$tend['groupName'] = D('EPGroup')->where(array('groupclassid'=>$tend['touchEndPoint_GroupClassId']))->getField('groupname');
					$tend['groupName'] = D('EndpointsGroups')->getGroupPath($tend['touchEndPoint_GroupClassId']);
					
					// 该终端的任务列表列表
					$tendTasks = D('EPTask')->where(array('touchMainId'=>$tend['touchMainId'], 'isFinished'=>0))->order('taskId desc')->select();
					if ($tendTasks) {
						foreach ($tendTasks as &$item) {
							$item['commandNote'] = gbk2utf8($item['commandNote']);
						}
					}
					
					$this->assign('tendTasks', $tendTasks);
					$this->assign('tend', $tend);	
				} else {
					$this->error('非法操作！');
				}
				
			} else {
				$this->error('非法操作！');
			}
			$this->assign('isWrite', 1);
			$this->display();
		}
	}
	
	private function updateEndpoint() {
		
		if (IS_POST) {
			$tid = I('post.tid', 0, 'int');
			$epName = I('post.epName', '', 'strip_tags');
			$epInterval = I('post.epInterval', 0, 'int');
			$epDelayTime = I('post.epDelayTime', 0, 'int');
			
			if ($tid) {
				$tendModel = D('Endpoint');
				$tend = $tendModel->where(array('tid'=>$tid))->find();
				
				if ($tend) {
					
					$taskModel = D('EPTask');
					$failed = 1;
					$errMsg = '';
					//$data = array();
					if (!empty($epName)) {
						//$data['touchEndPointName'] = utf82gbk($epName);
						
						if ($tend['touchEndPointName'] != utf82gbk($epName)) {
							if (!$taskModel->addEPTask($tend['touchMainId'], 'Command_ChangeEndpointName', $epName, '请求修改终端名称')) {
								$failed = 0;
								$errMsg += '修改终端名称请求发送失败<br>';
							}
						}
					}
					
					if (!empty($epInterval)) {
						//$data['touchEndPointInterval'] = $epInterval;
						
						if ($tend['touchEndPointInterval'] != $epInterval) {
							if (!$taskModel->addEPTask($tend['touchMainId'], 'Command_ChangeEndpointInterval', $epInterval, '请求修改终端心跳间隔')) {
								$failed = 0;
								$errMsg += '修改终端心跳间隔请求发送失败<br>';
							}
						}
					}
					
					if (!empty($epDelayTime)) {
						//$data['touchMainAdsDelayTime'] = $epDelayTime;
						
						if ($tend['touchMainAdsDelayTime'] != $epDelayTime) {
							if (!$taskModel->addEPTask($tend['touchMainId'], 'Command_ChangeTouchMainAdsDelayTime', $epDelayTime, '请求修改屏保延时时间')) {
								$failed = 0;
								$errMsg += '修改屏保延时时间请求发送失败<br>';
							}
						}
					}
					
					if ($failed) {
						$this->success('操作成功！');
					} else {
						$this->error($errMsg);
					}
					
					/* $result = $tendModel->where(array('tid'=>$tid))->save($data);
					if ($result !== false) {
						$this->success('操作成功！');
					} else {
						$this->error('操作失败！[原因]：' . $tendModel->getError());
					} */
					
				} else {
					$this->error('非法操作！');
				}
				
			} else {
				$this->error('非法操作！');
			}
			
		} else {
			$this->error('非法操作！');
		}
	}
	
	/**
	 * 批量配置
	 */
	public function multiCfg() {
		if (IS_POST) {
			$epInterval = I('post.epInterval', 0, 'int');
			$epDelayTime = I('post.epDelayTime', 0, 'int');
			$tids = trim(I('post.tids'), '-');
			
			if (!$epDelayTime || !$epInterval || empty($tids)) {
				$this->error('网络数据错误，请重试....');
			} else {
				$tidsArr = explode('-', $tids);
				if (count($tidsArr > 0)) {
					$tendModel = D('Endpoint');
					$taskModel = D('EPTask');
					$errMsg = '';
					foreach ($tidsArr as $tid) {
						$tend = $tendModel->where(array('tid'=>$tid))->find();
						if ($tend) {
							if ($tend['touchEndPointInterval'] != $epInterval) {
								if (!$taskModel->addEPTask($tend['touchMainId'], 'Command_ChangeEndpointInterval', $epInterval, '请求修改终端心跳间隔')) {
									$errMsg += $tend['touchMainId'] . ' - 修改终端心跳间隔请求发送失败<br>';
								}
							}
							
							if ($tend['touchMainAdsDelayTime'] != $epDelayTime) {
								if (!$taskModel->addEPTask($tend['touchMainId'], 'Command_ChangeTouchMainAdsDelayTime', $epDelayTime, '请求修改屏保延时时间')) {
									$errMsg += $tend['touchMainId'] . ' - 修改屏保延时时间请求发送失败<br>';
								}
							}
						}
					}
					
					if (empty($errMsg)) {
						$this->success('操作成功！');
					} else {
						$this->error('操作失败！[原因]：' . $errMsg);
					}
				} else {
					$this->error('网络数据错误，请重试....');
				}
			}
			
		} else {
			$this->error('非法操作！');
		}
	}
	
	/*
	 * 删除终端
	 */
	public function del() {
		
		if (IS_AJAX) {
			$tids = trim(I('post.tids'), '-');
			if (!empty($tids)) {
				$tidsArr = explode('-', $tids);
				if (count($tidsArr > 0)) {
					$tendModel = D('Endpoint');
					$touchMainIds = $tendModel->where(array('tid'=>array('in', $tidsArr)))->getField('touchMainId', true);
					$result = $tendModel->where(array('tid'=>array('in', $tidsArr)))->delete();
					if ($result !== false) {
						
						if (count($touchMainIds) > 0) {
							// 清除该终端的关联的任务与错误
							D('EPTask')->where(array('touchMainId'=>array('in', $touchMainIds)))->delete();
							D('EPError')->where(array('touchMainId'=>array('in', $touchMainIds)))->delete();
						}
						
						echo json_encode(array('stat'=>1));
					} else {
						echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $tendModel->getError()));
					}
				} else {
					echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
				}
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
			}
			
		} else {
			$tid = I('get.tid', 0, 'int');
			
			if ($tid) {
				$tendModel = D('Endpoint');
				$tend = $tendModel->where(array('tid'=>$tid))->find();
				if ($tend) {
					$touchMainId = $tend['touchMainId'];
					
					$result = $tendModel->where(array('tid'=>$tid))->delete();
					if ($result !== false) {
						
						// 清除该终端的关联的任务与错误
						D('EPTask')->where(array('touchMainId'=>$touchMainId))->delete();
						D('EPError')->where(array('touchMainId'=>$touchMainId))->delete();
						
						$this->success('操作成功！');
					} else {
						$this->error('操作失败！[原因]：' . $tendModel->getError());
					}

				} else {
					$this->error('非法操作！');
				}

			} else {
				$this->error('非法操作！');
			}
		}
	}
	
	/**
	 * 添加终端截图任务
	 */
	public function addEndTask() {
		
		if (IS_AJAX) {
			
			$type = I('post.type');
			$tid = I('post.tid', 0, 'int');
			
			if (empty($type) || !$tid) {
				echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
			}
			
			$tendModel = D('Endpoint');
			$tend = $tendModel->where(array('tid'=>$tid))->find();
			
			if ($tend) {
				$taskModel = D('EPTask');
				if ($type == 'capturescreen') {   // 终端截图
					if ($taskModel->addEPTask($tend['touchMainId'], 'Command_CaptureScreen', '', '请求终端截图')) {
						echo json_encode(array('stat'=>1));
					} else {
						echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $taskModel->getError()));
					}
				}
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
			}
		}
	}


	/**
	 * 添加更新终端程序任务
	 */
	public function addUPEndTask() {
		
		if (IS_AJAX) {
			
			$tendModel = D('Endpoint');
			$tends = $tendModel->getField('touchMainId', true);
			
			if ($tends) {
				$taskModel = D('EPTask');
				$failed = 0;
				foreach ($tends as $item) {
					if (!$taskModel->addEPTask($item, 'Command_PublishNewMainSoft', C('latest_upfile'), '请求更新软件版本')) {
						$failed ++;
						break;
					}
				}

				if ($failed <= 0) {
					echo json_encode(array('stat'=>1));
				} else {
					echo json_encode(array('stat'=>0, 'msg'=>'操作失败！请重试....'));
				}
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
			}
		}
	}
	
	public function soapClientWay() {
		
		// 命令列表
		$commandList  = array(
			'ZipGroupData'					=>	array('commandName'=>'Command_ZipGroupData_X86','commandNote'=>'生成最新数据'),//zjh add cs版的模板中有此命令：生成最新数据按钮
			'PublishNewMainSoft'			=>	array('commandName'=>'Command_PublishNewMainSoft','commandNote'=>'请求更新系统版本'),
			'DownLoadDataFile'				=>	array('commandName'=>'Command_DownLoadDataFile','commandNote'=>'更新数据文件'),
			'RestartMain'					=>	array('commandName'=>'Command_RestartMain','commandNote'=>'请求重启终端主程序'),
			'ShutdownMain'					=>	array('commandName'=>'Command_ShutdownMain','commandNote'=>'请求关闭终端主程序'),
			'RestartSystem'					=>	array('commandName'=>'Command_RestartSystem','commandNote'=>'请求重启终端计算机'),
			'CloseSystem'					=>	array('commandName'=>'Command_CloseSystem','commandNote'=>'请求终端关闭计算机'),
			'CaptureScreen'					=>	array('commandName'=>'Command_CaptureScreen','commandNote'=>'请求终端截图'),
			'ChangeEndpointName'			=>	array('commandName'=>'Command_ChangeEndpointName','commandNote'=>'请求修改终端名称'),
			'ChangeTouchMainCloseTime'		=>	array('commandName'=>'Command_ChangeTouchMainCloseTime','commandNote'=>'请求修改关机时间'),
			'ChangeTouchMainExitCode'		=>	array('commandName'=>'Command_ChangeTouchMainExitCode','commandNote'=>'请求修改终端关机码'),
			'ChangeEndpointInterval'		=>	array('commandName'=>'Command_ChangeEndpointInterval','commandNote'=>'请求修改终端心跳间隙'),
			'ChangeTouchMainAdsDelayTime'	=>	array('commandName'=>'Command_ChangeTouchMainAdsDelayTime','commandNote'=>'请求修改广告延迟时间'),
			'PowerOnOffTime'				=>	array('commandName'=>'Command_PowerOnOffTime','commandNote'=>'指定新的开关机计划')
		);
		
		// 表单提交参数
		$endType = trim(I('post.endType'));	// 该参数应对应终端类型
		$type = trim(I('post.type'));	// 该参数：grp-指定的组，eps-选中的终端
		$cmdKey = trim(I('post.cmd'));	// 该参数应对应命令列表的键值
		$tids = trim(I('post.tids'), '-');	// 如果type为grp，该参数的值为终端组classid;如果type为eps，该参数的值为终端id，多个用“-”连接
		
		if (empty($type) || empty($cmdKey) || empty($tids)) {
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
		if ($type == 'eps') {
			
			$tidsArr = explode('-', $tids);
			if (count($tidsArr > 0)) {
			
				$EPModel = D('Endpoint');
				foreach($tidsArr as $v) {
					$tmid = $EPModel->where(array('tid'=>$v))->getField('touchMainId');
					if (!$tmid) {
						continue;
					}
					
					try {
						
						$this->clearHistoryTask();
						
						$commandParam1 = ($cmdKey == 'PublishNewMainSoft') ? $this->getLatestUpFile($endType) : '';
						$params = array('systemName'=>C("SYSTEMNAME")/*董工加的参数，本文件后面多个params格式同此/apps/conf/madmin/config.php*/,'touchMainId'=>$tmid,'commandName'=>$commandName,'commandParam1'=>$commandParam1,'commandParam2'=>'','commandNote'=>$commandNote);
						
						$ss = $clients->EndpointTask($params);
						
					} catch (Exception $e) {
						// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					} catch(SoapFault $f) {
						// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					}
				}
			
				echo json_encode(array('stat'=>1));
				exit();
				
			} else {
				
				echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
				exit();
				
			}
				
		} elseif ($type == 'grp') {
			
			if ($tids != 'all') {
				if ($tids*1 <= 0) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
				
				$EPGModel = D('EPGroup');
				$grpClassId = $EPGModel->where(array('id'=>$tids))->getField('groupclassid');
				if (!$grpClassId) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
				
				$commandParam2 = $grpClassId;
			} else {
				$commandParam2 = 'all';
			}
			
			//下发开关机计划
			if (in_array($cmdKey,array('PowerOnOffTime'))){
				
				$powerPlan = $EPGModel->where("id=".$tids)->find();
				if ($powerPlan){
					$PowerPlanId = $powerPlan['PowerPlanId'];
					//echo json_encode(array('stat'=>1, 'msg'=>$powerPlanId));exit;
				}else{
					//失败原因：未设置开关机计划
				}
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
					//echo json_encode(array('stat'=>1, 'msg'=>$commandParam1));exit;
				}
				
				//查到本终端组的所有终端，为每一个终端发任务
				$EPModel = D('Endpoint');
				$endClassIdsArr = $EPModel->where(array('touchEndPoint_GroupClassId'=>$grpClassId))->getField('touchMainId', true);
				
				//foreach($endClassIdsArr as $endCID) {
				//	$out .= $endCID.",";
				//}
				//echo json_encode(array('stat'=>1, 'msg'=>$out));exit;//输出测试数据
				
				foreach($endClassIdsArr as $endCID) {
					try {
						$this->clearHistoryTask();
						//$commandParam1 = $commandParam1;
						$params = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>$endCID,'commandName'=>$commandName,'commandParam1'=>$commandParam1,'commandParam2'=>'','commandNote'=>$commandNote);
						$ss = $clients->EndpointTask($params);

					//	file_put_contents("debug.txt",PHP_EOL.serialize($params).PHP_EOL,FILE_APPEND);//写调试到TXT
					} catch (Exception $e) {
						// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					
					} catch(SoapFault $f) {
					
						// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					}
				}
				echo json_encode(array('stat'=>1));//zjh add
				exit();
			} 
			
			if (in_array($cmdKey, array('RestartMain','ShutdownMain','RestartSystem','CloseSystem'))) {
				
				$EPModel = D('Endpoint');
				if ($tids != 'all') {
					$endClassIdsArr = $EPModel->where(array('touchEndPoint_GroupClassId'=>$grpClassId))->getField('touchMainId', true);
				} else {
					$endClassIdsArr = $EPModel->where(1)->getField('touchMainId', true);
				}
					
				if (count($endClassIdsArr) <= 0) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
				
				foreach($endClassIdsArr as $endCID) {
				
					try {
						
						$this->clearHistoryTask();
					
						$commandParam1 = ($cmdKey == 'PublishNewMainSoft') ? $this->getLatestUpFile($endType) : '';
						$params = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>$endCID,'commandName'=>$commandName,'commandParam1'=>$commandParam1,'commandParam2'=>'','commandNote'=>$commandNote);
						$ss = $clients->EndpointTask($params);
					
					} catch (Exception $e) {
					
						// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					
					} catch(SoapFault $f) {
					
						// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
						exit();
					
					}
					
				}
				echo json_encode(array('stat'=>1));//zjh add
				exit();				
			} else {
				
				try {
					
					$this->clearHistoryTask();
						
					$commandParam1 = ($cmdKey == 'PublishNewMainSoft') ? $this->getLatestUpFile($endType) : '';
					$params = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>'','commandName'=>$commandName,'commandParam1'=>$commandParam1,'commandParam2'=>$commandParam2,'commandNote'=>$commandNote);
					//file_put_contents("debug.txt",PHP_EOL."".PHP_EOL.serialize($params).PHP_EOL,FILE_APPEND);//写调试到TXT
					
					$ss = $clients->EndpointTask($params);
					//	echo json_encode(array('stat'=>0, 'msg'=>"xxxxxxxxxxxxxxxxxxxxxxxxxxx"));exit;//生成最新数据执行的这句
				} catch (Exception $e) {
						
					// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
					echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
					exit();
						
				} catch(SoapFault $f) {
						
					// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
					echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
					exit();
						
				}
					
				
			}
			
			echo json_encode(array('stat'=>1));
			exit();
				
			
				
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
			exit();
		}
	}
	
	private function getLatestUpFile($endType = 'x86') {
		return M('syscfg')->where(array('id'=>1))->getField($endType . '_latest_upfile');
	}
	
	public function soapClientMultiWay() {
		
		// 命令列表
		$commandList  = array(
			'PublishNewMainSoft'			=>	array('commandName'=>'Command_PublishNewMainSoft','commandNote'=>'请求更新系统版本'),
			'DownLoadDataFile'				=>	array('commandName'=>'Command_DownLoadDataFile','commandNote'=>'更新数据文件'),
			'RestartMain'					=>	array('commandName'=>'Command_RestartMain','commandNote'=>'请求重启终端主程序'),
			'ShutdownMain'					=>	array('commandName'=>'Command_ShutdownMain','commandNote'=>'请求关闭终端主程序'),
			'RestartSystem'					=>	array('commandName'=>'Command_RestartSystem','commandNote'=>'请求重启终端计算机'),
			'CloseSystem'					=>	array('commandName'=>'Command_CloseSystem','commandNote'=>'请求终端关闭计算机'),
			'CaptureScreen'					=>	array('commandName'=>'Command_CaptureScreen','commandNote'=>'请求终端截图'),
			'ChangeEndpointName'			=>	array('commandName'=>'Command_ChangeEndpointName','commandNote'=>'请求修改终端名称'),
			'ChangeTouchMainCloseTime'		=>	array('commandName'=>'Command_ChangeTouchMainCloseTime','commandNote'=>'请求修改关机时间'),
			'ChangeTouchMainExitCode'		=>	array('commandName'=>'Command_ChangeTouchMainExitCode','commandNote'=>'请求修改终端关机码'),
			'ChangeEndpointInterval'		=>	array('commandName'=>'Command_ChangeEndpointInterval','commandNote'=>'请求修改终端心跳间隙'),
			'ChangeTouchMainAdsDelayTime'	=>	array('commandName'=>'Command_ChangeTouchMainAdsDelayTime','commandNote'=>'请求修改广告延迟时间'),
			'PowerOnOffTime'				=>	array('commandName'=>'Command_PowerOnOffTime','commandNote'=>'指定新的开关机计划')
		);
		
		// 表单提交参数
		$type = trim(I('post.type'));	// 该参数：grp-指定的组，eps-选中的终端
		$tids = trim(I('post.tids'), '-');	// 如果type为grp，该参数的值为终端组classid;如果type为eps，该参数的值为终端id，多个用“-”连接
		
		
		if (empty($type) || empty($tids)) {
			echo json_encode(array('stat'=>0, 'msg'=>'[ RequestData ] 网络数据错误，请刷新页面重试……'));
			exit();
		}
		
		$params = array();
		$epName = trim(I('post.epName'));
		$mainClosetime = trim(I('post.mainClosetime'));
		$tclientExitcode = trim(I('post.tclientExitcode'));
		$epInterval = I('post.epInterval', 0, 'int');
		$epDelayTime = I('post.epDelayTime', 0, 'int');
		
		if (!empty($epName)) {
			$tmp = $commandList['ChangeEndpointName'];
			$tmp['commandParam1'] = $epName;
			array_push($params, $tmp);
		}
		
		if (!empty($mainClosetime)) {
			$tmp = $commandList['ChangeTouchMainCloseTime'];
			$tmp['commandParam1'] = $mainClosetime;
			array_push($params, $tmp);
		}
		
		if (!empty($tclientExitcode)) {
			$tmp = $commandList['ChangeTouchMainExitCode'];
			$tmp['commandParam1'] = $tclientExitcode;
			array_push($params, $tmp);
		}
		
		if (!empty($epInterval)) {
			$tmp = $commandList['ChangeEndpointInterval'];
			$tmp['commandParam1'] = $epInterval;
			array_push($params, $tmp);
		}
		
		if (!empty($epDelayTime)) {
			$tmp = $commandList['ChangeTouchMainAdsDelayTime'];
			$tmp['commandParam1'] = $epDelayTime;
			array_push($params, $tmp);
		}
		
		if (count($params) <= 0) {
			echo json_encode(array('stat'=>0, 'msg'=>'[ RequestData ] 网络数据错误，请刷新页面重试……'));
			exit();
		}
		
		//  请求TServer的服务端口
		try {
			$options = array(
					'exceptions'=>true,
					'cache_wsdl'=>WSDL_CACHE_NONE
			);
			$clients = @new SoapClient('http://'.C("TSERVER_IP").':'.C("SOAP_PORT").'?wsdl', $options);//'http://localhost:5249?wsdl'
		} catch (Exception $e) {
			//echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：WSDL异常！'));
			exit();
		} catch(SoapFault $f) {
			// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：WSDL异常！'));
			exit();
		}
		
		/**
		 * 操作指定组：参数touchMainId为空，参数commandParam2为终端组CLASSID
		 * 操作指定终端：参数touchMainId为终端编号，参数commandParam2为空
		 * 所有组：参数touchMainId为空，参数commandParam2为all
		 */
		$endClassIdsArr = array();
		$EPModel = D('Endpoint');
		if ($type == 'eps') {
			$tidsArr = explode('-', $tids);
			if (count($tidsArr > 0)) {
				$endClassIdsArr = $EPModel->where(array('tid'=>array('in', $tidsArr)))->getField('touchMainId', true);
				if (count($endClassIdsArr) <= 0) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
				exit();
			}
		} else {
			if ($tids != 'all') {
				if ($tids*1 <= 0) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
			
				$EPGModel = D('EPGroup');
				$grpClassId = $EPGModel->where(array('id'=>$tids))->getField('groupclassid');
				if (!$grpClassId) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
			
				$endClassIdsArr = $EPModel->where(array('touchEndPoint_GroupClassId'=>$grpClassId))->getField('touchMainId', true);
			} else {
				$endClassIdsArr = $EPModel->where(1)->getField('touchMainId', true);
			}
			
			if (count($endClassIdsArr) <= 0) {
				echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
				exit();
			}
		}
		
		foreach($endClassIdsArr as $endCID) {
				
			foreach ($params as $param) {
				try {
					
					$this->clearHistoryTask();
						
					$params_eps = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>$endCID,'commandName'=>$param['commandName'],'commandParam1'=>$param['commandParam1'],'commandParam2'=>'','commandNote'=>$param['commandNote']);
					$ss = $clients->EndpointTask($params_eps);
		
				} catch (Exception $e) {
					// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
					echo json_encode(array('stat'=>0, 'msg'=>$param['commandNote'] . '失败...Caused by WSDL.'));
					exit();
				} catch(SoapFault $f) {
					// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
					echo json_encode(array('stat'=>0, 'msg'=>$param['commandNote'] . '失败...Caused by WSDL.'));
					exit();
				}
			}
		}
			
		echo json_encode(array('stat'=>1));
		exit();
		
		/* if ($type == 'eps') {
			
			$tidsArr = explode('-', $tids);
			if (count($tidsArr > 0)) {
			
				$EPModel = D('Endpoint');
				foreach($tidsArr as $v) {
					$tmid = $EPModel->where(array('tid'=>$v))->getField('touchMainId');
					if (!$tmid) {
						continue;
					}
					
					foreach ($params as $param) {
						try {
							
							$params_eps = array('touchMainId'=>$tmid,'commandName'=>$param['commandName'],'commandParam1'=>$param['commandParam1'],'commandParam2'=>'','commandNote'=>$param['commandNote']);
							$ss = $clients->EndpointTask($params_eps);
						
						} catch (Exception $e) {
							// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
							echo json_encode(array('stat'=>0, 'msg'=>$param['commandNote'] . '失败...Caused by WSDL.'));
							exit();
						} catch(SoapFault $f) {
							// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
							echo json_encode(array('stat'=>0, 'msg'=>$param['commandNote'] . '失败...Caused by WSDL.'));
							exit();
						}
					}
				}
			
				echo json_encode(array('stat'=>1));
				exit();
				
			} else {
				
				echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
				exit();
				
			}
				
		} elseif ($type == 'grp') {
			
			if ($tids != 'all') {
				if ($tids*1 <= 0) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
				
				$EPGModel = D('EPGroup');
				$grpClassId = $EPGModel->where(array('id'=>$tids))->getField('groupclassid');
				if (!$grpClassId) {
					echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
					exit();
				}
				
				$commandParam2 = $grpClassId;
			} else {
				$commandParam2 = 'all';
			}
				
			foreach ($params as $param) {
				try {
						
					$params_grp = array('touchMainId'=>'','commandName'=>$param['commandName'],'commandParam1'=>$param['commandParam1'],'commandParam2'=>$commandParam2,'commandNote'=>$param['commandNote']);
					$ss = $clients->EndpointTask($params_grp);
						
				} catch (Exception $e) {
						
					// echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
					echo json_encode(array('stat'=>0, 'msg'=>$param['commandNote'] . '失败...Caused by WSDL.'));
					exit();
						
				} catch(SoapFault $f) {
						
					// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
					echo json_encode(array('stat'=>0, 'msg'=>$param['commandNote'] . '失败...Caused by WSDL.'));
					exit();
						
				}
			}
				
			echo json_encode(array('stat'=>1));
			exit();
			
				
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
			exit();
		} */
	}
	
	private function clearHistoryTask() {
		$maxCounts = 1000;
		$taskModel = D('EPTask');
		$taskTotals = $taskModel->count();
		if ($taskTotals > $maxCounts) {
			$delItemIds = $taskModel->order('taskId asc')->limit($taskTotals - $maxCounts)->getField('taskId', true);
			if (count($delItemIds) > 0) {
				$taskModel->where(array('taskId'=>array('in', $delItemIds)))->delete();
			}
		}
	}
	
	/**
	 * 删除终端任务
	 */
	public function delEndTask() {
		$type = I('get.type');
		$touchMainId = I('get.touchMainId');
		$taskId = I('get.taskId', 0, 'int');
		
		if (!empty($type)) {
			
			$taskModel = D('EPTask');
			$where = null;
			
			if ($type == 'all') { // 清除终端的所有任务
				if (!empty($touchMainId)) {
					$where = array('touchMainId'=>$touchMainId, 'isFinished'=>0);
				} else {
					$this->error('非法操作！');
				}
			} elseif ($type == 'one') {  // 清除终端的单个任务
				if (!empty($taskId)) {
					$where = array('taskId'=>$taskId);
				} else {
					$this->error('非法操作！');
				}
			}
			
			$result = $taskModel->where($where)->delete();
			
			if ($result !== false) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！[原因]：' . $taskModel->getError());
			}
			
		} else {
			$this->error('非法操作！');
		}
	}
	
	public function sendNotice() {
	    
	    $type = I('post.type');
	    $tids = trim(I('post.tids'), '-');
	    $msgName = I('post.name');
	    $fontfamily = I('post.fontfamily');
	    $fontsize = I('post.fontsize');
	    $fontcolor = I('post.fontcolor');
	    $backcolor = I('post.backcolor');
	    $position = I('post.position');
	    $speed = I('post.speed');
	    $startTime = I('post.starttime');
	    $endTime = I('post.endtime');
	    $contents = strip_tags(I('post.contents'));
	    
	    if (empty($type) || empty($tids)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'网络参数错误，请刷新页面重试……')));
	    }
	    
	    if (empty($msgName) || empty($contents)) {
	        die(json_encode(array('stat'=>-1, 'msg'=>'消息名称和内容不能为空！')));
	    }
	    
	    if (strtotime($startTime) >= strtotime($endTime)) {
	        die(json_encode(array('stat'=>-1, 'msg'=>'结束时间必须大于开始时间！')));
	    }
	    
	    $noticeModel = D('NoticeMsg');
	    $data['msgname'] = $msgName;
	    $data['startdate'] = date('Y-m-d', strtotime($startTime));
	    $data['starttime'] = date('H:i:s', strtotime($startTime));
	    $data['enddate'] = date('Y-m-d', strtotime($endTime));
	    $data['endtime'] = date('H:i:s', strtotime($endTime));
	    $data['txtcontent'] = $contents;
	    $data['myfontsize'] = $fontsize;
	    $data['position'] = ucfirst($position);
	    $data['background'] = $backcolor;
	    $data['fontcolor'] = $fontcolor;
	    $data['fontfamily'] = $fontfamily;
	    $data['speed'] = $speed;
	    $data['creattime'] = date('Y-m-d H:i:s');
	    $data['modifytime'] = date('Y-m-d H:i:s');
	    
	    $insResult = $noticeModel->add($data);
	    if ($insResult !== false) {
	        
	        $latestNID = $noticeModel->getLastInsID();
	        
	        $params = null;
	        $commandName = 'Command_PublishNotice';  //  Command_StopNotice
	        $commandNote = '发送插播消息';
	        $errMsg = '发送插播消息失败...Caused by WSDL.......';
	        	
	        //  请求TServer的服务端口
	        try {
	            $options = array(
	                'exceptions'=>true,
	                'cache_wsdl'=>WSDL_CACHE_NONE
	            );
	            $clients = @new SoapClient('http://'.C("TSERVER_IP").':'.C("SOAP_PORT").'?wsdl', $options);//'http://localhost:5249?wsdl'
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
	        if ($type == 'eps') {

	            $tidsArr = explode('-', $tids);
	            	
                $EPModel = D('Endpoint');
                $tmids = $EPModel->where(array('tid'=>array('in', $tidsArr)))->getField('touchMainId', true);
                if (empty($tmids)) {
                    echo json_encode(array('stat'=>0, 'msg'=>'[ EndPointsID ] 数据请求错误，请刷新页面重试....'));
			        exit();
                }
                
                foreach ($tmids as $tmid) {
                	
                    try {
        
                        $this->clearHistoryTask();
        
                        $params = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>$tmid,'commandName'=>$commandName,'commandParam1'=>$latestNID,'commandParam2'=>'','commandNote'=>$commandNote);
                        $ss = $clients->EndpointTask($params);
						//插播命令执行的这儿
                    } catch (Exception $e) {
                        // echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
						echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));exit;//这里有错误
                        echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
                        exit();
                    } catch(SoapFault $f) {
                        // echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
                        echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
                        exit();
                    }
                }
                	
                echo json_encode(array('stat'=>1));
                exit();
	        
	        } elseif ($type == 'grp') {
	            	
	            if ($tids != 'all') {
	                if ($tids*1 <= 0) {
	                    echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
	                    exit();
	                }
	        
	                $EPGModel = D('EPGroup');
	                $grpClassId = $EPGModel->where(array('id'=>$tids))->getField('groupclassid');
	                if (!$grpClassId) {
	                    echo json_encode(array('stat'=>0, 'msg'=>'[ EndGrpID ] 数据请求错误，请刷新页面重试....'));
	                    exit();
	                }
	        
	                $commandParam2 = $grpClassId;
	            } else {
	                $commandParam2 = 'all';
	            }
	        
                try {
                    	
                    $this->clearHistoryTask();
        
                    $params = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>'','commandName'=>$commandName,'commandParam1'=>$latestNID,'commandParam2'=>$commandParam2,'commandNote'=>$commandNote);
                    $ss = $clients->EndpointTask($params);
					
                } catch (Exception $e) {
        
                    // echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
                    echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
                    exit();
        
                } catch(SoapFault $f) {
        
                    // echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
                    echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
                    exit();
        
                }
	            	
	            echo json_encode(array('stat'=>1));
	            exit();
	        
	        } else {
	            echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
	            exit();
	        }
	        
	    } else {
	        die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $noticeModel->getError())));
	    }
	}
	
	public function noticeMsg() {
	    $teptype = I('get.et', 'x86');
	    $etype = I('get.type', 'grp');

	    $noticeModel = D('NoticeMsg');
	    $noticeRecordModel = D('NoticeMsgSendRecord');
	    $where = array();
	    
	    $where['teptype'] = $teptype;

	    if ($etype == 'grp') {
	        $where['tepgroupids'] = array('neq', '');
	    } else {
	        $where['tepids'] = array('neq', '');
	    }
	    
	    // 获取停播的……
		$fieldName = $etype == 'grp' ? 'tepgroupids' : 'tepids';
	    $stopped = $noticeRecordModel->where(array_merge($where, array('noticemsgid'=>0)))->getField($fieldName, true);
	    $stopped = array_unique($stopped);
	    $stoppedIds = array();
	    foreach ($stopped as $st) {
			$latestRecord = $noticeRecordModel->where(array($fieldName=>$st, 'noticemsgid'=>0))->order('sendtime desc')->limit(1)->getField('sendtime');
			$ids = $noticeRecordModel->where(array($fieldName=>$st, 'sendtime'=>array('elt', $latestRecord)))->getField('id', true);
	        $stoppedIds = array_merge($stoppedIds, $ids);
	    }
	    
	    if (!empty($stoppedIds)) {
	        $where['id'] = array('not in', $stoppedIds);
	    }
	    
	    // 加载数据分页类
	    import('ORG.Util.Page');
	    
	    // 数据分页
	    $totals = $noticeRecordModel->where($where)->count();
	    $Page = new Page($totals, 12);
	    $show = $Page->show();
	    $this->assign('page', $show);
	    
	    $noticeRecords = $noticeRecordModel->where($where)->order('sendtime desc')->limit($Page->firstRow. ',' .$Page->listRows)->select();
	    foreach ($noticeRecords as &$record) {
	        if ($etype == 'grp' && $record['tepgroupids'] != 'all') {
	            $record['groupname'] = D('EndpointsGroups')->where(array('groupclassid'=>$record['tepgroupids']))->getField('groupname');
	        }
	        $record['noticeInfo'] = $noticeModel->where(array('id'=>$record['noticemsgid']))->find();
	        $record['noticeInfo']['short_txtcontent'] = mb_strlen($record['noticeInfo']['txtcontent'], 'UTF-8') > 28 ? mb_substr($record['noticeInfo']['txtcontent'], 0, 28) . '……' : $record['noticeInfo']['txtcontent'];
	    }
	    //dump($noticeRecords);
	    $this->assign('notices', $noticeRecords);
	    $this->assign('pager', $show);
	    $this->display();
	}
	
	public function stopNotice() {
	    $recordID = I('post.recordID', 0, 'int');
	    
	    if (!$recordID) {
	        echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
	        exit();
	    }
	    
	    $noticeRecordModel = D('NoticeMsgSendRecord');
	    $recordInfo = $noticeRecordModel->where(array('id'=>$recordID))->find();
	    if (!$recordInfo) {
	        echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
	        exit();
	    }
	    
	    $params = null;
	    $commandName = 'Command_StopNotice';
	    $commandNote = '停止播放插播消息';
	    $errMsg = '停止播放插播消息失败...Caused by WSDL.';
	    
	    //  请求TServer的服务端口
	    try {
	        $options = array(
	            'exceptions'=>true,
	            'cache_wsdl'=>WSDL_CACHE_NONE
	        );
	        $clients = @new SoapClient('http://localhost:5249?wsdl', $options);//
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
	    $type = trim($recordInfo['tepgroupids']) != '' ? 'grp' : 'eps';
	    if ($type == 'eps') {
	    
            try {
    
                $this->clearHistoryTask();
    
                $params = array('touchMainId'=>$recordInfo['tepids'],'commandName'=>$commandName,'commandParam1'=>'','commandParam2'=>'','commandNote'=>$commandNote);
                $ss = $clients->EndpointTask($params);
    
            } catch (Exception $e) {
                // echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
                echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
                exit();
            } catch(SoapFault $f) {
                // echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
                echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
                exit();
            }
	         
	        echo json_encode(array('stat'=>1));
	        exit();
	         
	    } elseif ($type == 'grp') {
	         
	        try {
	             
	            $this->clearHistoryTask();
	    
	            $params = array('systemName'=>C("SYSTEMNAME"),'touchMainId'=>'','commandName'=>$commandName,'commandParam1'=>'','commandParam2'=>$recordInfo['tepgroupids'],'commandNote'=>$commandNote);
	            $ss = $clients->EndpointTask($params);
	    
	        } catch (Exception $e) {
	    
	            // echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
	            echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
	            exit();
	    
	        } catch(SoapFault $f) {
	    
	            // echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
	            echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
	            exit();
	    
	        }
	    
	        echo json_encode(array('stat'=>1));
	        exit();
	         
	    } else {
	        echo json_encode(array('stat'=>0, 'msg'=>'非法操作！'));
	        exit();
	    }
	    
	}
}