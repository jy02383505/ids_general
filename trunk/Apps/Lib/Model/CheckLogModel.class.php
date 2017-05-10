<?php
/**
 * 
 * 审核日志模型，用于节目审核，栏目组审核，栏目审核，节目文章审核
 * $checkLogModel = D("CheckLog");
 */
class CheckLogModel extends Model {
	protected $trueTableName = 'TB_CheckLog';
	protected $type = array();//类型
	protected $type2 = array();//类型，带中文

	public function _initialize(){
		$this->$type = array(
			array("en"=>"program","cn"=>"节目"),
			array("en"=>"programDirGroup","cn"=>"栏目组"),
			array("en"=>"programDir","cn"=>"栏目"),
			array("en"=>"programArticle","cn"=>"文章"),
			array("en"=>"resourceWorldNews","cn"=>"全球要闻"),
			array("en"=>"resourceHistoric","cn"=>"历史上的今天"),//资源：历史上的今天
			array("en"=>"resourceFamousQuotes","cn"=>"名人名言"),
			array("en"=>"resourceBaike","cn"=>"百科知识"),
			array("en"=>"resourceHumorJoke","cn"=>"幽默笑话")
		);
		
	}
	
	public function getTypeArr(){

		return $this->$type;	
	}
	
	/**
	 * 写入审核日志
	 * 参数：
	 	type - 审核对象的类型，只限于数据成员type的元素的值，包括节目、栏目组、栏目、节目文章、资源
		word - 附加消息的内容
		classid - 唯一区别的字符串，
				　各对象表中的classid，字段名在各表中不同
				  tb_programs.program_classid
				  tb_programs_dirs_groups.dirgroup_classid
				  tb_programs_dirs.classid
				  tb_programs_articles.article_classid
		rid - 对象各表的自增序号
		userid - 用户的会员编号
		username - 用户的会员名
		dotype - 当前操作方式，共三种
				 0 待审核
				 1 已审核（合格，通过）
				-1　驳回（不合格，退回修改，下发内容时不包括0和-1的内容）
				 
			  
	 *
	*/
	public function writeNewLog($dotype=0,$word="",$type="",$rid=0,$classid=""){
		$data = array();
		$data['dotype'] = $dotype;
		$data['word'] = substr($word,0,255);
		$data['userid'] = session(C('USER_AUTH_KEY'));
		$data['username'] = session("username");
		$data['type'] = $type;
		$data['rid'] = $rid;
		$data['classid'] = $classid;
		$data['checktime'] = date("Y-m-d H:i:s",time());
		
		//$data['ip'] = get_client_ip();
		$this->data($data)->add();
		
	}
}