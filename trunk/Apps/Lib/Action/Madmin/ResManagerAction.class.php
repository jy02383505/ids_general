<?php
/**
 * 素材管理控制器
 * @author Skeam TJ
 *
 */
class ResManagerAction extends CommonAction {
	
	public function index() {
		
		// 根据层级获取资源列表，默认获取顶级即level为1的层级
		$rmModel = D('ResmanagerDirs');
		$rmfModel = M('ResmanagerFiles');
		$this->assign('treeData', json_encode($rmModel->getZTreeData()));
		
		// 获取子属分类
		$cateList = $rmModel->field(array('id','dir_name','parent_classid','classid'))->where(array('dir_level'=>1,'dir_status'=>1))->select();
		foreach ($cateList as &$cate) {
			$cate['dir_name'] = gbk2utf8($cate['dir_name']);
			// 检测分类是否有子分类
			$subCateCounts = $rmModel->where(array('parent_classid'=>$cate['classid']))->count();
			$subFileCounts = $rmfModel->where(array('belong_dirclassid'=>$cate['classid']))->count();
			$cate['has_sub'] = ($subCateCounts + $subFileCounts) > 0 ? 1 : 0;
		}
		$this->assign('categories', $cateList);
		
		// 获取子属文件
		$fileList = $rmfModel->field(array('id', 'classid', 'filename', 'filetype', 'filepath', 'ctime_stamps'))->where(array('belong_dirclassid'=>'', 'status'=>1))->select();
		foreach ($fileList as &$file) {
			$file['filename'] = gbk2utf8($file['filename']);
			$file['filepath'] = gbk2utf8($file['filepath']);
			$file['ctime'] = date('Y-m-d H:i:s', $file['ctime_stamps']);
		}
		$this->assign('files', $fileList);
		
		$this->display();
	}
	
