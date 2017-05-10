<?php
/**
 * 系统模块模型
 * @author Skeam TJ
 *
 */
class NodeModel extends Model {
	
	/**
	 * 递归处理分类数据（排序）
	 * @param array $newList
	 * @param array $dataList
	 * @param number $pid
	 */
	public function sortNodes(&$newList, $dataList, $pid = 0) {
		foreach ($dataList as &$item) {
			if ($item['pid'] == $pid) {
				if ($pid) {
					if ($newList[$pid]) {
						$newList[$pid]['has_children'] = 1;
						$item['path'] = $newList[$pid]['path'] . '_' . $item['id'];
					} else {
						$item['path'] = $pid . '_' . $item['id'];
					}
				} else {
					$item['path'] = $item['id'];
				}
				$item['space'] = str_repeat('&nbsp;&nbsp;', ($item['level']-1)*4);
				$newList[$item['id']] = $item;
				$this->sortNodes($newList, $dataList, $item['id']);
			}
		}
	}
	
	/**
	 * 递归处理分类数据（子属）
	 * @param array $newList
	 * @param array $dataList
	 * @param number $pid
	 */
	public function treeNodes(&$newList, $dataList, $pid = 0) {
		foreach ($dataList as &$item) {
			if ($item['pid'] == $pid) {
				if ($pid) {
	
					$item['space'] = str_repeat('&nbsp;&nbsp;', $item['level']*4);
	
					if ($newList[$pid]) {
	
						$item['path'] = $newList[$pid]['path'] . '_' . $item['id'];
						$newList[$pid]['has_children'] = 1;
						$newList[$pid]['children'][$item['id']] = $item;
	
					} else {
						$item['path'] = $pid . '_' . $item['id'];
						$newList[$item['id']] = $item;
					}
				} else {
					$item['path'] = $item['id'];
					$newList[$item['id']] = $item;
				}
	
				$this->treeNodes($newList, $dataList, $item['id']);
			}
		}
	}
}