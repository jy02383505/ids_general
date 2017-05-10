<?php
/**
 * 管理平台首页
 * @author Skeam TJ
 *
 */
class IndexAction extends CommonAction {
    
    public function index(){
        $this->display();
    }
    
    public function topFrame(){
    	
    	// 顶部主导航权限判断
    	$this->assign('topNavs', parent::topNavAccess());
		
    	// 内容管理默认
		/*
    	if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
    		$scenceids = M('RoleScencesitems')->where(array('role_id'=>$_SESSION['role']))->getField('scence_id', true);
    		$where = array('id'=>array('in', $scenceids));
    	} else {
    		$where = array('ishomescence'=>'True');
    	}
		
    	$scencesModel = D('Scences');
    	$homeScencedID = $scencesModel->where($where)->order('id asc')->limit(1)->getField('id');
    	$menuDef = '__GROUP__/Scences/' . ($homeScencedID*1 ? 'index/sid/' . $homeScencedID : 'emptyCon');
    	$this->assign('homeScenceURL', $menuDef);
		*/
    	
    	if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
	    	
	    	$this->assign('roleAccessCon', parent::conAccess());
    	}
    	
    	$this->display();
    }
    
    /*public function leftFrame(){
    	
		$scenceids = $itemids = array();
    	if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
	    	$roleScenceitemsModel = M('RoleScencesitems');
			$roleScenceitems = $roleScenceitemsModel->where(array('role_id'=>$_SESSION['role']))->select();
			if ($roleScenceitems) {
				foreach ($roleScenceitems as $si) {
					$scenceids[] = $si['scence_id'];
					if (trim($si['item_ids']) != '')
						$itemids = array_merge($itemids, explode('-', $si['item_ids']));
				}
			}
    	}
    	
    	// 获取场景插件数据
    	$scencesModel = D('Scences');
    	$scencesWhere = $_SESSION[C('ADMIN_AUTH_KEY')] ? array() : array('id'=>array('in', $scenceids));
    	$scences = $scencesModel->field(array('id,classid,scencename'))->where($scencesWhere)->order('id asc')->select();
    	if ($scences) {
    		
    		// 过滤插件
    		$itemManagerTypes = array_keys($this->pluginsTypes());
    		
    		$pluginsModel = D('Plugins');
    		foreach ($scences as &$scence) {
				$scence['scencename'] = gbk2utf8($scence['scencename']);
    			$scence['short_scencename'] = (mb_strlen($scence['scencename'], 'UTF-8') > 14) ? mb_substr($scence['scencename'], 0, 14, 'UTF-8') . '....' : $scence['scencename'];
    			
		    	$pluginsWhere = array('belong_scenceid'=>$scence['classid'], 'itemtype_classid'=>array('in', $itemManagerTypes));
		    	if (!$_SESSION[C('ADMIN_AUTH_KEY')])
		    		$pluginsWhere['id'] = array('in', $itemids);
    			$subPlugins = $pluginsModel->field(array('id,classid,name,belong_scenceid,itemtype_classid'))->where($pluginsWhere)->select();
				if ($subPlugins) {
					foreach ($subPlugins as &$sp) {
						$sp['name'] = gbk2utf8($sp['name']);
					}
					$scence['sub_plugins'] = $subPlugins;
				}
    		}
    	}
    	
    	if (IS_AJAX) {
    		echo json_encode($scences);
    	} else {
    		$this->assign('scences', $scences);
    		$this->display();
    	}
    }*/
    
    public function leftFrame(){
    	
    	$menuID = I('get.menuID', 'homepage');
    	$menuDef = ''; 	
    	
    	if ($menuID == 'homepage') {
    		
    		$menuDef = '__GROUP__/Index/rightFrame';
    		
    	} elseif ($menuID == 'scences') {
	    	$scencesModel = D('Scences');
			
			$where = array('ishomescence'=>'True');
			$homeScencedID = $scencesModel->where($where)->order('id asc')->limit(1)->getField('id');
			$menuDef = '__GROUP__/Scences/' . ($homeScencedID*1 ? 'index/sid/' . $homeScencedID : 'emptyCon');

	    	/* $scenceids = $itemids = array();
			if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
				$roleScenceitemsModel = M('RoleScencesitems');
				$roleScenceitems = $roleScenceitemsModel->where(array('role_id'=>$_SESSION['role']))->select();
				if ($roleScenceitems) {
					foreach ($roleScenceitems as $si) {
						$scenceids[] = $si['scence_id'];
						if (trim($si['item_ids']) != '')
							$itemids = array_merge($itemids, explode('-', $si['item_ids']));
					}
				}
			} */
			
			// 获取场景插件数据
			//$scencesWhere = $_SESSION[C('ADMIN_AUTH_KEY')] ? array() : array('id'=>array('in', $accScenceIds));
			/*
			$scences = $scencesModel->field(array('id,classid,scencename'))->where($scencesWhere)->order('id asc')->select();
			if ($scences) {
				
				// 过滤插件
				$itemManagerTypes = array_keys($this->pluginsTypes());
				
				$pluginsModel = D('Plugins');
				foreach ($scences as &$scence) {
					$scence['scencename'] = gbk2utf8($scence['scencename']);
					$scence['short_scencename'] = (mb_strlen($scence['scencename'], 'UTF-8') > 14) ? mb_substr($scence['scencename'], 0, 14, 'UTF-8') . '....' : $scence['scencename'];
					
					$pluginsWhere = array('belong_scenceid'=>$scence['classid'], 'itemtype_classid'=>array('in', $itemManagerTypes));
					if (!$_SESSION[C('ADMIN_AUTH_KEY')])
						$pluginsWhere['id'] = array('in', $itemids);
					$subPlugins = $pluginsModel->field(array('id,classid,name,belong_scenceid,itemtype_classid'))->where($pluginsWhere)->select();
					if ($subPlugins) {
						foreach ($subPlugins as &$sp) {
							$sp['name'] = gbk2utf8($sp['name']);
						}
						$scence['sub_plugins'] = $subPlugins;
					}
				}
			} */
			
			// 获取场景插件数据
			$accScenceIds =  M('RoleScencesitems')->field(array('scence_id', 'type'))->where(array('role_id'=>$_SESSION['role']))->select();
			$treeAcc = array('sids'=>array(), 'pids'=>array());
			foreach ($accScenceIds as $asi) {
				$treeAcc[$asi['type'] == 1 ? 'sids' : 'pids'][] = $asi['scence_id'];
			}
			$scencesWhere = array('ishomescence'=>'True');
			if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
				$scencesWhere['id'] = array('in', !empty($treeAcc['sids']) ? $treeAcc['sids'] : array(0));
			}
			$homeSecnce = $scencesModel->field(array('id','classid','scencename','parentscence_id','ishomescence'))->where($scencesWhere)->find();
			 
			if ($homeSecnce) {
				$sTree = array();
				$this->spTree($sTree, $homeSecnce, $treeAcc);
				$this->assign('sTree', json_encode($sTree));
			}
	    	
    	} elseif ($menuID == 'mall') {

    		$menuDef = '__GROUP__/Mall/stores';
    		
    	} elseif ($menuID == 'syscfg') {

    		$menuDef = '__GROUP__/Syscfg/basicCfg';
    		
    	} elseif ($menuID == 'endPoints') {

    		$endType = '';

    		if (isset($_GET['et']) && !empty($_GET['et'])) {
	    		$endType = $_GET['et'];
    		} else {
	    		if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
	    			$roleAccessCon = parent::conAccess();
	    			$endType = array_shift($roleAccessCon['EndPoints']);
	    		} else {
		    		$endType = 'x86';
	    		}
    		}
    		
    		$groupsTree = D('EndpointsGroups')->getZTreeData($endType, true);
    		$this->assign('groupsTree', json_encode($groupsTree));
    		
    		$menuDef = '__GROUP__/Endpoints/index/et/' . $endType;
    		$this->assign('etype', $endType);
    		
    	} elseif ($menuID == 'ucenter') {
    		
    		$menuDef = '__GROUP__/Users/index';
    		
    	} elseif ($menuID == 'resLib') {
    		
    		$menuDef = '__GROUP__/ResLib/index';
    		
    	} elseif ($menuID == 'programs') {
    		
    		$endType = '';
    		
    		if (isset($_GET['et']) && !empty($_GET['et'])) {
    			$endType = $_GET['et'];
    		} else {
    			if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
    				$roleAccessCon = parent::conAccess();
    				$endType = array_shift($roleAccessCon['EndPoints']);
    			} else {
    				$endType = 'x86';
    			}
    		}
    		$this->assign('etype', $endType);
    		
    		if ($endType == 'x86') {
    		
        		$columnModel = D('ProgramsDirs');
                // 树节点当中新增“文章回收站”节点
                $trashArticleNode[name] = '文章回收站';
                $trashArticleNode[node_type] = 'trashArticle';
                $programTreeData = $columnModel->getZTreeData();
                $programTreeData[] = $trashArticleNode;
        		$this->programTree = json_encode($programTreeData);
        		$menuDef = '__GROUP__/Programs/index';
    		
    		} else if ($endType == 'azad' || $endType == 'azt') {
    		    
    		    /* $playlists = D('TBAPlaylists')->field(array('id', 'pl_classid', 'pl_name'))->select();
    		    foreach ($playlists as &$pl) {
    		        $pl['name'] = $pl['pl_name'];
    		        $pl['unid'] = $pl['pl_classid'];
    		    }
        		$this->assign('programTree', json_encode($playlists)); */
        		$menuDef = '__GROUP__/Playlists/programs';
    		    
    		} else {   // $endType = 'adt'
    		    
        		$this->assign('programTree', json_encode(null));
        		$menuDef = '__GROUP__/Empty/index';
    		}
    		
    	}
    	$this->assign('menuDef', $menuDef);
    	$this->assign('menuID', $menuID);
    	$this->display();
    }
    
    public function toggleFrame(){
    	
    	$this->display();
    }
    
    public function rightFrame(){
    	
    	// 系统信息
    	$sysinfo = sysinfo();
    	$this->assign('sysinfo', $sysinfo);
  
  
		$programsModel = M('Programs');			//节目
		$pdgModel = M('ProgramsDirsGroups');	//栏目组
		$columnModel = M('ProgramsDirs');		//栏目
		$articleModel = M('ProgramsArticles');	//文章
		
		$reslibNewsModel = M('ReslibNews');				//全球要闻
		$reslibHistoricModel = M('ReslibHistoric');		//历史上的今天
		$reslibFamousQuotesModel = D('FamousQuotes');	//名人名言
		$reslibBaikeModel = M('ReslibBaike');			//百科知识
		$reslibHumorJokesModel = D('HumorJokes');		//幽默笑话
		
		$myCheckDS = 0;//我的待审核数量
		$myCheckBH = 0;//我的被驳回数量
		$allCountDS = 0;//全部待审核数量，只显示给有审核权限的
		
		//初始化数组
		$countCheckedProgram = array("ds"=>0,"ys"=>0,"bh"=>0);		//节目：待审，已审，未审
		$countCheckedDirGroup = array("ds"=>0,"ys"=>0,"bh"=>0);		//栏目组：待审，已审，未审
		$countCheckedDir = array("ds"=>0,"ys"=>0,"bh"=>0);			//栏目：待审，已审，未审
		$countCheckedArticle = array("ds"=>0,"ys"=>0,"bh"=>0);		//文章：待审，已审，未审
		$countCheckedResLibWorldNews = array("ds"=>0,"ys"=>0,"bh"=>0);		//全球要闻：待审，已审，未审
		$countCheckedResLibHistoric = array("ds"=>0,"ys"=>0,"bh"=>0);		//历史上的今天：待审，已审，未审
		$countCheckedResLibFamousQuotes = array("ds"=>0,"ys"=>0,"bh"=>0);		//名人名言：待审，已审，未
		$countCheckedResLibBaike = array("ds"=>0,"ys"=>0,"bh"=>0);		//百科知识：待审，已审，未审
		$countCheckedResLibHumorJokes = array("ds"=>0,"ys"=>0,"bh"=>0);		//幽默笑话：待审，已审，未审
		
		//节目统计
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedProgram['ds'] = $programsModel->where($where)->count();//待审
		$countCheckedProgram['ys'] = $programsModel->where("checked = 1 and bevalid = 1")->count();//已审
		$countCheckedProgram['bh'] = $programsModel->where("checked = -1 and bevalid = 1")->count();//驳回
		
		//栏目组统计
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedDirGroup['ds'] = $pdgModel->where($where)->count();//待审
		$countCheckedDirGroup['ys'] = $pdgModel->where("checked = 1")->count();//已审
		$countCheckedDirGroup['bh'] = $pdgModel->where("checked = -1")->count();//驳回		

		//栏目统计
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedDir['ds'] = $columnModel->where($where)->count();//待审
		$countCheckedDir['ys'] = $columnModel->where("checked = 1")->count();//已审
		$countCheckedDir['bh'] = $columnModel->where("checked = -1")->count();//驳回			

		//文章统计
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedArticle['ds'] = $articleModel->where($where)->count();//待审
		$countCheckedArticle['ys'] = $articleModel->where("checked = 1")->count();//已审
		$countCheckedArticle['bh'] = $articleModel->where("checked = -1")->count();//驳回	
		
		//全球要闻
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedResLibWorldNews['ds'] = $reslibNewsModel->where($where)->count();//待审
		$countCheckedResLibWorldNews['ys'] = $reslibNewsModel->where("checked = 1")->count();//已审
		$countCheckedResLibWorldNews['bh'] = $reslibNewsModel->where("checked = -1")->count();//驳回		
		
		//历史上的今天
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedResLibHistoric['ds'] = $reslibHistoricModel->where($where)->count();//待审
		$countCheckedResLibHistoric['ys'] = $reslibHistoricModel->where("checked = 1")->count();//已审
		$countCheckedResLibHistoric['bh'] = $reslibHistoricModel->where("checked = -1")->count();//驳回		
		
		//名人名言
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedResLibFamousQuotes['ds'] = $reslibFamousQuotesModel->where($where)->count();//待审
		$countCheckedResLibFamousQuotes['ys'] = $reslibFamousQuotesModel->where("checked = 1")->count();//已审
		$countCheckedResLibFamousQuotes['bh'] = $reslibFamousQuotesModel->where("checked = -1")->count();//驳回		
		
		//百科知识
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedResLibBaike['ds'] = $reslibBaikeModel->where($where)->count();//待审
		$countCheckedResLibBaike['ys'] = $reslibBaikeModel->where("checked = 1")->count();//已审
		$countCheckedResLibBaike['bh'] = $reslibBaikeModel->where("checked = -1")->count();//驳回		
		
		//幽默笑话
		$where['_string']  = 'checked is null or checked = 0';
		$countCheckedResLibHumorJokes['ds'] = $reslibHumorJokesModel->where($where)->count();//待审
		$countCheckedResLibHumorJokes['ys'] = $reslibHumorJokesModel->where("checked = 1")->count();//已审
		$countCheckedResLibHumorJokes['bh'] = $reslibHumorJokesModel->where("checked = -1")->count();//驳回		
		
		$allCountDS = 
			$countCheckedProgram['ds']+
			$countCheckedDirGroup['ds']+
			$countCheckedDir['ds']+
			$countCheckedArticle['ds']+
			$countCheckedResLibHistoric['ds']+
			$countCheckedResLibFamousQuotes['ds']+
			$countCheckedResLibBaike['ds']+
			$countCheckedResLibHumorJokes['ds'];//全部待审核总数
			
		$allCountBH = 
			$countCheckedProgram['bh']+
			$countCheckedDirGroup['bh']+
			$countCheckedDir['bh']+
			$countCheckedArticle['bh']+
			$countCheckedResLibHistoric['bh']+
			$countCheckedResLibFamousQuotes['bh']+
			$countCheckedResLibBaike['bh']+
			$countCheckedResLibHumorJokes['bh'];//全部被驳回总数			
		
		$this->assign("allCountDS",$allCountDS);//所有待审，只显示给有审核权限的
		$this->assign("allCountBH",$allCountBH);//
    	$this->display();
    }
    
    public function test() {
    	header('Content-Type:text/html; charset=utf-8');
    	$ScenceModel = D('Scences');
    	$sList = $ScenceModel->field(array('id','classid','scencename','parentscence_id','ishomescence'))->order('parentscence_id asc, id asc')->select();
    	
    	$homeSecnce = null;
    	foreach ($sList as $scen) {
    		if ($scen['ishomescence'] == 'True') {
    			$homeSecnce = $scen;
    			break;
    		}
    	}
    	
    	if ($homeSecnce) {
    		$sTree = array();
    		$this->spTree($sTree, $homeSecnce);
    		/* echo '<pre>';
    		print_r($sTree);
    		echo '</pre>';
    		die(); */
    	}
    	
    	$this->assign('sTree', json_encode($sTree));
    	$this->display();
    }
    
    private function spTree(&$sTree, $parentScemce, $accIds) {
		
    	$sTree = array('id'=>$parentScemce['id']*1, 'name'=>gbk2utf8($parentScemce['scencename']), 'isParent'=>true, '_dataType'=>'scence');
    	if ($parentScemce['ishomescence'] == 'True') {
    		$sTree['open'] = true;
    		$sTree['iconSkin'] = 'root';
    	}
    	
    	$scenceModel = D('Scences');
    	$scencesWhere = array('parentscence_id'=>$parentScemce['classid']);
    	if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
    		$scencesWhere['id'] = array('in', $accIds['sids']);
    	}
    	$childrenScences = $scenceModel->where($scencesWhere)->order('id asc')->select();
    	if ($childrenScences) {
	    	foreach ($childrenScences as $cs) {
	    		$this->spTree($sTree['children'][], $cs, $accIds);
	    	}
    	}
    	
    	$pluginsModel = D('Plugins');
    	$itemManagerTypes = array_keys($this->pluginsTypes());
    	$pluginsWhere = array('belong_scenceid'=>$parentScemce['classid'], 'itemtype_classid'=>array('in', $itemManagerTypes));
		$pluginsWhere['allowbackdata'] = 1;
    	if (!$_SESSION[C('ADMIN_AUTH_KEY')])
    		$pluginsWhere['id'] = array('in', $accIds['pids']);
    	$plugins = $pluginsModel->field(array('id','name'))->where($pluginsWhere)->order('id asc')->select();
    	foreach ($plugins as $pi) {
	    	$sTree['children'][] = array('id'=>$pi['id']*1, 'name'=>gbk2utf8($pi['name']));
    	}
    }
}