<?php
class PlanAction extends CommonAction {
    
	// ****************** 开关机计划 START
	
    /**
     * 开关机计划列表
     */
    public function PowerPlanIndex() {      
		 
		$powerPlanModel = D("PowerPlan");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容

		$map = array();
		$map['id'] = array('GT',0);
		
        // 加载数据分页类
        import('ORG.Util.Page');
        
        // 数据分页
        $totals = $powerPlanModel->where($map)->count();
        $Page = new Page($totals, 10);
        $show = $Page->show();
        $this->assign('page', $show);	
		
		$datas = $powerPlanModel->where($map)->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select(); 
		foreach ($datas as $k=>$v){
			$count = $powerPlanDetailModel->where("PowerPlanId=".$v['Id'])->count();
			$datas[$k]['Count'] = $count;
			
			$datas_time = $powerPlanDetailModel->where("PowerPlanId=".$v['Id'])->order("OnTime ASC")->select();
			$tmp_time = array();
			foreach($datas_time as $kk=>$vv){
				$tmp_time[] = $vv['OnTime']."-".$vv['OffTime'];
			}
			$tmp_time_str = implode(",",$tmp_time);
			$datas[$k]['timeList'] = $tmp_time_str;//时间段列表
		}
		
		$this->assign('datas', $datas);
		$this->display("Plan/PowerPlanIndex");
    }		
	
    /**
     * 新增开关机计划
     */
    public function AddPowerPlan() {  	
		if (IS_POST) {
			$this->SavePowerPlan();
		} else {
			$planType = "week";//现阶段只有周计划
			$this->display("Plan/editPowerPlanWeek");
		}
    }	
	
    /**
     * 修改开关机计划
     */
    public function EditPowerPlan() {  	
		if (IS_POST) {
			$operation = $_POST[operation];
			$data[id] = $_POST[PlanId];
			$data[Name] = $_POST[title];
			$data[Type] = $_POST[type];
			if($operation != 'datePowerPlanUpdate'){
				$this->SavePowerPlan();
			}else{
				$powerPlanModel = M()->table('TB_PowerOnOffPlans');
				$r = $powerPlanModel->create($data);

				// if($powerPlanModel->where("Id=$data[Id]")->save($data)){
				if($powerPlanModel->save($data)){
					$this->success('操作成功！', U('Plan/PowerPlanIndex'));
				}else{
					$this->error('操作失败！');
				}
			}

		} else {
			$id = I("request.PlanId",0,"intval");
			
			$powerPlanModel = D("PowerPlan");//开关机计划
			$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
			
			if ($id){
				//开关机计划
				$datas_plan = $powerPlanModel->where("id=".$id)->find();//var_dump($datas);
				$PowerPlanId = $datas_plan['id'];
				$planType = $datas_plan['Type'];
			}else{
				$this->error("未找到记录");
			}	
			
			switch ($planType){
				case "week":
					//周计划
					if ($datas_plan){
						$powerWeekModel = D("PowerPlanWeek");
						$powerDayArrangeModel = D("PowerDayArrange");
						$powerDayArrangeDetailModel = D("PowerDayArrangeDetail");
						$datas_week = $powerWeekModel->where("PowerPlanId=".$PowerPlanId)->select();//最多七条记录
						
						//遍历这七条，找到对应的日安排
						foreach ($datas_week as $k=>$v){
							$data_tmp = $powerDayArrangeModel->where("id=".$v['PowerDayArrangeId'])->find();//var_dump($data_tmp);
							if ($data_tmp){
								$datas_week[$k]['Name'] = $data_tmp['Name'];
								
								//找到此日安排的详细
								$datas_detail = $powerDayArrangeDetailModel->where("PowerDayArrangeId=".$v['PowerDayArrangeId'])->select();
								foreach ($datas_detail as $kk=>$vv){
									if ($vv['TplClassId']){
										$datas_tpl = $tplsModel->where("tplclassid='".$vv['TplClassId']."'")->find();
										if ($datas_tpl){
											$datas_detail[$kk]['tplName'] = $datas_tpl['tplname'];
										}
									}
								}
								$datas_week[$k]['datas_detail'] = $datas_detail;
							}
						}
						
					}
					$this->assign('datas_week', $datas_week);	
					
					$powerDayArrangeModel = D("PowerDayArrange");
					$datas_day = $powerDayArrangeModel->order("Id DESC")->select();
					$this->assign('datas_day', $datas_day);						
					
					$this->assign('datas', $datas_plan);		//var_dump($datas_plan);							
					$this->display("editPowerPlanWeek");
					break;
				case "date":
					$planId = $_GET[PlanId] ? : die('参数有误！');
					$this->plan = M()->table('TB_PowerOnOffPlans')->find($planId);
					$this->dayArranges = M()->table('TB_PowerDayArranges')->select();

			        import('ORG.Util.Page');
			        $total = M()->table('TB_PowerDatePlans')->where("powerPlanId=$planId")->count();
			        $Page = new Page($total, 10);
			        $this->show = $Page->show();
					$powerMonthPlans = M()->table('TB_PowerDatePlans')->where("powerPlanId=$planId")->order('beginDate')->limit($Page->firstRow.','.$Page->listRows)->select();
					foreach($powerMonthPlans as &$value){
						$value[powerDayArrangeName] = M()->table('TB_PowerDayArranges')->where("id=$value[dayArrangeId]")->getField('Name');
					}
					$this->powerMonthPlans = $powerMonthPlans;
					$this->display("editPowerPlanDate");
					break;
				default:
					;
			}			
		}
    }	
	
    /**
     * 保存开关机计划
     */
    public function SavePowerPlan() {
		$id = I("post.PlanId",0,"intval");
		$name = I("post.title",0,"trim");
		$from = I("post.from",0,"trim");//来源，主要用于识别ajax提交
		$type = $_POST[type];
		// $planType = "week";

		$powerPlanModel = D("PowerPlan");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
		
		$data = array();
		
		$funcName = '';
		if ($id) {
			$data['Id'] = $id;
			$data[Type] = $type;
			$funcName = 'save';
		} else {
			$data[Type] = $type;
			$funcName = 'add';
		}	

		$data['Name'] = $name;	
		$result = D("PowerPlan")->where("Id=$data[Id]")->$funcName($data);
		$newPlayPlanId = $result;

		//新增七条记录：星期日至星期六（这儿其实应该要用事务处理保证全部成功，否则回滚）
		$powerWeekModel = D("PowerPlanWeek");

		for ($i=0;$i<7;$i++){
			$data = array();
			$data['PowerPlanId'] = $newPlayPlanId;
			$data['Week'] = $i;
			$data['PowerDayArrangeId'] = 0;
			$result2 = $powerWeekModel->add($data);
			// file_put_contents("debug.txt",PHP_EOL."debug:".PHP_EOL.serialize($data).PHP_EOL,FILE_APPEND);//
		}

		if ($from == 'ajax'){
			$data = array();
			
			if ($result !== FALSE) {
				$data = $result;
				die(json_encode(array("stat"=>"1","msg"=>'操作成功',"data"=>$data)));
			} else {
				die(json_encode(array("stat"=>"0","msg"=>'操作失败',"data"=>$data)));
			}
			exit;
		}else{
			if ($result !== FALSE) {
				$this->success('操作成功！', U('Plan/PowerPlanIndex'));
			} else {
				$this->error('操作失败！[原因]：' . $model->getError());
			}	
		}	
	
    }	
	
