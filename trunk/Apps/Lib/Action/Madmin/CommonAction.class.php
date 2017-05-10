<?php
class CommonAction extends Action {
	
	public function _initialize() {
		
		import('@.ORG.RBAC');
	
		// 检测是否登录，没有登录就打回设置的网关
		Rbac::checkLogin();
		
		// 检测是否有权限没有权限就做相应的处理
		if(!Rbac::AccessDecision('Madmin')) {
			$this->error('没有访问权限！');
		}
		
		// 获取数据库配置信息，并动态加载
		dbConfig();
	}
	
	/**
	 * 插件过滤器
	 * @return array|NULL
	 */
	protected function pluginsTypes() {
		$pluginsTypes = D('PluginsTypes')->where(array('iswebmanager'=>'true'))->getField('itemtype_classid,itemtype_name');
		if ($pluginsTypes) {
			foreach ($pluginsTypes as &$pt) {
				$pt = gbk2utf8($pt);
			}
			
			return $pluginsTypes;
		} else {
			return null;
		}
	}
	
	/**
	 * 顶部主导航权限判断
	 * @return array
	 */
	protected function topNavAccess() {
		
		$topNavs = array();
		$perms = array(
				'homepage'	=>	'91f7b348-3d99-debd-a703-ac7ce6e03649',//后台首页
		 		'mall'	    =>	'a69422aa-6077-6385-4ffd-1676c591a4cc',//商场管理
				'template'	=>	'11bf8da9-ab17-96e6-47de-0c16a46c6c03',//模板管理				
		 		'programs'	=>	'a69422aa-6077-6385-4ffd-1676c591a4cc',//节目管理
		 		'endPoints'	=>	'3674e34f-c36f-32ed-bd2f-903923699adb',//（终端管
		 		'syscfg'	=>	'9a364967-7038-a751-c261-c4b0ccfd3533',//系统设置
		 		'ucenter'	=>	'd7ffe092-d6f5-3d0f-b87b-15f694e74b29',//用户管理
		 		'resLib'	=>	'5294fac0-f693-af84-ab62-745028978a5e',//资源管理
				'plan'		=>	'60f18087-2f05-39d2-7d46-011196d9624a',//资源管理
		 );
		
		if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
			$access = M('Access')->where(array('role_id'=>$_SESSION['role']))->getField('node_id', true);
			foreach ($perms as $key=>$item) {
				if (in_array($item, $access))
					array_push($topNavs, $key);
			}
		} else {
			$topNavs = array_keys($perms);
		}
		return $topNavs;
	}
	
	protected function conAccess() {		
		$roleAccessCon = M('AccessCon')->where(array('role_id'=>$_SESSION['role']))->select();
		$roleAccessCons = array();
		foreach ($roleAccessCon as $rac) {
			if ($rac['con_name'] == 'Programs') {
				//$roleAccessCons['Programs'][$rac['con_type']][] = $rac['con_item_classid'];
				$roleAccessCons['Programs'][] = $rac['con_type'];
			}
		
			if ($rac['con_name'] == 'EndPoints') {
				$roleAccessCons['EndPoints'][] = $rac['con_type'];
			}
		}
		
		return $roleAccessCons;
	}
	
	protected function topNavAccessOLD() {
		//$topNavs = array(2=>'homepage', 3=>'scences', 4=>'syscfg', 5=>'endPoints', 6=>'ucenter');
		$topNavs = array(
				'91f7b348-3d99-debd-a703-ac7ce6e03649'	=>	'homepage',
				'0e6732a8-347a-c0b4-614d-60cb70ec2d7c'	=>	'scences',
				'9a364967-7038-a751-c261-c4b0ccfd3533'	=>	'syscfg',
				'3674e34f-c36f-32ed-bd2f-903923699adb'	=>	'endPoints',
				'd7ffe092-d6f5-3d0f-b87b-15f694e74b29'	=>	'ucenter',
				'5294fac0-f693-af84-ab62-745028978a5e'	=>	'resLib',
				'd8596a55-2967-e28c-b63e-c11e9bdec390'	=>	'programs'
		);
		
		if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
			$topAccess = M('Access')->where(array('role_id'=>$_SESSION['role']))->getField('node_id', true);
			foreach ($topNavs as $k=>$item) {
				if (!in_array($k, $topAccess))
					unset($topNavs[$k]);
			}
		}
		
		return $topNavs;
	}
}