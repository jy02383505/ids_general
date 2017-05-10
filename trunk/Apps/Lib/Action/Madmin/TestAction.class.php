<?php
/**
 * 测试
 * @author ZJH
 *
 */
class TestAction extends CommonAction {
    
    public function index(){
        $this->display("Test/Index");
    }
 
	/*
	 * 测试：统计未审核和已驳回的栏目组、栏目、文章，并将它们相加
	*/
	public function test(){
		$checkedCountModel = D("CheckedCount");
		$countWaitChecked = $checkedCountModel->jianchejiemushenghe();
		echo $countWaitChecked;
		
	}
	
	public function testdate(){
		header("Content-type: text/html; charset=utf-8");
		$thedate = "2016-10-21";
		echo "<br>开始日期：".$thedate;
	//	echo "<br>".$thedate;
	//	echo "<br>".$thedate;
		$next = strtotime($thedate)+24*60*60;
	//	echo "<br>下一天：".$next;
	//	echo "<br>下一天：".date('Y-m-d H:i:s',$next);
		echo "<br>";
		echo "<br>";
		$days = 3;
		
		echo "<br>".$days."天";
		
		echo "<br>其它日期";
		if ($days){
			for($i=1;$i<$days;$i++){
				$next = strtotime($thedate) + 24*60*60*$i;
				echo "<br>下一天：".date('Y-m-d',$next); // H:i:s
			}	
			
		}
		
		
		
	//	var_dump( "明天:".date('Y-m-d H:i:s',strtotime('+1 day')));
		
	}
	
	
	public function test_online(){
		$endPoint = D("Endpoint");//终端
		$name = 'T0001';
		$datas = $endPoint->where("touchMainId='".$name."'")->field("tid,TouchMainRunStatus,lastdatazip")->select();
		foreach($datas as $k=>$v){
			//var_dump($v);
			file_put_contents("endpoint.txt",PHP_EOL."debug:".PHP_EOL.serialize($v).PHP_EOL,FILE_APPEND);//
			
			
		}
		$this->show("<meta http-equiv='refresh' content='10'>");
	}
	
	
    
}