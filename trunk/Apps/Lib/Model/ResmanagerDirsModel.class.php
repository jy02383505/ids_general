<?php
/**
 * 素材库目录模型
 * @author Skeam TJ
 *
 */
class ResmanagerDirsModel extends Model {
	
	/**
	 * 生成资源存放路径
	 * @param number $catID
	 * @return string
	 */
	public function genFileSavePath ($catID) {
		//static $savepath = array();
		static $savepath = '';
		
		$cate = $this->field(array('id','classid','parent_classid', 'dir_name'))->where(array('id'=>$catID))->find();
		if ($cate) {
			if (trim($cate['parent_classid']) != '') {
				$pcate = $this->field(array('id','classid','parent_classid', 'dir_name'))->where(array('classid'=>$cate['parent_classid']))->find();
				if ($pcate) {
					$this->genFileSavePath($pcate['id']);
				} else{
					//$savepath[] = $pcate['id'] . '-' . gbk2utf8($pcate['dir_name']);
					//$savepath[] = $pcate['id'] . '-' . gbk2utf8($pcate['classid']);
					$savepath .= $pcate['classid'] . '/';
				}
			}
			
			//$savepath[] = $cate['id'] . '-' . gbk2utf8($cate['dir_name']);
			//$savepath[] = $cate['id'] . '-' . gbk2utf8($cate['classid']);
			$savepath .= $cate['classid'] . '/';
		}
		
		return $savepath;
	}
	
	public function genFileSavePath2 ($catID, &$spath) {
		
		$cate = $this->field(array('id','classid','parent_classid', 'dir_name'))->where(array('id'=>$catID))->find();
		if ($cate) {
			if (trim($cate['parent_classid']) != '') {
				$pcate = $this->field(array('id','classid','parent_classid', 'dir_name'))->where(array('classid'=>$cate['parent_classid']))->find();
				if ($pcate) {
					$this->genFileSavePath2($pcate['id'], $spath);
				} else{
					$spath .= $pcate['classid'] . '/';
				}
			}
			
			$spath .= $cate['classid'] . '/';
		}
		//file_put_contents('log.txt', $spath . PHP_EOL, FILE_APPEND);
	}
	
	/**
	 * 生成树结构的数组
	 * @return array
	 */
	public function getZTreeData() {
		$categories = $this->field(array('id', 'dir_name', 'classid', 'parent_classid'))->select();
		
		$categories2 = array();
		foreach ($categories as &$cate) {
			$cate['dir_name'] = gbk2utf8($cate['dir_name']);
			$cate['name'] = $cate['dir_name'];
			$categories2[$cate['classid']] = $cate;
		}
		
		return $this->generateTree($categories2);
	}
	
	/**
	 * 生成树结构的数组
	 * @param array $categories
	 * @return array
	 */
	public function generateTree ($categories) {
		
		$tree = array();
		
		foreach($categories as $cate){
			
			if (isset($categories[$cate['parent_classid']])) {
				$categories[$cate['parent_classid']]['children'][] = &$categories[$cate['classid']];
			}else{
				$tree[] = &$categories[$cate['classid']];
			}
		}
		return $tree;
	}
	
	public function getChildrenCates($catId, $type = 'int') {
	    
	    static $childrenIds = array();
	    
	    if ($type == 'int') {
	        $classId = $this->where(array('id'=>$catId))->getField('classid');
	        array_push($childrenIds, $classId);
	    } else { 
	        $classId = $catId;
	    }
	    
	    $children = $this->field(array('id', 'classid'))->where(array('parent_classid'=>$classId))->select();
	    if(!empty($children)) {
	        foreach ($children as $child) {
	            array_push($childrenIds, $child['classid']);
	            $this->getChildrenCates($child['classid'], 'str');
	        }
	    }
	    
	    return $childrenIds;
	}
	
}