	/**
	 * 更新星期几的日安排字段 DayArrangeId
	*/
	public function updatePowerPlanWeekData(){
		
		$PowerPlanId = I("post.PlanId",0,"intval");;
		$week = I("post.week",0,"intval");;
		$dayArrangeId = I("post.dayArrangeId",0,"intval");
		
		//检测有效性
		if (!$PowerPlanId || !$dayArrangeId){
			echo json_encode(array("stat"=>"0","msg"=>"参数错误","data"=>""));exit;	
		}
		
		$playPlanWeekModel = D("PowerPlanWeek");		
		
		$result = $playPlanWeekModel->where("PowerPlanId=".$PowerPlanId ." and Week=".$week)->setField('PowerDayArrangeId',$dayArrangeId);
		
		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"更新成功","data"=>""));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"更新失败","data"=>""));exit;
		}
	}	
	
	
	/*
	 * 删除开关机
	*/
	public function delPowerPlan(){
		$planId = I("request.PlanId","","intval");
		
		$powerPlanModel = D("PowerPlan");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
		
		//这里实际上应该考虑权限，否则通过构造地址都全部可以删除，不过暂时不验证
		
		$result = $powerPlanDetailModel->where("PowerPlanId=".$planId)->delete();//删除时间段
		if ($result != false){
			$powerPlanModel->where("Id=".$planId)->delete();//删除计划
		}
		
		
		if ($result !== FALSE) {
			//$insertId = $result;
			echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
		} else {
			echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
		}		
	}		
	
	
	
	/**
	 * 批量删除开关机计划
	 * zjh	 
	*/
	public function multiDelPowerPlan(){
		$Ids = trim(I('request.nids'), ',');//去掉结尾的;
		$aidsArr = explode(',', $Ids);//转成数组
	
		if (empty($Ids)){
			echo json_encode(array("stat"=>"0","msg"=>"操作失败，未指定参数","data"=>$data));exit;
		}	

		$powerPlanModel = D("PowerPlan");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
	
		$result = $powerPlanDetailModel->where(array('PowerPlanId'=>array('in', $Ids)))->delete();//删除时间段
		$result = $powerPlanModel->where(array('Id'=>array('in', $Ids)))->delete();//删除计划（方案）


		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>$data));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>$data));exit;
		}	
	}	
	
	/**
	 * 开关机日安排列表
	*/
	public function powerDayArranges(){
		$dayArrangeModel = D("PowerDayArrange");//日安排
		$dayArrangeDetailModel = D("PowerDayArrangeDetail");//日安排详细
		
		//搜索条件
		$keyboard = trim(I('request.keyboard',"","trim"));
		$tplType = trim(I('request.tplType',"","trim"));
		$resolution = trim(I('request.resolution',"","trim"));
		
		$this->assign('keyboard', $keyboard);
		$this->assign('planType', $planType);
		$this->assign('tplType', $tplType);
		$this->assign('resolution', $resolution);

		$map = array();
		$map['id'] = array('GT',0);
		if ($keyboard){$map['Name']=array('LIKE','%'.$keyboard.'%');}
		if ($tplType){$map['TplType'] = $tplType;}
		if ($resolution){$map['Resolution'] = $resolution;}

        // 加载数据分页类
        import('ORG.Util.Page');
        
        // 数据分页
        $totals = $dayArrangeModel->where($map)->count();
        $Page = new Page($totals, 10);
        $show = $Page->show();
        $this->assign('page', $show);
		$datas = $dayArrangeModel->where($map)->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select(); 
		foreach ($datas as $k=>$v){
			$count = $dayArrangeDetailModel->where("PowerDayArrangeId=".$v['id'])->count();
			$datas[$k]['Count'] = $count;
			
			$datas_time = $dayArrangeDetailModel->where("PowerDayArrangeId=".$v['id'])->order("id ASC")->select();
			$tmp_time = array();
			foreach($datas_time as $kk=>$vv){
				$tmp_time[] = $vv['OnTime']."-".$vv['OffTime'];
			}
			$tmp_time_str = implode(",",$tmp_time);
			$datas[$k]['timeList'] = $tmp_time_str;//时间段列表
			
		}

		$publicVisualModel = D("PublicVisual");//公共虚拟模型
		$res = $publicVisualModel->getAllResolution();
		$this->assign('res', $res);//分辨率
		
		$this->assign('datas', $datas);	//var_dump($datas);
		
		$this->display("powerDayArranges");		
		
	}	
	
	
	/**
	 * 新增日安排
	*/
	public function addPowerDayArrange(){
		if (IS_POST) {
			$this->savePowerDayArrange();
		} else {
			$dayArrangeModel = D("PowerDayArrange");
			
			$this->display("Plan/editPowerDayArrange");
		}
	}	
	
	/**
	 * 修改开关机日安排
	*/
	public function editPowerDayArrange(){
		if (IS_POST) {
			$this->savePowerDayArrange();
		} else {
			$id = I("request.id",0,"intval");$this->assign('id', $id);
			
			$dayArrangeModel = D("PowerDayArrange");
			$tplsModel = D("Tpls");
			
			if ($id){
				//日安排
				$datas = $dayArrangeModel->where("id=".$id)->find();//var_dump($datas);
				
			}else{
				$this->error("未找到记录");
			}
			
			//日安排详细
			$dayArrangeDetailModel = D("PowerDayArrangeDetail");
			$map = array();
			$map['PowerDayArrangeId'] = $id;
			$datas_detail = $dayArrangeDetailModel->where($map)->order("OnTime ASC,Id ASC")->select();
		//	$datas = $dayArrangeDetailModel->table("TB_Tpls")->where($map)->field("TB_Sch_Emergencies.id,TB_Sch_Emergencies.roomId,TB_Sch_Room.name as roomName,convert(VARCHAR(24),TB_Sch_Emergencies.beginTime,120) as starttime,convert(VARCHAR(24),TB_Sch_Emergencies.endTime,120) as endtime")->join("LEFT /*RIGHT*/ JOIN TB_Sch_Room ON TB_Sch_Emergencies.roomId = TB_Sch_Room.id")->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select();
			
			$this->assign('datas_detail', $datas_detail);

			$default = $dayArrangeDetailModel->where("PowerDayArrangeId=".$id)->order("OnTime DESC")->find();
			if($default != false){
				$begin = $default['OnTime'];
				$hms = explode(':',$begin); //var_dump($hms);
				$this->assign('h', $hms[0]);//时 默认选中
				$this->assign('m', $hms[1]);//分 默认选中
				$this->assign('s', $hms[2]);//秒 默认选中	
				
				$this->assign('tplclassid', $default['TplClassId']);//默认选中上一个		

			}else{
				$hms = explode(':',$begin); 
				$this->assign('h', "00");//时 默认选中
				$this->assign('m', "00");//分 默认选中
				$this->assign('s', "00");//秒 默认选中	
				
				//$this->assign('tplclassid', $default['TplClassId']);//默认选中上一个			
			}
			$this->assign('datas', $datas);//var_dump($datas);
			$this->display("editPowerDayArrange");				
		}		
	}	

	/**
	 * 存储开关机日安排
	*/
	public function savePowerDayArrange(){
		$id = I("request.id",0,"intval");
		$from = I("request.from",0,"trim");//来源，主要用于识别ajax提交
		$name = I("request.title",0,"trim");
				
		$powerPlanWeekModel = D("PowerPlanWeek");//周计划
//		$playPlanDateModel = D("PlayPlanDate");//日计划
		$dayArrangeModel = D("PowerDayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		$data = array();
		$funcName = '';
		if ($id) {
			$data['Id'] = $id;
			$funcName = 'save';
		} else {
			$funcName = 'add';
		}
		$data['Name'] = $name;
		// 执行操作
		$dayArrangeModel = D("PowerDayArrange");//日安排
		$result = $dayArrangeModel->$funcName($data);
		if ($from == 'ajax'){
			if ($result !== FALSE) {
				if ($id){
					$data = $id;
				}else{
					$data = $result;
				}
				die(json_encode(array("stat"=>"1","msg"=>'操作成功',"data"=>$data)));
			} else {
				die(json_encode(array("stat"=>"0","msg"=>'操作失败',"data"=>$data)));
			}
		}else{
			if ($result !== FALSE) {
				$this->success('操作成功！', U('Plan/powerDayArranges'));
			} else {
				$this->error('操作失败！[原因]：' . $model->getError());
			}	
		}
	}
	
	/**
	 * 删除开关机日安排
	*/
	public function multiDelPowerDayArrange(){
		$Ids = trim(I('request.nids'), ',');//去掉结尾的;
		$aidsArr = explode(',', $Ids);//转成数组
	
		if (empty($Ids)){
			echo json_encode(array("stat"=>"0","msg"=>"操作失败，未指定参数","data"=>$data));exit;
		}	

		$PowerDayArrangeModel = D("PowerDayArrange");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
	
		$result = $powerPlanDetailModel->where(array('PowerPlanId'=>array('in', $Ids)))->delete();//删除时间段
		$result = $PowerDayArrangeModel->where(array('Id'=>array('in', $Ids)))->delete();//删除计划（方案）

		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>$data));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>$data));exit;
		}			
	}
	
	
	/*
	 * 删除开关机时段
	*/
	public function delPowerPlanDetail(){
		$planId = I("request.PlanId","","intval");
		$planDetailId = I("request.PlanDetailId","","intval");
		
		$powerPlanModel = D("PowerPlan");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
		
		//这里实际上应该考虑权限，否则通过构造地址都全部可以删除，不过暂时不验证
		
		$result = $powerPlanDetailModel->where("Id=".$planDetailId." and PowerPlanId=".$planId)->delete();
		if ($result !== FALSE) {
			//$insertId = $result;
			echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
		} else {
			echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
		}		
	}	
	
	
	/*
	 * 新增开关机计划时间段
	*/
	public function addPowerPlanDetails(){
		$PlanId = I("request.PlanId","","intval");
		$planDetailId = I("request.PlanDetailId","","intval");
		$start = I("request.start","","trim");//格式：08:08:08
		$end   = I("request.end","","trim");//格式：08:08:08
		
		//转为时间戳
		$start_d = strtotime($start);
		$end_d	 = strtotime($end);
		
		if (empty($start)){
			$out = array("stat"=>0,"msg"=>"开机时间未指定");
			die(json_encode($out));		
		}
		if (!$end){
			$out = array("stat"=>0,"msg"=>"关机时间未指定");
			die(json_encode($out));		
		}
		
		if ($start_d > $end_d){
			//开始时间大于等于结束时间
			$out = array("stat"=>0,"msg"=>"开机时间大于关机时间，请指定合理的时间安排");
			die(json_encode($out));			
		}

		if ($start_d == $end_d){
		//开始时间等于结束时间
			$out = array("stat"=>0,"msg"=>"开机时间等于关机时间，请指定合理的时间安排");
			die(json_encode($out));			
		}		
		
		
		
		
		$powerPlanModel = D("PowerPlan");//开关机计划
		$powerPlanDetailModel = D("PowerPlanDetail");//开关机计划内容
		
		//防止时间重复
		$sameData = $powerPlanDetailModel->where("PowerPlanId=".$PlanId ." and OnTime = '".$start."'")->find();
		if ($sameData != false){
			$out = array("stat"=>0,"msg"=>"开机时间重复");
			die(json_encode($out));		
		}
		
		//检测有效性
		
		
		
		//检测时间交叉（一个日安排下的日安排详细范围内检测）
		
		$countDetail = $powerPlanDetailModel->where("PowerPlanId=".$PlanId)->count();
		if ($countDetail){
			$datas_detail = $powerPlanDetailModel->where("PowerPlanId=".$PlanId)->select();
			foreach ($datas_detail as $k => $v){
				if ( (strtotime($v['OnTime']) < $start_d) && ($start_d < strtotime($v['OffTime'])) ){
					$out = array("stat"=>0,"msg"=>"指定的开机时间在其它时间段内");
					echo json_encode($out);exit;
					break;
				}
				if ( (strtotime($v['OnTime']) < $end_d) && ($end_d < strtotime($v['OffTime'])) ){
					$out = array("stat"=>0,"msg"=>"指定的关机时间在其它时间段内"); 
					echo json_encode($out);exit;
					break;
				}
				//以上两种判断，不包括边界重复，因为边界重复是允许的				
			}
		}
		
		
		//入库
		$data['PowerPlanId'] = $PlanId;//
		$data['OnTime'] = $start;
		$data['OffTime'] = $end;
		$result = $powerPlanDetailModel->data($data)->add();
		
		if ($result !== FALSE) {
			//$insertId = $result;
			echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
		} else {
			echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
		}
		
	}
	
	
	/*
	 * 新增开关机日安排详细
	*/
	public function addPowerDayArrangeDetails(){
		$dayArrangeId = I("post.dayArrangeId","","intval");
		$beginTime = I("post.beginTime","","trim");
		$endTime = I("post.endTime","","trim");

		if(strtotime($beginTime) >= strtotime($endTime)){
			die('关机时间不得小于开机时间！');
		}
		if(!$dayArrangeId){
			die('单日安排id有误！');		
		}
		
		$allPowerDayArrange = D("PowerDayArrangeDetail")->where("PowerDayArrangeId=$dayArrangeId")->select();
		foreach($allPowerDayArrange as &$powerDayArrange){
			$powerDayArrange[beginStamp] = strtotime($powerDayArrange[OnTime]);
			$powerDayArrange[endStamp] = strtotime($powerDayArrange[OffTime]);
			$beginStamp[] = $powerDayArrange[beginStamp];
			$endStamp[] = $powerDayArrange[endStamp];
			if(strtotime($beginTime) >= $powerDayArrange[beginStamp] and strtotime($beginTime) <= $powerDayArrange[endStamp]){
				die('开机时间与现有时间段重叠！');
			}
			if(strtotime($endTime) >= $powerDayArrange[beginStamp] and strtotime($endTime) <= $powerDayArrange[endStamp]){
				die('关机时间与现有时间段重叠！');
			}
			if(strtotime($beginTime) <= $powerDayArrange[beginStamp] and strtotime($endTime) >= $powerDayArrange[endStamp]){
				die('当前设定的时间段将包含现有时间段！');
			}
		}
		foreach($endStamp as $end){
			if(strtotime($beginTime) >= $end and strtotime($beginTime) <= $end * 1 + 60 * 5){
				die('关机后5分钟内无法设置开机时间！');
			}
		}
		foreach($beginStamp as $begin){
			if(strtotime($endTime) <= $begin and strtotime($endTime) >= $begin - 60 * 5){
				die('当前设定的关机时间距离已有的开机时间不足5分钟，请重新设置！');
			}
		}
		$data['PowerDayArrangeId'] = $dayArrangeId;
		$data['OnTime'] = $beginTime;
		$data['OffTime'] = $endTime;
		$result = D("PowerDayArrangeDetail")->data($data)->add();
		$result ? die('1') : die('数据新增失败！');
	}	
	
	/*
	 * 删除日安排详细
	*/
	public function delPowerDayArrangeDetails(){
		$dayArrangeId = I("request.dayArrangeId","","intval");
		$dayArrangeDetailId = I("request.dayArrangeDetailId","","intval");
		
		$dayArrangeModel = D("PowerDayArrange");//日安排
		$dayArrangeDetailModel = D("PowerDayArrangeDetail");//日安排详细
		
		//file_put_contents("debug.txt",PHP_EOL."debug:".PHP_EOL.$dayArrangeDetailId//.PHP_EOL,FILE_APPEND);//
		
		//这里实际上应该考虑权限，否则通过构造地址都全部可以删除，不过暂时不验证
		
		$result = $dayArrangeDetailModel->where("Id=".$dayArrangeDetailId." and PowerDayArrangeId=".$dayArrangeId)->delete();
		if ($result !== FALSE) {
			//$insertId = $result;
			echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
		} else {
			echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
		}		
	}	
	
	
	/*
	 * 删除日安排
	*/
	public function delPowerDayArranges(){
		$dayArrangeModel = D("PowerDayArrange");//日安排
		$dayArrangeDetailModel = D("PowerDayArrangeDetail");//日安排详细
		
		$playPlanWeekModel = D("PowerPlanWeek");//周计划
	//	$playPlanDateModel = D("PowerPlanDate");//日计划
		
		$id = I("request.id",0,"trim");
		if ($id){
			$datas = $dayArrangeModel->where("Id=".$id)->find();
			if ($datas){
				
				//用到此日安排的周计划归0
				$playPlanWeekModel->where("DayArrangeId=".$id)->setField('DayArrangeId',0);
				//用到此日安排的日计划归0
//				$playPlanDateModel->where("DayArrangeId=".$id)->setField('DayArrangeId',0);
				
				$result = $dayArrangeDetailModel->where("DayArrangeId=".$id)->delete();
				//if ($result){
					$dayArrangeModel->where("Id=".$id)->delete();	
					$this->success("删除成功");
				//}else{
				//	$this->error("删除失败...");
				//}
			}else{
				$this->error("无此记录");	
			}
		}else{
			$this->error("参数错误");	
		}
	}
	
	/**
	 * 删除日安排（批量）
	*/
	public function multiDelPowerDayArranges(){
		
		$artiClassIDs = trim(I('request.aids'), ',');//去掉结尾的;
		$aidsArr = explode(',', $artiClassIDs);//转成数组
	
			
		if (empty($artiClassIDs)){
			echo json_encode(array("stat"=>"0","msg"=>"操作失败，未指定参数","data"=>$data));exit;
		}	
	
		$dayArrangeModel = D("PowerDayArrange");//日安排
		$dayArrangeDetailModel = D("PowerDayArrangeDetail");//日安排详细
		
		$playPlanWeekModel = D("PowerPlanWeek");//周计划
//		$playPlanDateModel = D("PowerPlanDate");//日计划
	
		//用到此日安排的周计划归0
		$playPlanWeekModel->where(array('DayArrangeId'=>array('in', $artiClassIDs)))->setField('DayArrangeId',0);
		//用到此日安排的日计划归0
		$playPlanWeekModel->where(array('DayArrangeId'=>array('in', $artiClassIDs)))->setField('DayArrangeId',0);
	
		$result = $dayArrangeDetailModel->where(array('DayArrangeId'=>array('in', $artiClassIDs)))->delete();
		$result = $dayArrangeModel->where(array('Id'=>array('in', $artiClassIDs)))->delete();

		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>$data));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>$data));exit;
		}	
		
	}	

	/**
	 * 位于修改开关机计划页面的新增开关机月计划
	 * 该操作实质是将开关机单日安排与开关机计划绑定上
	 * 所写入的表为TB_PowerDatePlans
	 * 2016-12-27添加6条验证规则
	 * 2016-12-28完善各条规则，圆满
	 * 将新增和修改功能合并，函数名称待调整addOrEditPowerMonthPlan
	 */
	public function addOrEditPowerMonthPlan(){
		foreach($_POST[formData] as $k => $v){
			$data[$v[name]] = $v[value];
		}
		$powerOnOffTimes = M()->table('TB_PowerDayArrangeDetails')->where("powerDayArrangeId=$data[dayArrangeId]")->select();
		for($i = 0; $i <= (strtotime($data[endDate]) - strtotime($data[beginDate])) / 3600 / 24; $i++){
			foreach($powerOnOffTimes as $onOff){
				$tmp[on] = date('Y-m-d ', strtotime($data[beginDate]) + 3600 * 24 * $i) . $onOff[OnTime];
				$tmp[off] = date('Y-m-d ', strtotime($data[beginDate]) + 3600 * 24 * $i) . $onOff[OffTime];
				$allDatetimeStrArr[] = $tmp;
			}
		}

		$alreadySetDate = M()->table('TB_PowerDatePlans')->where("powerPlanId=$data[powerPlanId] and id!=$data[id]")->select();
		foreach($alreadySetDate as $value){
			$alreadyOnOff = M()->table('TB_PowerDayArrangeDetails')->where("PowerDayArrangeId=$value[dayArrangeId]")->find();
			$tmp1[on] = $value[beginDate] . ' ' . $alreadyOnOff[OnTime];
			$tmp1[off] = $value[endDate] . ' ' . $alreadyOnOff[OffTime];
			$alreadyAllDatetimeStrArr[] = $tmp1;
		}
		foreach($alreadyAllDatetimeStrArr as $alreadyDatetime) {
			for($j = 0; $j <= intval((strtotime($alreadyDatetime[off]) - strtotime($alreadyDatetime[on])) / 3600 / 24); $j++){
				$tmp2[on] = date('Y-m-d H:i:s', (strtotime($alreadyDatetime[on]) + 3600 * 24 * $j));
				$tmp2[off] = date('Y-m-d H:i:s', strtotime(date('Y-m-d ', strtotime($alreadyDatetime[on])).date('H:i:s', strtotime($alreadyDatetime[off]))) + 3600 * 24 * $j);
				$alreadyCouple[] = $tmp2;
				$alreadyAllOn[] = date('Y-m-d H:i:s', (strtotime($alreadyDatetime[on]) + 3600 * 24 * $j));
				$alreadyAllOff[] = date('Y-m-d H:i:s', (strtotime($alreadyDatetime[off]) + 3600 * 24 * $j));
			}
		}
		// Rule No.1
		if(strtotime($data[endDate]) < strtotime($data[beginDate])){
			die(json_encode(array('stat' => '0', 'msg' => '关机日期不得小于开机日期！')));
		}
		foreach($alreadyCouple as $couple){
			foreach($allDatetimeStrArr as $postDatetime){
				// Rule No.2
				if(strtotime($postDatetime[on]) <= strtotime($couple[on]) and strtotime($postDatetime[off]) >= strtotime($couple[off])){
					die(json_encode(array('stat' => '0', 'msg' => '您所设定的开关机计划包含既有开关机计划，请重新设定！')));
				}
				// Rule No.3
				if(strtotime($postDatetime[on]) >= strtotime($couple[on]) and strtotime($postDatetime[on] <= strtotime($couple[off]))){
					die(json_encode(array('stat' => '0', 'msg' => '您所设定的开机日期时间与既有开关机日期时间对重叠！请重新设定！')));
				}
				// Rule No.4
				if(strtotime($postDatetime[off]) >= strtotime($couple[on]) and strtotime($postDatetime[off] <= strtotime($couple[off]))){
					die(json_encode(array('stat' => '0', 'msg' => '您所设定的关机日期时间与既有开关机日期时间对重叠！请重新设定！')));
				}
			}
		}
		foreach($alreadyAllOff as $off){
			foreach($allDatetimeStrArr as $postDt){
				// Rule No.5
				if(strtotime($postDt[on]) >= strtotime($off) and strtotime($postDt[on]) <= strtotime($off) + 60 * 5){
					die(json_encode(array('stat' => '0', 'msg' => '您所设定的开机日期时间与既有的关机日期时间间隔不足5分钟！请重新设定！')));
				}
			}
		}
		foreach($alreadyAllOn as $on){
			foreach($allDatetimeStrArr as $postDt){
				// Rule No.6
				if(strtotime($postDt[off]) <= strtotime($on) and strtotime($postDt[off]) >= strtotime($on) - 60 * 5){
					die(json_encode(array('stat' => '0', 'msg' => '您所设定的关机日期时间与既有的开机日期时间间隔不足5分钟！请重新设定！')));
				}
			}
		}
		if($data[id]){
			$funcName = 'save';
			$msgS = '数据更新成功！';
		}else{
			$funcName = 'add';
			unset($data[id]);
			$msgS = '新增数据成功！';
		}
		$powerDatePlanModel = M()->table('TB_PowerDatePlans');
		$dObj = $powerDatePlanModel->create($data);
		if($powerDatePlanModel->$funcName()){
			die(json_encode(array('stat' => '1', 'msg' => $msgS)));
		}else{
			die(json_encode(array('stat' => '0', 'msg' => $powerDatePlanModel->getDbError())));
		}
	}

	/**
	 * 位于修改开关机计划页面的删除开关机月计划
	 */
	public function delPowerMonthPlan(){
		$powerMonthPlanId = $_POST[powerMonthPlanId];
		if(M()->table('TB_PowerDatePlans')->delete($powerMonthPlanId)){
			die('1');
		}else{
			die('0');
		}
	}
	
	// ****************** 开关机计划 end *********************************************************************************************
	
	
