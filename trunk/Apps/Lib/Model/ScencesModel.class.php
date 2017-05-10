<?php
/**
 * 场景模型
 * @author Skeam TJ
 *
 */
class ScencesModel extends Model {
	protected $trueTableName = 'TB_Scences';
	
    protected $_validate = array(
        array('playorder', '/\d+/', '必须输入数字', 2, 'regex'),
        array('playorder', 'checkOrder', '顺序从0开始', 2, 'callback'),
        array('playtime', '/\d+/', '必须输入数字', 2, 'regex'),
        array('playtime', 'checkTime', '时间不得小于0', 2, 'callback'),
    );

    protected function checkOrder($order){
        return $order < 0 ? false : true;
    }

    protected function checkTime($time){
        if($time < 0){
            return false;
        }else{
            return true;
        }
    }	
	
	
	//排序数组为树形，用于前端显示
	public function sortedTypes(&$newList, $dataList, $parentID = '', $level = 1) {
		foreach ($dataList as &$item) {
			if ($item['parentscence_id'] == $parentID) {
				if (!empty($parentID)) {
					if ($newList[$parentID]) {
						$newList[$parentID]['has_children'] = 1;
						$item['path'] = $newList[$parentID]['path'] . '_' . $item['classid'];
					} else {
						$item['path'] = $parentID . '_' . $item['classid'];
					}
				} else {
					$item['path'] = $item['classid'];
				}
				$item['level'] = $level;
				$item['space'] = str_repeat('&nbsp;&nbsp;', ($level-1)*4);
				$newList[$item['classid']] = $item;
				$this->sortedTypes($newList, $dataList, $item['classid'], $level+1);
			}
		}
	}
}