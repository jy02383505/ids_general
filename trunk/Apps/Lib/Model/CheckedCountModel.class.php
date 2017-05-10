<?php
/**
 * 虚拟模型：审核统计模型
 * 用途，用于统计栏目组、栏目、文章等的审核情况
 * $checkedCountModel = D("CheckedCount");
*/
class CheckedCountModel extends Model {
	Protected $autoCheckFields = false;	
	public $isDebug = 1;
	
 	/**
	 * 终端管理->管理终端组->图标：查看下属终端->终端组：生成最新数据
	 * 在生成前，要检查是否有未审核和被驳回
	*/
 	public function jianchejiemushenghe($groupId){
		return 0;//暂时禁用生成前审核，开启时去掉本句即可
		$this->$isDebug = 0;
		$epgModel = D('EndpointsGroups');//终端组
		
		$playPlanModel = D("PlayPlan");//播放计划
		$playPlanWeekModel = D("PlayPlanWeek");//周计划
		$playPlanDateModel = D("PlayPlanDate");//日计划
		$dayArrangeModel = D("DayArrange");//日安排
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		$tplsModel = D("Tpls");//模板
		$programsModel = M('Programs');//节目
		$pdgModel = M('ProgramsDirsGroups');//栏目组
		$columnModel = M('ProgramsDirs');//栏目
		$articleModel = M('ProgramsArticles');//文章
		
		header("Content-type: text/html; charset=utf-8");
		
		$epgId = $groupId;//终端组，tplclassid
		//echo "<br><b>终端组id：</b>".$epgId;
		//var_dump($this->$isDebug);
		$datas_epg = $epgModel->where("id=".$epgId)->field("groupname,PlayPlanId")->find();
		if ($datas_epg){
			$PlayPlanId =$datas_epg['PlayPlanId'];//此终端组的播放计划
			if ($PlayPlanId){
				if ($isDebug){echo "<br>播放计划Id：".$PlayPlanId;}
				$result = $playPlanModel->where("Id=".$PlayPlanId)->find();
				if (!$result){
					if ($this->$isDebug){echo "<br>此播放计划不存在";}
					exit;
				}
				else{
					if ($this->$isDebug){echo "<br>播放计划名称：".$result['Name'];}
					if ($this->$isDebug){echo "<br>播放计划类型：".$result['PlanType'];}
					switch ($result['PlanType']){
						case "week":
							$datas_play_plan = $playPlanWeekModel->where("PlayPlanId=".$result['Id'])->select();//找周计划中的日安排（七天）
							break;
						case "date":
							$datas_play_plan = $playPlanDateModel->where("PlayPlanId=".$result['Id'])->select();//找日计划中的日安排（0天到n天）
							break;
						default:
							;
						
					}
					//end switch
					//var_dump($datas_play_plan);
					
					//到此，构造出日安排Id数组
					$arr_dayArrangeId = array();
					foreach ($datas_play_plan as $k=>$v){
						$arr_dayArrangeId[] = $v['DayArrangeId'];
						
					}
					//var_dump($arr_dayArrangeId);//array(日安排id,日安排id,日安排id);array(3) {  [0]=>  int(12)  [1]=>  int(13)  [2]=>  int(14)}
					$StrDayArrangeId = implode(",",$arr_dayArrangeId);
					if ($this->$isDebug){echo "<br>日安排Id字符串：".$StrDayArrangeId;}
					
					$ArrTplClassId = $this->getArrTplClassIdFromDayArrangeId($StrDayArrangeId);//从日安排获取模板
					//var_dump($ArrTplClassId);
					$StrTplClassId = implode(",",$ArrTplClassId);//拆分为字符串echo $StrTplClassId;//24f9a36c-db26-4f98-b03e-58a02458e3f8,11111-22222-33333-44444
					if (!empty($ArrTplClassId)){
						$ArrProgramClassId = $this->getArrProgramClassIdFromTplClassId($StrTplClassId);//从模板ClassId获取节目ClassId  var_dump($ArrProgramClassId);
					}
					$StrProgramClassId = implode(",",$ArrProgramClassId);//逗号分隔的节目ClassId
					
					//从节目ClassId找到所有栏目目组
					$ArrDirGroupClassId = $this->getArrDirGroupClassIdFromProgramClassId($StrProgramClassId);
					$StrDirGroupClassId = implode(",",$ArrDirGroupClassId);//逗号分隔的栏目组ClassId
					
					//从节目ClassId找到所有栏目
					$ArrDirClassId = $this->getArrDirClassIdFromProgramClassId($StrProgramClassId);//根据节目ClassId取栏目ClassId
					$StrDirClassId = implode(",",$ArrDirClassId);//逗号分隔的栏目ClassId
				
					
					//从栏目ClassId获取全部文章
					$ArrDatasArticleClassId = $this->getArrArticleClassIdFromDirClassId($StrDirClassId);//
					$StrArticleClassId = implode(",",$ArrDatasArticleClassId);
					if ($this->$isDebug){echo "<br>文章总数：".count($ArrDatasArticle);}
					
					//待审核和已驳回的文章统计
					$countCheckedArticle = array();
					$where = array();
					$where['article_classid'] = array("IN",$StrArticleClassId);//这儿用了IN，效率肯定不如直接统计
					$where['_string']  = 'checked is null or checked = 0';
					$countCheckedArticle['ds'] = $articleModel->where($where)->count();//待审
					$where['_string']  = 'checked = 1';
					$countCheckedArticle['ys'] = $articleModel->where($where)->count();//已审
					$where['_string']  = 'checked = -1';
					$countCheckedArticle['bh'] = $articleModel->where($where)->count();//驳回
					if ($this->$isDebug){echo "<br>待审文章总数：".$countCheckedArticle['ds'];}
					if ($this->$isDebug){echo "<br>已审文章总数：".$countCheckedArticle['ys'];}
					if ($this->$isDebug){echo "<br>驳回文章总数：".$countCheckedArticle['bh'];}
			
					//待审核和已驳回的栏目组统计
					$countCheckedDirGroup = array();
					$where = array();
					$where['program_id'] = array("IN",$StrProgramClassId);//这儿用了IN，效率肯定不如直接统计
					$where['_string']  = 'checked is null or checked = 0';
					$countCheckedDirGroup['ds'] = $pdgModel->where($where)->count();//待审
					$where['_string']  = 'checked = 1';
					$countCheckedDirGroup['ys'] = $pdgModel->where($where)->count();//已审
					$where['_string']  = 'checked = -1';
					$countCheckedDirGroup['bh'] = $pdgModel->where($where)->count();//驳回
					if ($this->$isDebug){
						echo "<br>待审栏目组总数：".$countCheckedDirGroup['ds'];
						echo "<br>已审栏目组总数：".$countCheckedDirGroup['ys'];
						echo "<br>驳回栏目组总数：".$countCheckedDirGroup['bh'];
					}
					
					//待审核和已驳回的栏目统计
					$countCheckedDir = array();
					$where = array();
					$where['classid'] = array("IN",$StrDirClassId);//这儿用了IN，效率肯定不如直接统计
					$where['_string']  = 'checked is null or checked = 0';
					$countCheckedDir['ds'] = $columnModel->where($where)->count();//待审
					$where['_string']  = 'checked = 1';
					$countCheckedDir['ys'] = $columnModel->where($where)->count();//已审
					$where['_string']  = 'checked = -1';
					$countCheckedDir['bh'] = $columnModel->where($where)->count();//驳回
					if ($this->$isDebug){
						echo "<br>待审栏目总数：".$countCheckedDir['ds'];
						echo "<br>已审栏目总数：".$countCheckedDir['ys'];
						echo "<br>驳回栏目总数：".$countCheckedDir['bh'];
					}
					
					//待审核和已驳回的总数
					$count_all = $countCheckedDirGroup['ds'] + $countCheckedDirGroup['bh'] +
								 $countCheckedDir['ds'] + $countCheckedDir['bh']+
								 $countCheckedArticle['ds'] + $countCheckedArticle['bh'];

					if ($this->$isDebug){echo "<br>待审核的和驳回的栏目组、栏目、文章总数统计：".$count_all;}
					return $count_all;//返回待审核的和驳回的栏目组、栏目、文章总数统计
				}
			}else{
				if ($this->$isDebug){echo "未绑定播放计划";}
				return false;//未绑定播放计划
			}
		}else{
			if ($this->$isDebug){echo "无此终端组";}
			return false;//无此终端组
		}		
	}
	