///////////////////////////////////////////////播放计划 START///////////////////////////////////	
	/**
	 * 播放计划列表
	*/
	public function playPlans(){
		$playPlanModel = D("PlayPlan");

		$playPlanDateModel = D("PlayPlanDate");//日计划
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		$playPlanWeekModel = D("PlayPlanWeek");
		
		//搜索条件
		$keyboard = trim(I('request.keyboard',"","trim"));
		$planType = trim(I('request.planType',"","trim"));
		$tplType = trim(I('request.tplType',"","trim"));
		$resolution = trim(I('request.resolution',"","trim"));
		
		$this->assign('keyboard', $keyboard);
		$this->assign('planType', $planType);
		$this->assign('tplType', $tplType);
		$this->assign('resolution', $resolution);

		$map = array();
		$map['Id'] = array('GT',0);
		if ($keyboard){$map['Name']=array('LIKE','%'.$keyboard.'%');}
		if ($planType){$map['PlanType'] = $planType;}
		if ($tplType){$map['TplType'] = $tplType;}
		if ($resolution){$map['Resolution'] = $resolution;}
		
        // 加载数据分页类
        import('ORG.Util.Page');
        
        // 数据分页
        $totals = $playPlanModel->where($map)->count();
        $Page = new Page($totals, 10);
        $show = $Page->show();
        $this->assign('page', $show);
		
		$datas = $playPlanModel->where($map)->field("Id,Name,PlanType,TplType,Resolution,convert(VARCHAR(24),CreateTime,120) as CreateTime,convert(VARCHAR(24),LastTime,120) as LastTime")->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select(); 
		foreach($datas as $k=>$v){
			if ($v['PlanType'] == "week"){
				$wid = $playPlanWeekModel->where("PlayPlanId = ".$v['Id'])->field("Id")->find();//周计划
				if (!$wid){
					$datas[$k]['status'] = '0';
				}else{
					//判断周计划设置了几天
					$week_day_count = $playPlanWeekModel->where("DayArrangeId > 0 and PlayPlanId = ".$v['Id'])->count();
					$datas[$k]['seteddays'] = $week_day_count;
				}
			}else{
				
				//统计本计划方案下添加了多少个日计划
				$days_count = $playPlanDateModel->where("PlayPlanId=".$v['Id'])->count();
				if (!$days_count){
					$datas[$k]['status'] = '0';
					
				}else{
					$datas[$k]['status'] = '1';
					$datas[$k]['seteddays'] = $days_count;
				}
			}
			
		}
		
		
		$publicVisualModel = D("PublicVisual");//公共虚拟模型
		$res = $publicVisualModel->getAllResolution();
		$this->assign('res', $res);//分辨率
		
		$this->assign('datas', $datas);
		$this->display("PlayPlans/PlayPlans");
	}
	
	/**
	 * 新增播放计划
	*/
	public function addPlayPlans(){
		if (IS_POST) {
			$this->savePlayPlans();
		} else {		
			$planType = I("request.planType");
			if (empty($planType)){
				$planType = "week";
			}
			
			$publicVisualModel = D("PublicVisual");//公共虚拟模型
			$res = $publicVisualModel->getAllResolution();
			$this->assign('res', $res);//分辨率		
			
			switch ($planType){
				case "week":
					$this->display("PlayPlans/editPlayPlansWeek");
					break;
				case "date":
					$this->display("PlayPlans/editPlayPlansDate");
					break;
				default:
					;
			}
		}
	}	
	
	/**
	 * 修改播放计划
	*/
	public function editPlayPlans(){
		if (IS_POST) {
			$this->savePlayPlans();
		} else {		
			$planType = I("request.planType",'',"trim");
			$playPlanId = I("request.playPlanId",0,"intval");
			
			if (empty($planType)){
				$planType = "week";
			}
			$datas_plan = array();

			$publicVisualModel = D("PublicVisual");//公共虚拟模型
			$res = $publicVisualModel->getAllResolution();
			$this->assign('res', $res);//分辨率			
			
			$playPlanModel = D("PlayPlan");//播放计划
			$dayArrangeModel = D("DayArrange");//日安排
			$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
			$tplsModel = D("Tpls");//模板
			
			
			
			if ($playPlanId){
				$datas_plan = $playPlanModel->where("Id=".$playPlanId)->find();
				//var_dump($datas_plan);
			}
			
			//弹窗中的日安排列表，只显示与计划的分辨率相同且模板类型相同的项
//			$map = array();
//			$map['Resolution']=$datas_plan['Resolution'];
//			$map['TplType']=$datas_plan['TplType'];
//			$datas_day = $dayArrangeModel->where($map)->order("Id DESC")->select();
//			$this->assign('datas_day', $datas_day);
			
			switch ($planType){
				case "week":
					//周计划
					if ($datas_plan){
						$playPlanWeekModel = D("PlayPlanWeek");
						$datas_week = $playPlanWeekModel->where("PlayPlanId=".$playPlanId)->select();//最多七条记录
					
						//遍历这七条，找到对应的日安排
						foreach ($datas_week as $k=>$v){
							$data_tmp = $dayArrangeModel ->where("Id=".$v['DayArrangeId'])->find();//var_dump($data_tmp);
							if ($data_tmp){
								$datas_week[$k]['Name'] = $data_tmp['Name'];
								
								//找到此日安排的详细
								$datas_detail = $dayArrangeDetailModel->where("DayArrangeId=".$v['DayArrangeId'])->select();
								foreach ($datas_detail as $kk=>$vv){
									if ($vv['TplClassId']){
										$datas_tpl = $tplsModel->where("tplclassid='".$vv['TplClassId']."'")->find();
										if ($datas_tpl){
											$datas_detail[$kk]['tplName'] = $datas_tpl['tplname'];
										}
									}
								}
								$datas_week[$k]['datas_detail'] = $datas_detail;
							}
						}
					}
					$this->assign('datas_week', $datas_week);	
					
					$map = array();
					$map['Resolution']=$datas_plan['Resolution'];
					$map['TplType']=$datas_plan['TplType'];
					$datas_day = $dayArrangeModel->where($map)->order("Id DESC")->select();//var_dump($datas_day);
					$this->assign('datas_day', $datas_day);

					
					$this->assign('datas_plan', $datas_plan);		//var_dump($datas_plan);							
					$this->display("PlayPlans/editPlayPlansWeek");
					break;
				case "date":
					//日计划
					if ($datas_plan){
						$playPlanDateModel = D("PlayPlanDate");
						
						$map = array();
						$map['PlayPlanId'] = $playPlanId;
						
						// 加载数据分页类
						import('ORG.Util.Page');
						
						// 数据分页
						$totals = $playPlanDateModel->where($map)->count();
						$Page = new Page($totals, 5);
						$show = $Page->show();
						$this->assign('page', $show);	
						
						
						$datas_date = $playPlanDateModel->where($map)->field("Id,PlayPlanId,DayArrangeId,convert(VARCHAR(24),Date,102) as Date")->order("Date DESC")->limit($Page->firstRow.','.$Page->listRows)->select();//最多记录可能是几百条，甚至更多
						
						//var_dump($datas_date);
						
						$dayArrangeModel = D("DayArrange");//日安排
						$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
						
						foreach($datas_date as $k=>$v){
							//取日安排名称
							$datas_day_arrange = $dayArrangeModel->where("Id=".$v['DayArrangeId'])->find();
							$datas_date[$k]['dayArrangeName'] = $datas_day_arrange['Name'];	//var_dump($datas_day_arrange['Name']);
							
							//日安排详细，以数组存入
							$datas_detail = array();
							$datas_detail = $dayArrangeDetailModel->where("DayArrangeId=".$v['DayArrangeId'])->order("Id ASC")->select();
							foreach($datas_detail as $kk=>$vv){
								if ($vv['TplClassId']){
									$datas_tpl = $tplsModel->where("tplclassid='".$vv['TplClassId']."'")->find();
									if ($datas_tpl){
										$datas_detail[$kk]['tplName'] = $datas_tpl['tplname'];
									}	
								}
							}
							$datas_date[$k]['datas_detail'] = $datas_detail;//var_dump($datas_detail);
						}
					}
					
					//默认设置日期为下一日（最后一条记录日期的下一日）
					$datePlayPlan = $playPlanDateModel->where("PlayPlanId=".$playPlanId)->field("Id,PlayPlanId,DayArrangeId,convert(VARCHAR(24),Date,120) as Date")->order("Date DESC")->find();
					if ($datePlayPlan){
						$defaultDate = date("Y-m-d",strtotime($datePlayPlan['Date'])+86400);//'2016-10-10';
						$defaultDayArrangeId = $datePlayPlan['DayArrangeId'];
					}else{
						
					}
					
					//弹窗的日安排下拉列表，只显示与计划的分辨率和模板类型匹配的记录
					$map = array();
					$map['Resolution']=$datas_plan['Resolution'];
					$map['TplType']=$datas_plan['TplType'];
					$datas_day = $dayArrangeModel->where($map)->order("Id DESC")->select();//var_dump($datas_day);
					$this->assign('datas_day', $datas_day);
					
					
					
					
					
					$this->assign('defaultDate', $defaultDate);//默认的日期
					$this->assign('defaultDayArrangeId', $defaultDayArrangeId);//默认的日安排
					
					
					$this->assign('datas_date', $datas_date);
					$this->assign('datas_plan', $datas_plan);		//var_dump($datas_plan);
					$this->display("PlayPlans/editPlayPlansDate");
					break;
				default:
					;
			}
		}
	}
	
	/**
	 * 删除播放计划
	 * 2016-09-30
	 * zjh
	*/
	public function delPlayPlan(){
		//接收参数
		$playPlanId = I("request.playPlanId",0,"intval");
		$playPlanModel = D("PlayPlan");//播放计划（方案
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
		
		if (!$playPlanId){
			$this->error("参数错误");
		}
		
		//查询，检测是否存在
		$dataPlayPlan = $playPlanModel->where("Id=".$playPlanId)->find();
		if ($dataPlayPlan){
			$planType = $dataPlayPlan['PlanType'];
		}
		
		//存在的话，判断计划类型，分为周计划和日计划
		if (!empty($planType)){
			switch ($planType){
				case "week"://周计划
					$map = array();
					$map['PlayPlanId'] = $playPlanId;
					$result = $playPlanWeekModel->where($map)->delete();
					break;
				case "date"://日计划
					$map = array();
					$map['PlayPlanId'] = $playPlanId;
					$result = $playPlanDateModel->where($map)->delete();				
					break;
				default:
					;
			}
			$map = array();
			$map['Id'] = $playPlanId;
			$result = $playPlanModel->where($map)->delete();
			
		}else{
			//正常情况不会存在计划类型为空，如果为空，可分别删除周计划和日计划
				
		}
		
		if ($result !== FALSE) {
			//echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
			$this->success('操作成功！', U('Plan/playPlans'));
		} else {
			//echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
			$this->error('操作失败！[原因]：' . $model->getError());
		}
		
		
		
		
	}
	
	/**
	 * 批量删除播放计划
	 * 2016-09-30
	 * zjh	 
	*/
	public function multiDelPlayPlan(){
		$artiClassIDs = trim(I('request.aids'), ',');//去掉结尾的;
		$aidsArr = explode(',', $artiClassIDs);//转成数组
	
		if (empty($artiClassIDs)){
			echo json_encode(array("stat"=>"0","msg"=>"操作失败，未指定参数","data"=>$data));exit;
		}	
	
		$playPlanModel = D("PlayPlan");//播放计划（方案）
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
	
		$result = $playPlanWeekModel->where(array('PlayPlanId'=>array('in', $artiClassIDs)))->delete();
		$result = $playPlanDateModel->where(array('PlayPlanId'=>array('in', $artiClassIDs)))->delete();
		$result = $playPlanModel->where(array('Id'=>array('in', $artiClassIDs)))->delete();//删除播放计划（方案）


		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>$data));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>$data));exit;
		}	

	

		
	}
	
	/**
	 * 保存播放计划
	*/
	public function savePlayPlans(){
		$name = I("request.title",'',"trim");
		$resolution = I("request.resolution",'',"trim");
		$old_resolution = I("request.old_resolution",'',"trim");
		$planType = I("request.planType",'',"trim");
		$tplType = I("request.tplType",'',"trim");
		$old_tplType = I("request.old_tplType",'',"trim");
		$playPlanId = I("request.playPlanId",0,"intval");
		
		$resolution = preg_replace('# #', '', $resolution);//替换英文空格
		$resolution = preg_replace('#　#', '', $resolution);//替换中文空格
		$resolution = str_replace('x', '*',$resolution);//替换x
		$resolution = str_replace('X', '*',$resolution);//替换x
		
		$old_resolution = preg_replace('# #', '', $old_resolution);//替换英文空格
		$old_resolution = preg_replace('#　#', '', $old_resolution);//替换中文空格	
		
		
		
		if (empty($resolution)){
			$this->error("分辨率不能为空");
		}
		$playPlanDateModel = D("PlayPlanDate");//日计划
		
		$data = array();
		
		$funcName = '';
		if ($playPlanId) {
			$data['Id'] = $playPlanId;
			$funcName = 'save';
		} else {
			$funcName = 'add';
		}
		
		$data['Name'] = $name;
		$data['Resolution'] = $resolution;
		$data['PlanType'] = $planType;
		$data['TplType'] = $tplType;
		if ($funcName == "add"){
			$data['CreateTime'] = date("Y/m/d h:i:s", mktime());;
		}
		$data['LastTime'] = date("Y/m/d h:i:s", mktime());;
		
		// 执行操作
		$playPlanModel = D("PlayPlan");;//日安排
		$result = $playPlanModel->$funcName($data);	
		$newPlayPlanId = $result;
		
		//周计划，要增加七条记录
		if ($planType == "week"){
			//新增七条记录：星期日至星期六（这儿其实应该要用事务处理保证全部成功，否则回滚）
			$playPlanWeekModel = D("PlayPlanWeek");
			if ($funcName == "add"){
				for ($i=0;$i<7;$i++){
					$data = array();
					$data['PlayPlanId'] = $newPlayPlanId;
					$data['Week'] = $i;
					$data['DayArrangeId'] = 0;
					$result = $playPlanWeekModel->add($data);
				}
			}else{//修改
				//分辨率改变或模板类型改变
				if ($resolution != $old_resolution || $tplType != $old_tplType ){
					$result = $playPlanWeekModel->where("PlayPlanId=".$playPlanId)->setField('DayArrangeId',0);//分辨率改变时，周计划对应的七条记录的日安排归0
				}
			
			}		
			
		}else if($planType == "date"){
			if ($funcName == "add"){
				//日计划不需要额外处理
			}else{
				//分辨率改变或模板类型改变
				if ($resolution != $old_resolution || $tplType != $old_tplType ){
					$result = $playPlanDateModel->where("PlayPlanId=".$playPlanId)->delete();//分辨率改变时，日计划对应的全部日期记录删除
				}
			}
			
		}else{
			//其它类型报错
		}
		
		if ($result !== FALSE) {
			if ($funcName == "add"){
				$this->success('操作成功！', U('Plan/editPlayPlans',array("planType"=>$planType,"playPlanId"=>$newPlayPlanId)));
			}else{
				$this->success('操作成功！', U('Plan/editPlayPlans',array("planType"=>$planType,"playPlanId"=>$playPlanId)));
			}
	   } else {
		   $this->error('操作失败！[原因]：' . $playPlanModel->getError());
	   }
		
	}	
	
	/**
	 * 周计划列表
	*/
	
	/**
	 * 更新星期几的日安排字段 DayArrangeId
	*/
	public function updatePlayPlanWeekData(){
		
		$playPlanId = I("post.playPlanId",0,"intval");;
		$week = I("post.week",0,"intval");;
		$dayArrangeId = I("post.dayArrangeId",0,"intval");
		
		//检测有效性
		if (!$playPlanId || !$dayArrangeId){
			echo json_encode(array("stat"=>"0","msg"=>"参数错误","data"=>""));exit;	
		}
		
		$playPlanWeekModel = D("PlayPlanWeek");		
		
		$result = $playPlanWeekModel->where("PlayPlanId=".$playPlanId ." and Week=".$week)->setField('DayArrangeId',$dayArrangeId);
		
		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"更新成功","data"=>""));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"更新失败","data"=>""));exit;
		}
		
	}
	
	
	/**
	 * 日计划列表
	*/	
	
	
	/**
	 * 新增日计划到某个播放计划（方案）下
	*/
	
	public function addPlayPlanDateRecord(){
		$playPlanId = I("post.playPlanId","","intval");//播放计划Id

		$tp = I("post.tp","","trim");//新增还是修改
		$dayArrangeId = I("post.dayArrangeId","","intval");//日安排Id
		$dotype = I("post.dotype","","trim");//edit add
		$doid = I("post.doid","","intval");
		
		$date = I("post.date","","trim");//日期		
		$date = preg_replace('#\.#', '-',$date);//替换2016.09.03为2016-09-03
		$date = date("Y-m-d",strtotime($date));//格式化为月日均为双数
		
		$playPlanDateModel = D("PlayPlanDate");//日计划
		
		//检测参数格式及合法性
		
		//检测日期是否重复（只限当前计划方案范围）
		$datasInThisPlan = $playPlanDateModel->where("PlayPlanId=".$playPlanId)->field("Id,PlayPlanId,DayArrangeId,convert(VARCHAR(24),Date,120) as Date")->select();
		foreach($datasInThisPlan as $k=>$v){

			if ($v['Id'] == $doid){
				//当前记录自己，忽略不检测，不需考虑新增时的0
			}else{
				//
				$d = date("Y-m-d",strtotime($v['Date']));
				if ($date == $d){
					//有重复，直接返回出错提示	
					$data = $d;
					echo json_encode(array("stat"=>"1","msg"=>'日期重复',"data"=>$data));exit;
				}
			}
		}
		
		//检测播放计划是否存在
		
		//入库：修改或新增
		$funcName = '';
		$data = array();
		if ($dotype == 'edit') {
			$data['Id'] = $doid;
			$funcName = 'save';
		} else {
			$funcName = 'add';
		}		
		
		
		$data['PlayPlanId'] = $playPlanId;
		$data['DayArrangeId'] = $dayArrangeId;
		$data['Date'] = $date;
		
		//000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
		
		$result = $playPlanDateModel->$funcName($data);	
		
		if ($result){
			//更新播放计划的最后修改日期更新
			$playPlanModel = D("PlayPlan");
			$result = $playPlanModel->where("Id=".$playPlanId)->setField('LastTime',date("Y/m/d h:i:s", mktime()));
						
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>""));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>""));exit;
		}	
	}
	
	/**
	 * 删除某个播放方案下的日计划中的某日
	*/
	public function delDayPlayPlan(){
		$artiClassIDs = trim(I('post.nids'), ';');//去掉结尾的;
		$aidsArr = explode(';', $artiClassIDs);//转成数组

		if (empty($artiClassIDs) || empty($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'页面数据错误，请刷新页面重试')));
		}		
		
		$playPlanDateModel = D("PlayPlanDate");
		$map = array();
		$map['Id'] = array("IN",$artiClassIDs);
		$result = $playPlanDateModel->where($map)->delete();
		
		if ($result !== false) {
			die(json_encode(array('stat'=>1, 'msg'=>'操作成功！','debug'=>$debug)));
		} else {
			die(json_encode(array('stat'=>0,'msg'=>'操作失败！','debug'=>$debug)));
		}
		
				
		/*
		$playPlanId = I("post.playPlanId",0,"intval");//播放方案Id
		$dayPlayPlanId = I("post.dayPlayPlanId",0,"intval");//日计划Id
		
	//	$dayArrangeModel = D("DayArrange");//日安排
	//	$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		$playPlanDateModel = D("PlayPlanDate");
		$result = $playPlanDateModel->where("Id=".$dayPlayPlanId)->delete();
		if ($result){
			//更新播放计划的最后修改日期更新
			$playPlanModel = D("PlayPlan");
			$result = $playPlanModel->where("Id=".$playPlanId)->setField('LastTime',date("Y/m/d h:i:s", mktime()));
			
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>""));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>""));exit;
		}			*/
	}
	
	
	/**
	 * 日安排列表
	*/
	public function dayArranges(){
		
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		//搜索条件
		$keyboard = trim(I('request.keyboard',"","trim"));
		$tplType = trim(I('request.tplType',"","trim"));
		$resolution = trim(I('request.resolution',"","trim"));
		
		$this->assign('keyboard', $keyboard);
		$this->assign('planType', $planType);
		$this->assign('tplType', $tplType);
		$this->assign('resolution', $resolution);

		$map = array();
		$map['Id'] = array('GT',0);
		if ($keyboard){$map['Name']=array('LIKE','%'.$keyboard.'%');}
		if ($tplType){$map['TplType'] = $tplType;}
		if ($resolution){$map['Resolution'] = $resolution;}

        // 加载数据分页类
        import('ORG.Util.Page');
        
        // 数据分页
        $totals = $dayArrangeModel->where($map)->count();
        $Page = new Page($totals, 10);
        $show = $Page->show();
        $this->assign('page', $show);
		$datas = $dayArrangeModel->where($map)->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select(); 
		foreach ($datas as $k=>$v){
			$count = $dayArrangeDetailModel->where("DayArrangeId=".$v['Id'])->count();
			$datas[$k]['Count'] = $count;
			
			$datas_time = $dayArrangeDetailModel->where("DayArrangeId=".$v['Id'])->order("Id ASC")->select();
			$tmp_time = array();
			foreach($datas_time as $kk=>$vv){
				$tmp_time[] = $vv['BeginTime'];//."-".$vv['EndTime']
			}
			$tmp_time_str = implode(",",$tmp_time);
			$datas[$k]['timeList'] = $tmp_time_str;//时间段列表
			
		}

		$publicVisualModel = D("PublicVisual");//公共虚拟模型
		$res = $publicVisualModel->getAllResolution();
		$this->assign('res', $res);//分辨率
		
		$this->assign('datas', $datas);	//var_dump($datas);
		
		$this->display("PlayPlans/dayArrange");
	}
	
	
	/**
	 * 新增日安排
	*/
	public function addDayArranges(){
		if (IS_POST) {
			$this->saveDayArranges();
		} else {
			$dayArrangeModel = D("DayArrange");
			$tplsModel = D("Tpls");

			//分辨率
			$publicVisualModel = D("PublicVisual");//公共虚拟模型
			$res = $publicVisualModel->getAllResolution();
			$this->assign('res', $res);
			
			//全部模板(下拉列表用)
			$datas_tpl = $tplsModel->where("bevalid = 1")->order("id DESC")->select();
			$this->assign('tpls', $datas_tpl);
			
			$this->display("PlayPlans/editDayArrange");
		}
	}
	
	/**
	 * 修改日安排
	*/
	public function editDayArranges(){
		if (IS_POST) {
			$this->saveDayArranges();
		} else {
			$id = I("request.id",0,"intval");
			
			$dayArrangeModel = D("DayArrange");
			$tplsModel = D("Tpls");
			
			if ($id){
				//日安排
				$datas = $dayArrangeModel->where("Id=".$id)->find();//var_dump($datas);
				
			}else{
				$this->error("未找到记录");
			}
			
			//日安排详细
			$dayArrangeDetailModel = D("DayArrangeDetail");
			$map = array();
			$map['DayArrangeId'] = $id;
			$datas_detail = $dayArrangeDetailModel->where($map)->order("BeginTime ASC,Id ASC")->select();
		//	$datas = $dayArrangeDetailModel->table("TB_Tpls")->where($map)->field("TB_Sch_Emergencies.id,TB_Sch_Emergencies.roomId,TB_Sch_Room.name as roomName,convert(VARCHAR(24),TB_Sch_Emergencies.beginTime,120) as starttime,convert(VARCHAR(24),TB_Sch_Emergencies.endTime,120) as endtime")->join("LEFT /*RIGHT*/ JOIN TB_Sch_Room ON TB_Sch_Emergencies.roomId = TB_Sch_Room.id")->order("id DESC")->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach ($datas_detail as $k => $v) {
				$datas_tmp = array();
				$datas_tmp = $tplsModel->where("tplclassid='".$v['TplClassId']."'")->find();//var_dump($datas[$k]['tplName']);
				$datas_detail[$k]['tplname'] = $datas_tmp['tplname'];
			}
			
			$this->assign('datas_detail', $datas_detail);
			
			//分辨率
			$publicVisualModel = D("PublicVisual");//公共虚拟模型
			$res = $publicVisualModel->getAllResolution();
			$this->assign('res', $res);
			
			
			
			//全部模板(下拉列表用)
			$map_tpls = array();//
			$map_tpls['bevalid'] = 1;
			$map_tpls['tplpx_width'] = substr( $datas['Resolution'] , 0, strpos($datas['Resolution'],'*') );//宽
			$map_tpls['tplpx_height'] = substr( $datas['Resolution'] , strpos($datas['Resolution'],'*')+1 , strlen($datas['Resolution'])-strpos($datas['Resolution'],'*')-1);//高
			$map_tpls['tpltype'] = $datas['TplType'];//x86,azt,azad
			
			$datas_tpl = $tplsModel->where($map_tpls)->order("id DESC")->select();//这儿要过滤分辨率和类型
			$this->assign('tpls', $datas_tpl);
			//var_dump($datas_tpl);
			//var_dump($map_tpls);
			
			
			
			$default = $dayArrangeDetailModel->where("DayArrangeId=".$id)->order("BeginTime DESC")->find();
			if($default != false){
				$begin = $default['BeginTime'];
				$hms = explode(':',$begin); //var_dump($hms);
				$this->assign('h', $hms[0]);//时 默认选中
				$this->assign('m', $hms[1]);//分 默认选中
				$this->assign('s', $hms[2]);//秒 默认选中	
				
				$this->assign('tplclassid', $default['TplClassId']);//默认选中上一个		

			}else{
				$hms = explode(':',$begin); 
				$this->assign('h', "00");//时 默认选中
				$this->assign('m', "00");//分 默认选中
				$this->assign('s', "00");//秒 默认选中	
				
				//$this->assign('tplclassid', $default['TplClassId']);//默认选中上一个			
			}

			
			
			
			$this->assign('datas', $datas);//var_dump($datas);
			$this->display("PlayPlans/editDayArrange");				
		}		
	}
	
	/**
	 * 存储日安排
	*/
	public function saveDayArranges(){
		$id = I("request.id",0,"intval");
		$from = I("request.from",0,"trim");//来源，主要用于识别ajax提交
		$name = I("request.title",0,"trim");
		$resolution = I("request.resolution",0,"trim");
		$tplType = I("request.tplType",0,"trim");
		
		$resolution = preg_replace('# #', '', $resolution);//替换英文空格
		$resolution = preg_replace('#　#', '', $resolution);//替换中文空格
		$resolution = str_replace('x', '*',$resolution);//替换x
		$resolution = str_replace('X', '*',$resolution);//替换x
		$resolution = str_replace('×', '*',$resolution);//替换×
		
		
		$old_resolution = I("request.old_resolution",'',"trim");//原分辨率
		$old_tplType = I("request.old_tplType",'',"trim");//原模板类型
		
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		if ($id){
			//先取出记录，进行比较
			$old = $dayArrangeModel->where("Id=".$id)->find();
			$map=array();
			$map['DayArrangeId']=$id;
			
			if ($resolution != $old['Resolution'] || $tplType != $old['TplType'] ){
				$result = $playPlanWeekModel->where($map)->setField('DayArrangeId',0);//周计划对应的七条记录的日安排归0
				$result = $playPlanDateModel->where($map)->setField('DayArrangeId',0);//日计划对应的全部日期记录归0
				
				//删除日安排详细
				$result = $dayArrangeDetailModel->where("DayArrangeId=".$id)->delete();
			}
		}
	
		
		$data = array();
		
		$funcName = '';
		if ($id) {
			$data['Id'] = $id;
			$funcName = 'save';
		} else {
			$funcName = 'add';
		}
		
		$data['Name'] = $name;
		$data['Resolution'] = $resolution;
		$data['TplType'] = $tplType;
       
		// 执行操作
		$dayArrangeModel = D("DayArrange");//日安排
		$result = $dayArrangeModel->$funcName($data);
		if ($from == 'ajax'){
			//$data = array();
			
			if ($result !== FALSE) {
				if ($id){
					$data = $id;
				}else{
					$data = $result;
				}
				
				echo json_encode(array("stat"=>"1","msg"=>'操作成功',"data"=>$data));exit;
			} else {
				echo json_encode(array("stat"=>"0","msg"=>'操作失败',"data"=>$data));exit;
			}
			exit;
		}else{
			if ($result !== FALSE) {
				$this->success('操作成功！', U('Programs/dayArranges'));
			} else {
				$this->error('操作失败！[原因]：' . $model->getError());
			}	
		}
	}
	
	/*
	 * 删除日安排
	*/
	public function delDayArranges(){
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
		
		$id = I("request.id",0,"trim");
		if ($id){
			$datas = $dayArrangeModel->where("Id=".$id)->find();
			if ($datas){
				
				//用到此日安排的周计划归0
				$playPlanWeekModel->where("DayArrangeId=".$id)->setField('DayArrangeId',0);
				//用到此日安排的日计划归0
				$playPlanDateModel->where("DayArrangeId=".$id)->setField('DayArrangeId',0);
				
				$result = $dayArrangeDetailModel->where("DayArrangeId=".$id)->delete();
				//if ($result){
					$dayArrangeModel->where("Id=".$id)->delete();	
					$this->success("删除成功");
				//}else{
				//	$this->error("删除失败...");
				//}
			}else{
				$this->error("无此记录");	
			}
		}else{
			$this->error("参数错误");	
		}
	}
	
	/**
	 * 删除日安排（批量）
	*/
	public function multiDelDayArranges(){
		
		$artiClassIDs = trim(I('request.aids'), ',');//去掉结尾的;
		$aidsArr = explode(',', $artiClassIDs);//转成数组
	
			
		if (empty($artiClassIDs)){
			echo json_encode(array("stat"=>"0","msg"=>"操作失败，未指定参数","data"=>$data));exit;
		}	
	
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
	
		//用到此日安排的周计划归0
		$playPlanWeekModel->where(array('DayArrangeId'=>array('in', $artiClassIDs)))->setField('DayArrangeId',0);
		//用到此日安排的日计划归0
		$playPlanWeekModel->where(array('DayArrangeId'=>array('in', $artiClassIDs)))->setField('DayArrangeId',0);
	
		$result = $dayArrangeDetailModel->where(array('DayArrangeId'=>array('in', $artiClassIDs)))->delete();
		$result = $dayArrangeModel->where(array('Id'=>array('in', $artiClassIDs)))->delete();

		if ($result){
			echo json_encode(array("stat"=>"1","msg"=>"操作成功","data"=>$data));exit;	
		}else{
			echo json_encode(array("stat"=>"0","msg"=>"操作失败","data"=>$data));exit;
		}	
		
	}
	
	
	/*
	 * 新增日安排详细
	*/
	public function addDayArrangeDetails(){
		$dayArrangeId = I("request.dayArrangeId","","intval");
		$tplclassid = I("request.tplclassid","","trim");
		$start = I("request.start","","trim");//格式：08:08:08
//		$end   = I("request.end","","trim");//格式：08:08:08
		
		//转为时间戳
		$start_d = strtotime($start);
//		$end_d	 = strtotime($end);
		
		if (empty($start)){
			$out = array("stat"=>0,"msg"=>"开始时间未指定");
			die(json_encode($out));		
		}
		if (!$dayArrangeId){
			$out = array("stat"=>0,"msg"=>"开始时间未指定");
			die(json_encode($out));		
		}
		
//		if ($start_d > $end_d){
			//开始时间大于等于结束时间
//			$out = array("stat"=>0,"msg"=>"开始时间大于结束时间，请指定合理的时间安排");
//			die(json_encode($out));			
//		}

//		if ($start_d == $end_d){
			//开始时间等于结束时间
//			$out = array("stat"=>0,"msg"=>"开始时间等于结束时间，请指定合理的时间安排");
//			die(json_encode($out));			
//		}		
		
		
		
		
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		$tplsModel = D("Tpls");//模板
		
		//防止时间重复
		$sameData = $dayArrangeDetailModel->where("DayArrangeId=".$dayArrangeId ." and BeginTime = '".$start."'")->find();
		if ($sameData != false){
			$out = array("stat"=>0,"msg"=>"时间重复");
			die(json_encode($out));		
		}
		
		//检测有效性（日安排id是否存在，模板classid是否存在）
		
		//检测时间交叉（一个日安排下的日安排详细范围内检测）
		/*
		$countDetail = $dayArrangeDetailModel->where("DayArrangeId=".$dayArrangeId)->count();
		if ($countDetail){
			$datas_detail = $dayArrangeDetailModel->where("DayArrangeId=".$dayArrangeId)->select();
			foreach ($datas_detail as $k => $v){
				if ( (strtotime($v['BeginTime']) < $start_d) && ($start_d < strtotime($v['EndTime'])) ){
					$out = array("stat"=>0,"msg"=>"指定的开始时间在其它播放时间段内");
					echo json_encode($out);exit;
					break;
				}
				if ( (strtotime($v['BeginTime']) < $end_d) && ($end_d < strtotime($v['EndTime'])) ){
					$out = array("stat"=>0,"msg"=>"指定的结束时间在其它播放时间段内"); 
					echo json_encode($out);exit;
					break;
				}
				//以上两种判断，不包括边界重复，因为边界重复是允许的				
			}
		}
		*/
		
		//入库
		$data['DayArrangeId'] = $dayArrangeId;//学校介绍
		$data['BeginTime'] = $start;
		$data['EndTime'] = $end;
		$data['TplClassId'] = $tplclassid;
		$result = $dayArrangeDetailModel->data($data)->add();
		
		if ($result !== FALSE) {
			//$insertId = $result;
			echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
		} else {
			echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
		}
		
	}
	
	/**
	 * 修改日安排详细
	*/
	public function editDayArrangeDetails(){
		
	}	
	
	/*
	 * 删除日安排详细
	*/
	public function delDayArrangeDetails(){
		$dayArrangeId = I("request.dayArrangeId","","intval");
		$dayArrangeDetailId = I("request.dayArrangeDetailId","","intval");
		
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		//这里实际上应该考虑权限，否则通过构造地址都全部可以删除，不过暂时不验证
		
		$result = $dayArrangeDetailModel->where("Id=".$dayArrangeDetailId." and DayArrangeId=".$dayArrangeId)->delete();
		if ($result !== FALSE) {
			//$insertId = $result;
			echo json_encode(array("stat"=>"1","msg"=>'操作成功'));exit;
		} else {
			echo json_encode(array("stat"=>"0","msg"=>'操作失败'));exit;
		}		
	}
	
	
	/*
		$playPlanModel = D("PlayPlan");//播放计划（方案）
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细	
		$tplsModel = D("Tpls");//模板
	
	*/
	
///////////////////////////////////////////////播放计划 END///////////////////////////////////
	
}