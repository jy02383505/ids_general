<?php
/**
 * 模型：日播放计划
 * @author zjh
 * 
 * $playPlanDateModel = D("PlayPlanDate");
 */
class PlayPlanDateModel extends Model {
	protected $trueTableName = 'TB_DatePlans';
	

	/*
	 * 检测日计划中的日期是否存在
	 * 参数：$playPlanId，int类型
	 * 参数：$date，格式：2016-10-05
	*/
	public function checkHadDate($playPlanId = 0,$date = ''){
		$datas = $this->where("PlayPlanId=".$playPlanId)->field("Id,PlayPlanId,DayArrangeId,convert(VARCHAR(24),Date,120) as Date")->select();
		foreach($datas as $k=>$v){
				$d = date("Y-m-d",strtotime($v['Date']));
				if ($date == $d){
					//有重复，直接返回出错提示	
					return true;
				}

		}					
		
		
	}
}