	/**
	 * 根据日安排Id获取模板Id
	 * 参数:日安排Id或逗号分隔的多个日安排Id
	 * 返回：模板ClassId数组
	*/
	public function getArrTplClassIdFromDayArrangeId($StrDayArrangeId=""){
		if (empty($StrDayArrangeId)){
			return false;	
		}
		/*
		if(strstr($StrDayArrangeId,",")){
			$ArrDayArrangeId = explode(",",$StrDayArrangeId);	
		}else{
			$ArrDayArrangeId = explode(",",intval($StrDayArrangeId));
		}
		var_dump($ArrDayArrangeId);//日安排数组
		*/
		$dayArrangeDetailModel = D("DayArrangeDetail");//日安排详细
		
		$tmp=array();
		$arrTplClassId = array();//模板TplClassId
		$map = array();
		$map['DayArrangeId'] = array("IN",$StrDayArrangeId);
		
		$datasDayArrangeDetail = $dayArrangeDetailModel->where($map)->select();
		foreach($datasDayArrangeDetail as $k=>$v){
			$arrTplClassId[]=$v['TplClassId'];//var_dump($v['TplClassId']);
		}
		$arrTplClassId = array_unique($arrTplClassId);//全部节目，去重复
		//var_dump($arrTplClassId);
		return $arrTplClassId;//返回模板ClassId，一个或多个（数组）
	} 
			