	/**
	 * 添加新分类
	 */
	public function addCategory() {
		$catename = trim(I('post.catename'));
		$pid = I('post.pid', 0, 'int');
		
		if (empty($catename)) {
			exit(json_encode(array('stat'=>0, 'msg'=>'分类名称不能为空！')));
		}
		
		$rmModel = M('ResmanagerDirs');
		$data['dir_name'] = utf82gbk($catename);
		$data['classid'] = generateUniqueID();
		$data['dir_status'] = 1;
		$data['dir_ico'] = '';
		$data['remark'] = '';
		if ($pid) {
			$pCate = $rmModel->field(array('classid', 'dir_level'))->where(array('id'=>$pid))->find();
			if ($pCate) {
				// 检查文件名是否已存在
				if ($rmModel->where(array('parent_classid'=>$pCate['classid'], 'dir_name'=>utf82gbk($catename)))->count() > 0) {
					exit(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：已存在名称为 ' . $catename . ' 的目录.')));
				}
				$data['parent_classid'] = $pCate['classid'];
				$data['dir_level'] = $pCate['dir_level'] + 1;
			} else {
				// 检查文件名是否已存在
				if ($rmModel->where(array('dir_level'=>1, 'dir_name'=>utf82gbk($catename)))->count() > 0) {
					exit(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：已存在名称为 ' . $catename . ' 的目录.')));
				}
				$data['parent_classid'] = '';
				$data['dir_level'] = 1;
			}
		} else {
			// 检查文件名是否已存在
			if ($rmModel->where(array('dir_level'=>1, 'dir_name'=>utf82gbk($catename)))->count() > 0) {
				exit(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：已存在名称为 ' . $catename . ' 的目录.')));
			}
			$data['parent_classid'] = '';
			$data['dir_level'] = 1;
		}
		
		$addRe = $rmModel->add($data);
		if ($addRe !== false) {
			$latestID = $rmModel->getLastInsID();
			$catInfo = $rmModel->field(array('id', 'dir_name', 'classid', 'parent_classid'))->where(array('id'=>$latestID))->find();
			if ($catInfo) {
				$catInfo['dir_name'] = gbk2utf8($catInfo['dir_name']);
			}
			exit(json_encode(array('stat'=>1, 'catId'=>$latestID, 'catInfo'=>$catInfo)));
		} else {
			exit(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $rmModel->getError())));
		}
	}
	
	/**
	 * 获取分类信息
	 */
	public function getCategory() {
		$catId = I('post.catId', 0, 'int');
		
		$rmModel = M('ResmanagerDirs');
		$rmfModel = M('ResmanagerFiles');
		$catInfo = $breadcrumbArr = $subCateList = $subFilesList = array();
		$where = array('dir_status'=>1);
		$fileWhere = array('status'=>1);
		
		if ($catId) {
			
			// 获取分类信息
			$catInfo = $rmModel->where(array('id'=>$catId))->find();
			if (!$catInfo) {
				exit(json_encode(array('stat'=>0, 'msg'=>'数据请求错误！请刷新页面重试……')));
			}
			$catInfo['dir_name'] = gbk2utf8($catInfo['dir_name']);
			
			$where['parent_classid'] = $catInfo['classid'];
			$fileWhere['belong_dirclassid'] = $catInfo['classid'];
			
			// 获取面包屑数据
			$breadcrumbArr = $this->genBreadcrumb($catInfo);
		} else {
			$where['dir_level'] = 1;
			$fileWhere['belong_dirclassid'] = '';
		}
		
		// 获取子属分类
		$subCateList = $rmModel->where($where)->select();
		foreach ($subCateList as &$cate) {
			$cate['dir_name'] = gbk2utf8($cate['dir_name']);
			// 检测分类是否有子分类
			$subCateCounts = $rmModel->where(array('parent_classid'=>$cate['classid']))->count();
			$subFileCounts = $rmfModel->where(array('belong_dirclassid'=>$cate['classid']))->count();
			$cate['has_sub'] = ($subCateCounts + $subFileCounts) > 0 ? 1 : 0;
		}
		
		// 获取子属文件
		$subFilesList = $rmfModel->field(array('id', 'classid', 'filename', 'filetype', 'filepath', 'ctime_stamps'))->where($fileWhere)->select();
		foreach ($subFilesList as &$file) {
			$file['filename'] = gbk2utf8($file['filename']);
			$file['filepath'] = gbk2utf8($file['filepath']);
			$file['ctime'] = date('Y-m-d H:i:s', $file['ctime_stamps']);
		}
		
		$returnArr = array(
			'cateinfo'		=>	$catInfo,
			'breadcrumb'	=>	$breadcrumbArr,
			'childrenCats'	=>	$subCateList,
			'childrenFiles'	=>	$subFilesList
		);
		
		exit(json_encode(array('stat'=>1, 'data'=>$returnArr)));
	}
	
	/**
	 * 生成面包屑数据
	 */
	private function genBreadcrumb($category) {
		static $breadcrumb = array();
		
		if (trim($category['parent_classid']) != '') {
			
			$rmModel = M('ResmanagerDirs');
			$pcatInfo = $rmModel->where(array('classid'=>$category['parent_classid']))->find();
			$breadcrumb[$pcatInfo['id']] = gbk2utf8($pcatInfo['dir_name']);
			$this->genBreadcrumb($pcatInfo);
		}
		
		return $breadcrumb;
	}
	
	public function delCategories() {
		$ids = trim(I('post.ids'), '-');
		$resType = trim(I('post.resType'));
		
		if (!empty($ids) && count(explode('-', $ids)) > 0) {
			
			$sucaiRootPath = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/';
			if ($resType == 'dir') {
				$rmModel = D('ResmanagerDirs');
				
				foreach (explode('-', $ids) as $cid) {
					$dirPath = $sucaiRootPath . trim($rmModel->genFileSavePath($cid), '/');
					if (is_dir($dirPath) && $dirPath != $sucaiRootPath) {
						@rmdir($dirPath);
					}
				}
				
				$delRe = $rmModel->where(array('id'=>array('in', explode('-', $ids))))->delete();
			} else {
				$rmfModel = M('ResmanagerFiles');
				$fileList = $rmfModel->where(array('id'=>array('in', explode('-', $ids))))->select();
				foreach ($fileList as $file) {
					$file['filepath'] = utf8ToGbk($file['filepath']);
					if (trim($file['filepath']) != '' && is_file($sucaiRootPath . $file['filepath'])) {
						@unlink($sucaiRootPath . $file['filepath']);
					}
				}
				$delRe = $rmfModel->where(array('id'=>array('in', explode('-', $ids))))->delete();
			}
			
			if ($delRe !== false) {
				exit(json_encode(array('stat'=>1)));
			} else {
				exit(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $rmModel->getError())));
			}
		} else {
			exit(json_encode(array('stat'=>0, 'msg'=>'数据请求错误！请刷新页面重试……')));
		}
	}
}