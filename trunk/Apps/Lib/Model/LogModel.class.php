<?php
/**
 * 日志模型
 * $logModel = D("Log");
 *
 */
class LogModel extends Model {
	protected $trueTableName = 'TB_Log';
	
	public function getLogType(){
		$logTypeArr = array(
			"userLogin",//登录后台
			"userLogout"//退出登录
		);
		return $logTypeArr;
	}
	
	/**
	 * 写日志到数据表
	 * 示例：$logModel->writeLog(session(C('USER_AUTH_KEY')),'用户登陆','','userLogin');
	*/
	public function writeLog($userid=0,$username='',$title='',$disc='',$type=''){
		if (!$userid || empty($title)){
		//	return ;
		}
		
		$data = array();
		$data['userid'] = $userid;
		$data['account'] = $username;
		$data['title'] = $title;
		$data['disc'] = $disc;
		$data['type'] = $type;
		$data['createtime'] = date("Y/m/d h:i:s", mktime());//time();//mktime();
		$data['ip'] = get_client_ip();
		$this->data($data)->add();
		
	}
	
	
}