<?php
/**
 * 终端组模型
 * @author Skeam TJ
 *
 */
class EndpointsGroupsModel extends Model {
	protected $trueTableName = 'TB_TEndpoint_Group';
	
	/**
	 * 递归处理分类数据（排序）
	 * @param array $newList
	 * @param array $dataList
	 * @param number $pid
	 */
	public function sortNodes(&$newList, $dataList, $parentClassId = '') {
		foreach ($dataList as &$item) {
			if ($item['parent_classid'] == $parentClassId) {
				if ($parentClassId) {
					if ($newList[$parentClassId]) {
						$newList[$parentClassId]['has_children'] = 1;
						$item['path'] = $newList[$parentClassId]['path'] . '_' . $item['groupclassid'];
					} else {
						$item['path'] = $parentClassId . '_' . $item['groupclassid'];
					}
				} else {
					$item['path'] = $item['groupclassid'];
				}
				$item['space'] = str_repeat('&nbsp;&nbsp;', ($item['level']-1)*4);
				$newList[$item['groupclassid']] = $item;
				$this->sortNodes($newList, $dataList, $item['groupclassid']);
			}
		}
	}
	
	/**
	 * 获取终端组的下属组CLASSID
	 * @param string $groupclassid
	 * @return array
	 */
	public function getChildrenGrps($groupclassid) {
		
		static $childrenGrps = array();
		
		array_push($childrenGrps, $groupclassid);
		
		$children = $this->where(array('parent_classid'=>$groupclassid))->getField('groupclassid', true);
		if (!empty($children)) {
			$grp = null;
			foreach ($children as $grp) {
				$this->getChildrenGrps($grp);
			}
		}		
		
		return $childrenGrps;
	}
	
	/**
	 * 更新终端组层级标识
	 * @param string $groupclassid
	 * @param number $level
	 */
	public function upChildrenLevel($groupclassid, $level) {
		
		$children = $this->where(array('parent_classid'=>$groupclassid))->getField('groupclassid', true);
		
		if (count($children) > 0) {
			foreach ($children as $item) {
				$this->where(array('groupclassid'=>$item))->setField('level', $level+1);
				
				$this->upChildrenLevel($item, $level+1);
			}
		}
	}
	
	/**
	 * 生成树结构的数组
	 * @return array
	 */
	public function getZTreeData($endType = 'x86', $expandAll = false) {
	
		$groups = $this->where(array('grouptype'=>$endType))->field(array('id', 'groupname', 'groupclassid', 'parent_classid', 'level', 'tplclassid', 'isChanged'))->select();
	
		$groupsNew = array();
		$grp = null;
		foreach ($groups as &$grp) {
			$grp['name'] = $grp['groupname'];
			$grp['unid'] = $grp['groupclassid'];
			$grp['endtype'] = $endType;
			$grp['open'] = $expandAll;
			$groupsNew[$grp['groupclassid']] = $grp;
		}
		
		$groupTree = $this->generateTree($groupsNew);
		array_unshift($groupTree, array('name'=>'所有终端', 'id'=>0, 'endtype'=>$endType));
		
		return $groupTree;
		
	}
	
	/**
	 * 生成树结构的数组
	 * @param array $categories
	 * @return array
	 */
	public function generateTree ($groups) {
	
		$tree = array();
	
		$grp = null;
		foreach($groups as $grp){
	
			if (isset($groups[$grp['parent_classid']])) {
				$groups[$grp['parent_classid']]['children'][] = &$groups[$grp['groupclassid']];
			}else{
				$tree[] = &$groups[$grp['groupclassid']];
			}
		}
		return $tree;
	}
	
	public function getGroupPath($groupClassID) {
		
		static $gpath = array();
		
		$groupInfo = $this->where(array('groupclassid'=>$groupClassID))->find();
		
		if (trim($groupInfo['parent_classid']) != '') {
			$this->getGroupPath($groupInfo['parent_classid']);
		}
		
		array_push($gpath, $groupInfo);
		
		return $gpath;
	}
}