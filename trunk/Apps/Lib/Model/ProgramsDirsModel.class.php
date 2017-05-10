<?php
/**
 * 栏目模型
 * @author Skeam TJ
 *
 */
class ProgramsDirsModel extends Model {
	
	/**
	 * 递归处理分类数据（排序）
	 * @param array $newList
	 * @param array $dataList
	 * @param number $pid
	 */
	public function sortNodes(&$newList, $dataList, $pid = 'root-0000-000-000', $level = 0) {
		foreach ($dataList as &$item) {
			if ($item['parent_classid'] == $pid) {
				if ($pid) {
					if ($newList[$pid]) {
						$newList[$pid]['has_children'] = 1;
						$item['path'] = $newList[$pid]['path'] . '_' . $item['classid'];
					} else {
						$item['path'] = $pid . '_' . $item['classid'];
					}
				} else {
					$item['path'] = $item['classid'];
				}
				
				//$item['space'] = str_repeat('&nbsp;&nbsp;', ($item['dir_level'])*4);
				$item['space'] = str_repeat('&nbsp;&nbsp;', $level*4);
				$newList[$item['classid']] = $item;
				
				$this->sortNodes($newList, $dataList, $item['classid'], $level +1);
			}
		}
	}
	
	
	/**
	 * 获取某个栏目下的所有栏目
	 * @param string $columnClassID
	 * @return array
	 */
	public function getBelongsColumns($columnClassID) {
		
		static $belongs = array();
		
		$children = $this->where(array('parent_classid'=>$columnClassID))->getField('classid', true);
		
		if (count($children) > 0) {
			foreach ($children as $item) {
				$this->getBelongsColumns($item);
			}
		}
		
		array_push($belongs, $columnClassID);
		return $belongs;
	}
	
	/**
	 * 更新栏目级别
	 * @param string $columnClassID
	 * @param int $level
	 */
	public function upColumnsLevel($columnClassID, $level) {
		
		$children = $this->where(array('parent_classid'=>$columnClassID))->getField('classid', true);
		
		if (count($children) > 0) {
			foreach ($children as $item) {
				$this->where(array('classid'=>$item))->setField('dir_level', $level+1);
				
				$this->upColumnsLevel($item, $level+1);
			}
		}
		
	}
	
	/**
	 * 生成树结构的数组
	 * @return array
	 */
	public function getZTreeData() {
		
		// 构建栏目树
		$dirs = $this->field(array('id', 'dir_name', 'classid', 'parent_classid', 'dirgroup_classid'))->select();
		
		$dirs2 = array();
		foreach ($dirs as &$dir) {
			$dir['dir_name'] = gbk2utf8($dir['dir_name']);
			$dir['name'] = $dir['dir_name'];
			$dir['node_type'] = 'column';
			$dir['unid'] = $dir['classid'];
			$dir['program_id'] = $this->table('tb_programs_dirs_groups')->where("dirgroup_classid='" . $dir['dirgroup_classid'] . "'")->getField('program_id');
			$dirs2[$dir['classid']] = $dir;
		}
		$dirTree = $this->generateTree($dirs2);
		
		// 节目权限控制
		$programNodeClassID = 'a69422aa-6077-6385-4ffd-1676c591a4cc';
		$where = 'bevalid = 1';
		if (!$_SESSION[C('ADMIN_AUTH_KEY')]) {
			$progAccess = M('Access')->where(array('role_id'=>$_SESSION['role'], 'node_id'=>$programNodeClassID))->count();
			if ($progAccess) {
				/*$accessCon = M('AccessCon')->where(array('role_id'=>$_SESSION['role'], 'con_classid'=>$programNodeClassID, 'con_type'=>'x86'))->getField('con_item_classid', true);
				if (!empty($accessCon)) {
					$cids = '';
					foreach ($accessCon as $cid) {
						$cids .= "'" . $cid . "',";
					}
					$where .= ' and program_classid in (' . trim($cids, ',') . ')';
				} else {
					$where .= " and program_classid = '00000000-0000-0000-0000-000000000000'";
				}*/
				
			} else {
				$where .= " and program_classid = '00000000-0000-0000-0000-000000000000'";
			}
		}
		
		// 构建节目、栏目组、栏目组成的树
		$prorams = $this->table('tb_programs')->field(array('id', 'program_name', 'program_classid', 'bevalid'))->where($where)->select();
		foreach ($prorams as &$prog) {
			$prog['program_name'] = $prog['name'] = gbk2utf8($prog['program_name']);
			$prog['node_type'] = 'program';
			$prog['unid'] = $prog['program_classid'];
			
			$groups = $this->table('tb_programs_dirs_groups')->where("program_id = '" . $prog['program_classid'] . "'")->field(array('id', 'dirgroup_name', 'dirgroup_classid', 'program_id'))->select();
			foreach ($groups as &$grop) {
				$grop['dirgroup_name'] = $grop['name'] = gbk2utf8($grop['dirgroup_name']);
				$grop['node_type'] = 'dirgroup';
				$grop['children'] = array();
				$grop['unid'] = $grop['dirgroup_classid'];
				foreach ($dirTree as &$dt) {
					if ($dt['dirgroup_classid'] == $grop['dirgroup_classid']) {
						$grop['children'][] = $dt;
					}
				}
			}
			if (!empty($groups)) {
				$prog['children'] = $groups;
			}
		}
		array_unshift($prorams, array('name'=>'所有节目', 'node_type'=>'all'));
		//dump($prorams);
		return $prorams;
	}
	
	/**
	 * 生成树结构的数组
	 * @param array $categories
	 * @return array
	 */
	public function generateTree ($dirs) {
	
		$tree = array();
	
		foreach($dirs as $dir){
				
			if (isset($dirs[$dir['parent_classid']])) {
				$dirs[$dir['parent_classid']]['children'][] = &$dirs[$dir['classid']];
			}else{
				$tree[] = &$dirs[$dir['classid']];
			}
		}
		return $tree;
	}
}