	/**
	 * 根据模板ClassId获取节目ClassId
	 * 输入:一个TplClassId或逗号分隔的多个
	 * 返回：节目ClassId数组
	*/
	public function getArrProgramClassIdFromTplClassId($strTplClassId){
		if (empty($strTplClassId)){
			return false;
		}else{
			$tplsModel = D("Tpls");//模板
			$map = array();
			$map['tplclassid'] = array("IN",$strTplClassId);
			$ArrDatasPrgClassId = $tplsModel->where($map)->select();
			
			$ArrProgramClassId = array();
			foreach($ArrDatasPrgClassId as $k=>$v){
				$ArrProgramClassId[] = $v['binding_program_classid'];//模板绑定的节目ClassId
			}
			
			$ArrProgramClassId = array_unique($ArrProgramClassId);//全部节目ClassId，去重复

			return $ArrProgramClassId;//节目ClassId数组
			
		}
		
	}
	
	
	/**
	 * 从节目ClassId找到所有栏目组ClassId
	 * 输入：一个或多个节目ClassId
	 * 输出：所有栏目组ClassId数组
	*/
	public function getArrDirGroupClassIdFromProgramClassId($StrProgramClassId){
		if (empty($StrProgramClassId)){
			return false;
		}
		
		$programsModel = M('Programs');//节目
		$pdgModel = M('ProgramsDirsGroups');//栏目组
		$columnModel = M('ProgramsDirs');//栏目
		
		$datasProgram = array();
		/*
		//找节目
		$tmp = array();
		$map = array();
		$map['program_classid'] = array("IN",$StrProgramClassId);
		$datasProgram = $programsModel->where($map)->field("id,program_classid")->select();//查询一遍，确保节目都是存在的
		
		foreach($datasProgram as $k=>$v){
			$tmp[] = $v['program_classid'];
		}
		
		$strPrgCld = implode(",",$tmp);//节目ClassId，逗号分隔
		*/
		
		//找栏目组
		$tmp = array();
		$map = array();
		$map['program_id'] = array("IN",$StrProgramClassId);
		$datasProgramDirGroup = array();
		$datasProgramDirGroup = $pdgModel->where($map)->field("dirgroup_classid")->select();//根据节目找到了栏目
		foreach($datasProgramDirGroup as $k=>$v){
			$tmp[] = $v['dirgroup_classid'];//栏目组ClassId数组
		}
		return $tmp;
	}
	
	/**
	 * 从节目ClassId找到所有栏目ClassId
	 * 输入：一个或多个节目ClassId（逗号分隔）
	 * 输出：所有栏目ClassId数组
	*/
	public function getArrDirClassIdFromProgramClassId($StrProgramClassId){
		if (empty($StrProgramClassId)){
			return false;
		}
		
		$programsModel = M('Programs');//节目
		$pdgModel = M('ProgramsDirsGroups');//栏目组
		$columnModel = M('ProgramsDirs');//栏目
		/*
		$datasProgram = array();
		
		//找节目
		$tmp = array();
		$map = array();
		$map['program_classid'] = array("IN",$StrProgramClassId);
		$datasProgram = $programsModel->where($map)->field("id,program_classid")->select();//查询一遍，确保节目都是存在的
		
		foreach($datasProgram as $k=>$v){
			$tmp[] = $v['program_classid'];
		}
		
		$strPrgCld = implode(",",$tmp);//节目ClassId，逗号分隔
		*/
		
		//找栏目组
		$tmp = array();
		$map = array();
		$map['program_id'] = array("IN",$StrProgramClassId);
		$datasProgramDirGroup = array();
		$datasProgramDirGroup = $pdgModel->where($map)->field("dirgroup_classid")->select();//根据节目找到了栏目
		foreach($datasProgramDirGroup as $k=>$v){
			$tmp[] = $v['dirgroup_classid'];//栏目组ClassId数组
		}
		//var_dump($tmp);
		$strDirGroupClassId = implode(",",$tmp);//逗号分隔的栏目组ClassId
		
		//找栏目
		$tmp = array();
		$map = array();
		$map['dirgroup_classid'] = array("IN",$strDirGroupClassId);
		$datasProgramDir = $columnModel->where($map)->field("classid")->select();
		foreach($datasProgramDir as $k=>$v){
			$tmp[] = $v['classid'];
		}
		
		return $tmp;//栏目ClassId数组
		
		//$tmp = array_unique($tmp);//全部节目ClassId，去重复
		//$strDirClassId = implode(",",$tmp);//逗号分隔的栏目ClassId
		//echo $strDirClassId;
	}
	
	/**
	 * 根据栏目ClassId获取文章ClassId
	*/
	public function getArrArticleClassIdFromDirClassId($StrDirClassId){
		if(empty($StrDirClassId)){
			return false;	
		}
		
		$articleModel = M('ProgramsArticles');//文章
		$tmp = array();
		$map = array();
		$map['program_dir_classid'] = array("IN",$StrDirClassId);
//		var_dump($StrDirClassId);
		$datasArticleClassId = $articleModel->where($map)->field("id,article_name,article_classid")->select();
		foreach($datasArticleClassId as $k=>$v){
			$tmp[] = $v['article_classid'];	
		}
		//var_dump($datasArticleClassId);
		return $tmp;
	}
	
	
	
	
}