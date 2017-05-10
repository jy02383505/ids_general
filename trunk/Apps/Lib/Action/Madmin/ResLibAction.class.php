<?php
/**
 * 资源库控制器
* @author Skeam Tj
*
*/
class ResLibAction extends CommonAction {
	
	/**
	 * 素材管理首页
	 */
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
	
		$this->display('ResManager:index');
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
	    
		$dirsIids = trim(I('post.dids'), '-');
		$filesIds = trim(I('post.fids'), '-');
		
		if (empty($dirsIids) && empty($filesIds)) {
		    exit(json_encode(array('stat'=>0, 'msg'=>'非法操作！')));
		}
		
		if (!empty($dirsIids)) {
		    $this->delResByDirs($dirsIids);
		}
		
		if (!empty($filesIds)) {
			$this->delResbyFiles($filesIds);
		}
			
		exit(json_encode(array('stat'=>1)));
	}
	
	private function delResByDirs($ids) {
	    
	    $sucaiRootPath = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/';
	    $dirsClassIds = array();
	    $rmModel = D('ResmanagerDirs');
	    $rmfModel = M('ResmanagerFiles');
	    
	    foreach (explode('-', $ids) as $cid) {
	        $dirsClassIds = array_merge($dirsClassIds, $rmModel->getChildrenCates($cid));
	    }
	    
	    $delFilesRe = $rmfModel->where(array('belong_dirclassid'=>array('in', $dirsClassIds)))->delete();
	    if ($delFilesRe !== false) {
	        
    	    foreach (explode('-', $ids) as $cid) {
    	        $spath = '';
    	        $rmModel->genFileSavePath2($cid, $spath);
    	        $dirPath = $sucaiRootPath . trim($spath, '/');
    	        if (is_dir($dirPath) && $dirPath != $sucaiRootPath) {
    	            //@rmdir($dirPath);
    	            deldir($dirPath);
    	    
    	            $thumbSavepath = rtrim(C('UPLOAD_THUMB_PATH'), '/') . '/' . $spath;
    	            if (is_dir($thumbSavepath) && rtrim($thumbSavepath, '/') != rtrim(C('UPLOAD_THUMB_PATH'), '/')) {
    	                deldir($thumbSavepath);
    	            }
    	        }
    	    }
    	    
	        $delDirsRe = $rmModel->where(array('classid'=>array('in', $dirsClassIds)))->delete();
	    } else {
	        
	        exit(json_encode(array('stat'=>0, 'msg'=>'非法操作！[原因DIR]：' . $rmfModel->getError())));
	    }

	}
	
	private function delResbyFiles($ids) {
	    $sucaiRootPath = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/';
	    $rmfModel = M('ResmanagerFiles');
	    
	    $fileList = $rmfModel->where(array('id'=>array('in', explode('-', $ids))))->select();
	    $delRe = $rmfModel->where(array('id'=>array('in', explode('-', $ids))))->delete();
	    
	    if ($delRe !== false) {
	        
    	    foreach ($fileList as $file) {
    	        $file['filepath'] = utf8ToGbk($file['filepath']);
    	        if (trim($file['filepath']) != '' && is_file($sucaiRootPath . $file['filepath'])) {
    	            @unlink($sucaiRootPath . $file['filepath']);
    	    
    	            // 检查并删除缩略图
    	            if (is_file(rtrim(C('UPLOAD_THUMB_PATH'), '/') . '/' . $file['filepath'])) {
    	                @unlink(rtrim(C('UPLOAD_THUMB_PATH'), '/') . '/' . $file['filepath']);
    	            }
    	        }
    	    }
	    
	    } else {
	        exit(json_encode(array('stat'=>0, 'msg'=>'非法操作！[原因FILE]：' . $rmfModel->getError())));
	    }
	    
	}
	
	/**
	 * 全球要闻（图片新闻）
	 */
	public function worldNews() {
		$newsModel = M('ReslibNews');
		$where = array();
		$newsTitle = trim(I('get.news_title'));
		if (!empty($newsTitle)) {
			$where['news_title'] = array('like', '%' . $newsTitle . '%');
		}
		
		// 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $newsModel->where($where)->count();
		$Page = new Page($totals, 12);
		$show = $Page->show();
		$this->assign('page', $show);
		
		$newsList = $newsModel->field(array('id', 'news_title', 'news_content', 'news_date'))->where($where)->order('news_date desc, id desc')->limit($Page->firstRow. ',' .$Page->listRows)->select();
		foreach ($newsList as &$news) {
			$news['news_title'] = stripslashes($news['news_title']);
			$news['news_content'] = stripslashes($news['news_content']);
		    $news['news_content_short'] = mb_strlen($news['news_content'], 'UTF-8') > 60 ? mb_substr($news['news_content'], 0, 60, 'UTF-8') . '...' : $news['news_content'];
		}
		
		$this->assign('newsList', $newsList);
		$this->display('ResManager:worldNews');
	}
	
	/**
	 * 新闻内容（图集）
	 */
	public function newsGallery() {
		$newsId = I('get.id', 0, 'int');
		
		if (!$newsId) {
			$this->error('非法请求！');
		}
		
		$newsModel = M('ReslibNews');
		$newsInfo = $newsModel->field(array('id', 'news_title', 'news_content', 'news_date'))->where(array('id'=>$newsId))->find();
		if (!$newsInfo) {
			$this->error('非法请求！');
		}
		$newsInfo['news_title'] = stripslashes($newsInfo['news_title']);
		$newsInfo['news_content'] = stripslashes($newsInfo['news_content']);
		$this->assign('newsinfo', $newsInfo);
		
		$galleryModel = M('ReslibNewsgallery');
		$gallery = $galleryModel->where(array('news_id'=>$newsId))->select();
		foreach ($gallery as &$ga) {
			$ga['note'] = stripslashes($ga['note']);
		}
		$this->assign('gallery', $gallery);
		
		$this->display('ResManager:newsGallery');
	}
	
	/**
	 * 导入新闻数据
	 */
	public function importNewsData() {
		header('Content-Type:text/html;charset=utf-8');
		$importRootDir = trim(C('IMPORT_DATA_PATH'), '/') . '/';
		$importFilepath = $importRootDir . 'newsEx/data.xls';
		$importImagepath = $importRootDir . 'newsEx/images/';
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			echoMsg('没有可导入数据！请检查数据目录……');
			exit();
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$newsData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($newsData);
		if (empty($newsData)) {
			echoMsg('没有可导入数据！请检查数据文件……');
			exit();
		}
		
		$news = array();
		foreach ($newsData as $ni) {
			$tmp = array();
			$tmp['ex_id'] = $ni['A'];
			$tmp['news_title'] = $ni['B'];
			$tmp['news_content'] = $ni['C'];
			$tmp['news_date'] = $ni['D'];
			$tmp['news_source'] = $ni['E'];
			$tmp['create_time'] = $ni['F'];
			$tmp['ctime_stamps'] = $ni['G'];
			array_push($news, $tmp);
		}
		//dump($news);
		
		$PHPExcel->setActiveSheetIndex(1);
		$galleryData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($galleryData);
		//dump($galleryData);
		if (!empty($galleryData)) {
			$gallery = array();
			foreach ($galleryData as $gi) {
				$tmp = array();
				$tmp['ex_id'] = $gi['A'];
				$tmp['news_id'] = $gi['B'];
				$tmp['image'] = $gi['C'];
				$tmp['note'] = $gi['D'];
				$tmp['create_time'] = $gi['E'];
				$tmp['ctime_stamps'] = $gi['F'];
				array_push($gallery, $tmp);
			}
		}
		//dump($gallery);
		
		if (count($news) > 0 && count($gallery) > 0) {
			
			$item = null;
			foreach ($news as &$item) {
				
				$imginfo = null;
				foreach ($gallery as $imginfo) {
					
					if ($item['ex_id'] == $imginfo['news_id']) {
						$item['gallery'][] = $imginfo;
					}
					
				}
				
			}
			
		}
		
		$newsModel = M('ReslibNews');
		$galleryModel = M('ReslibNewsgallery');
		$item = null;
		foreach ($news as $item) {
			
			$ndata = $item;
			unset($ndata['ex_id']);
			$nInsRe = $newsModel->add($ndata);
			if ($nInsRe && !empty($ndata['gallery'])) {
				$latestNewsId = $newsModel->getLastInsID();
				
				$sitem = null;
				foreach ($ndata['gallery'] as $sitem) {
					$gdata = $sitem;
					unset($gdata['ex_id']);
					$gdata['news_id'] = $latestNewsId;
					$re = $galleryModel->add($gdata);
					if (!$re) {
						echoMsg('错误ID：' . $latestNewsId . '， [错误信息]： ' . $galleryModel->getError());
					}
					
					$imgPath = trim($sitem['image']);
					if (!empty($imgPath)) {
						$sourcePath = $importImagepath . ltrim($imgPath, '/');
						if (is_file($sourcePath)) {
							$imgDirname = trim(dirname($imgPath), '/');
							$imgBasename = basename($imgPath);
							$targetDir = 'Uploads/reslib/news/' . $imgDirname;
							if (!is_dir($targetDir)) {
								@mkdir($targetDir, 0777, true);
							}
							if (!copy($sourcePath, $targetDir . '/' . $imgBasename)) {
								echoMsg('错误ID：' . $latestNewsId . '， [错误信息]： 文件 ' . $imgPath . ' 复制错误!');
							}
						}
					}
				}
			}
		}
		echoMsg('数据导入完成！');
		exit();
	}
	
	/**
	 * 删除新闻
	 */
	public function delNews() {
	    $newsIds = trim(I('post.nids'));
	     
	    if (empty($newsIds)) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	     
	    $newsIdsArr = explode(',', trim($newsIds, ','));
	    if (count($newsIdsArr) <= 0) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	     
	    $newsModel = M('ReslibNews');
	    $galleryModel = M('ReslibNewsgallery');
	
	    $newsImgRootPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/Uploads/reslib/news/';
	    foreach ($newsIdsArr as $newsId) {
	        $gallery = $galleryModel->field(array('id', 'image'))->where(array('news_id'=>$newsId))->select();
	        foreach ($gallery as $gall) {
	            $gallResult = $galleryModel->where(array('id'=>$gall['id']))->delete();
	            if ($gallResult !== false) {
	                if (is_file($newsImgRootPath . $gall['image'])) {
	                    @unlink($newsImgRootPath . $gall['image']);
	                }
	            } else {
	    	           die(json_encode(array('stat'=>0, '操作失败！删除相册记录失败！[原因]：' . $galleryModel->getError())));
	            }
	        }
	    }
	
	    $newsResult = $newsModel->where(array('id'=>array('in', $newsIdsArr)))->delete();
	    if ($newsResult !== false) {
	        die(json_encode(array('stat'=>1, '操作成功！')));
	    } else {
	        die(json_encode(array('stat'=>0, '操作失败！[原因]：' . $newsModel->getError())));
	    }
	}
	
	/**
	 * 批量审核新闻
	*/
	public function multiCheckWorldNews() {
		$artiClassIDs = trim(I('post.aids'), ';');//去掉结尾的;
		$aidsArr = explode(';', $artiClassIDs);//转成数组

		if (empty($artiClassIDs) || empty($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'页面数据错误，请刷新页面重试……！')));
		}
		
		$data = array();
		
		$checkedType = trim(I('post.checkedType'));
		//过滤：已审核/待审核/已驳回
		if (!empty($checkedType)){
			switch ($checkedType){
				case "ys"://已审
					$checkMessageValue = '审核通过';
					$dotype = 1;
					break;
				case "ds"://待审
					$checkMessageValue = '取消审核';
					$dotype = 0;
					break;
				case "bh"://驳回
					$checkMessageValue = substr(trim(I('post.checkMessage')),0,255);//附加消息
					$dotype = -1;
					break;
				default:
					$dotype = 0;//全部
			}
		}else{
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败，请重试……！')));
		}
		
		$data['checked'] = $dotype;
		
		$newsModel = M('ReslibNews');
		$succCounts = $failCounts = 0;
		foreach ($aidsArr as $aid) {
			
			$datas = $newsModel->where(array('id'=>$aid))->find();
			if (!$datas) {
				$failCounts++;
				continue;
			}else{
				$rid = $datas['id'];//写日志用
			}
			
			
			//$data = array('checked'=>'1');
			$re = $newsModel->where(array('id'=>$aid))->setField($data);;
			if ($re !== false) {
				$succCounts++;
				
				//写审核日志 START
				$checkLogModel = D("CheckLog");
				//$checkMessageValue = "审核通过";
				$checkLogModel->writeNewLog($dotype,$checkMessageValue,"resourceWorldNews",$rid,$aid);
				//写审核日志 END
				
			} else {
				$failCounts++;
			}
		}
		
		if ($failCounts == count($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'网络错误导致操作失败，请刷新页面重试……！')));
		} else {
			die(json_encode(array('stat'=>1)));
		}
	}
	
	/**
	 * 历史上的今天
	 */
	public function historic () {
		$hisModel = M('ReslibHistoric');
		$where = array();
		$month = I('get.month', 0, 'int');
		$day = I('get.day', 0, 'int');
		$where['today_month'] = $month ? $month : date('n');
		$where['today_day'] = $day ? $day : date('j');
		$_GET['month'] = $where['today_month'];
		$_GET['day'] = $where['today_day'];
		$eventTitle = trim(I('get.event_title'));
		if (!empty($eventTitle)) {
			$where['event_title'] = array('like', '%' . $eventTitle . '%');
		}
		
		// 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $hisModel->where($where)->count();
		$Page = new Page($totals, 12);
		$show = $Page->show();
		$this->assign('page', $show);
		
		$hisList = $hisModel->field(array('id', 'event_title', 'event_content'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();
		
		$this->assign('hisList', $hisList);
		$this->display('ResManager:historic');
	}

	public function delHistoric () {
	    $ids = trim(I('post.nids'));
	
	    if (empty($ids)) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $idsArr = explode(',', trim($ids, ','));
	    if (count($idsArr) <= 0) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $resModel = M('ReslibHistoric');
	    $resResult = $resModel->where(array('id'=>array('in', $idsArr)))->delete();
	    if ($resResult !== false) {
	        die(json_encode(array('stat'=>1, '操作成功！')));
	    } else {
	        die(json_encode(array('stat'=>0, '操作失败！[原因]：' . $resModel->getError())));
	    }
	}
	
	/**
	 * 批量审核历史上的今天
	*/
	public function multiCheckHistoric() {
		$artiClassIDs = trim(I('post.aids'), ';');//去掉结尾的;
		$aidsArr = explode(';', $artiClassIDs);//转成数组

		if (empty($artiClassIDs) || empty($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'页面数据错误，请刷新页面重试……！')));
		}
		
		$data = array();
		
		$checkedType = trim(I('post.checkedType'));
		//过滤：已审核/待审核/已驳回
		if (!empty($checkedType)){
			switch ($checkedType){
				case "ys"://已审
					$checkMessageValue = '审核通过';
					$dotype = 1;
					break;
				case "ds"://待审
					$checkMessageValue = '取消审核';
					$dotype = 0;
					break;
				case "bh"://驳回
					$checkMessageValue = substr(trim(I('post.checkMessage')),0,255);//附加消息
					$dotype = -1;
					break;
				default:
					$dotype = 0;//全部
			}
		}else{
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败，请重试……！')));
		}
		
		$data['checked'] = $dotype;
		
		$resModel = M('ReslibHistoric');
		$succCounts = $failCounts = 0;
		foreach ($aidsArr as $aid) {
			
			$datas = $resModel->where(array('id'=>$aid))->find();
			if (!$datas) {
				$failCounts++;
				continue;
			}else{
				$rid = $datas['id'];//写日志用
			}
			
			
			//$data = array('checked'=>'1');
			$re = $resModel->where(array('id'=>$aid))->setField($data);;
			if ($re !== false) {
				$succCounts++;
				
				//写审核日志 START
				$checkLogModel = D("CheckLog");
				//$checkMessageValue = "审核通过";
				$checkLogModel->writeNewLog($dotype,$checkMessageValue,"resourceHistoric",$rid,$aid);
				//写审核日志 END
				
			} else {
				$failCounts++;
			}
		}
		
		if ($failCounts == count($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'网络错误导致操作失败，请刷新页面重试……！')));
		} else {
			die(json_encode(array('stat'=>1)));
		}
	}	
	
	/**
	 * 名人名言
	 */
	public function famousQuotes () {
		$fqModel = D('FamousQuotes');
		$where = array();
		$author = trim(I('get.author'));
		if (!empty($author)) {
			$where['author'] = array('like', '%' . $author . '%');
		}
		
		// 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $fqModel->where($where)->count();
		$Page = new Page($totals, 12);
		$show = $Page->show();
		$this->assign('page', $show);
		
		$fqList = $fqModel->field(array('id', 'author', 'contents'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();
		
		$this->assign('fqList', $fqList);
		$this->display('ResManager:famousQuotes');
	}
	
	public function delFamousQuotes () {
	    $ids = trim(I('post.nids'));
	
	    if (empty($ids)) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $idsArr = explode(',', trim($ids, ','));
	    if (count($idsArr) <= 0) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $resModel = D('FamousQuotes');
	    $resResult = $resModel->where(array('id'=>array('in', $idsArr)))->delete();
	    if ($resResult !== false) {
	        die(json_encode(array('stat'=>1, '操作成功！')));
	    } else {
	        die(json_encode(array('stat'=>0, '操作失败！[原因]：' . $resModel->getError())));
	    }
	}

	/**
	 * 批量审核 名人名言
	*/
	public function multiCheckFamousQuotes() {
		$artiClassIDs = trim(I('post.aids'), ';');//去掉结尾的;
		$aidsArr = explode(';', $artiClassIDs);//转成数组

		if (empty($artiClassIDs) || empty($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'页面数据错误，请刷新页面重试……！')));
		}
		
		$data = array();
		
		$checkedType = trim(I('post.checkedType'));
		//过滤：已审核/待审核/已驳回
		if (!empty($checkedType)){
			switch ($checkedType){
				case "ys"://已审
					$checkMessageValue = '审核通过';
					$dotype = 1;
					break;
				case "ds"://待审
					$checkMessageValue = '取消审核';
					$dotype = 0;
					break;
				case "bh"://驳回
					$checkMessageValue = substr(trim(I('post.checkMessage')),0,255);//附加消息
					$dotype = -1;
					break;
				default:
					$dotype = 0;//全部
			}
		}else{
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败，请重试……！')));
		}
		
		$data['checked'] = $dotype;
		
		$resModel = D('FamousQuotes');
		$succCounts = $failCounts = 0;
		foreach ($aidsArr as $aid) {
			
			$datas = $resModel->where(array('id'=>$aid))->find();
			if (!$datas) {
				$failCounts++;
				continue;
			}else{
				$rid = $datas['id'];//写日志用
			}
			
			
			//$data = array('checked'=>'1');
			$re = $resModel->where(array('id'=>$aid))->setField($data);;
			if ($re !== false) {
				$succCounts++;
				
				//写审核日志 START
				$checkLogModel = D("CheckLog");
				//$checkMessageValue = "审核通过";
				$checkLogModel->writeNewLog($dotype,$checkMessageValue,"resourceFamousQuotes",$rid,$aid);
				//写审核日志 END
				
			} else {
				$failCounts++;
			}
		}
		
		if ($failCounts == count($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'网络错误导致操作失败，请刷新页面重试……！')));
		} else {
			die(json_encode(array('stat'=>1)));
		}
	}
	
	/**
	 * 百科知识
	 */
	public function baike () {
		$baikeModel = M('ReslibBaike');
		$where = array();
		$title = trim(I('get.title'));
		if (!empty($title)) {
			$where['title'] = array('like', '%' . $title . '%');
		}
		
		// 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $baikeModel->where($where)->count();
		$Page = new Page($totals, 12);
		$show = $Page->show();
		$this->assign('page', $show);
		
		$baikeList = $baikeModel->field(array('id', 'title', 'contents'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();
		
		$this->assign('baikeList', $baikeList);
		$this->display('ResManager:baike');
	}
	
	public function delBaike () {
	    $ids = trim(I('post.nids'));
	
	    if (empty($ids)) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $idsArr = explode(',', trim($ids, ','));
	    if (count($idsArr) <= 0) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $resModel = M('ReslibBaike');
	    $resResult = $resModel->where(array('id'=>array('in', $idsArr)))->delete();
	    if ($resResult !== false) {
	        die(json_encode(array('stat'=>1, '操作成功！')));
	    } else {
	        die(json_encode(array('stat'=>0, '操作失败！[原因]：' . $resModel->getError())));
	    }
	}

	/**
	 * 批量审核 百科
	*/
	public function multiCheckBaike() {
		$artiClassIDs = trim(I('post.aids'), ';');//去掉结尾的;
		$aidsArr = explode(';', $artiClassIDs);//转成数组

		if (empty($artiClassIDs) || empty($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'页面数据错误，请刷新页面重试……！')));
		}
		
		$data = array();
		
		$checkedType = trim(I('post.checkedType'));
		//过滤：已审核/待审核/已驳回
		if (!empty($checkedType)){
			switch ($checkedType){
				case "ys"://已审
					$checkMessageValue = '审核通过';
					$dotype = 1;
					break;
				case "ds"://待审
					$checkMessageValue = '取消审核';
					$dotype = 0;
					break;
				case "bh"://驳回
					$checkMessageValue = substr(trim(I('post.checkMessage')),0,255);//附加消息
					$dotype = -1;
					break;
				default:
					$dotype = 0;//全部
			}
		}else{
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败，请重试……！')));
		}
		
		$data['checked'] = $dotype;
		
		$resModel = M('ReslibBaike');
		$succCounts = $failCounts = 0;
		foreach ($aidsArr as $aid) {
			
			$datas = $resModel->where(array('id'=>$aid))->find();
			if (!$datas) {
				$failCounts++;
				continue;
			}else{
				$rid = $datas['id'];//写日志用
			}
			
			
			//$data = array('checked'=>'1');
			$re = $resModel->where(array('id'=>$aid))->setField($data);;
			if ($re !== false) {
				$succCounts++;
				
				//写审核日志 START
				$checkLogModel = D("CheckLog");
				//$checkMessageValue = "审核通过";
				$checkLogModel->writeNewLog($dotype,$checkMessageValue,"resourceBaike",$rid,$aid);
				//写审核日志 END
				
			} else {
				$failCounts++;
			}
		}
		
		if ($failCounts == count($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'网络错误导致操作失败，请刷新页面重试……！')));
		} else {
			die(json_encode(array('stat'=>1)));
		}
	}
	
	/**
	 * 幽默笑话
	 */
	public function humorJokes () {
		$hjModel = D('HumorJokes');
		$where = array();
		$title = trim(I('get.title'));
		if (!empty($title)) {
			$where['title'] = array('like', '%' . $title . '%');
		}
		
		// 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $hjModel->where($where)->count();
		$Page = new Page($totals, 10);
		$show = $Page->show();
		$this->assign('page', $show);
		
		$hjList = $hjModel->field(array('id', 'title', 'contents'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();
		
		$this->assign('hjList', $hjList);
		$this->display('ResManager:humorJokes');
	}

	public function delHumorJokes () {
	    $ids = trim(I('post.nids'));
	
	    if (empty($ids)) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $idsArr = explode(',', trim($ids, ','));
	    if (count($idsArr) <= 0) {
	        die(json_encode(array('stat'=>0, '请求参数错误！')));
	    }
	
	    $resModel = D('HumorJokes');
	    $resResult = $resModel->where(array('id'=>array('in', $idsArr)))->delete();
	    if ($resResult !== false) {
	        die(json_encode(array('stat'=>1, '操作成功！')));
	    } else {
	        die(json_encode(array('stat'=>0, '操作失败！[原因]：' . $resModel->getError())));
	    }
	}

	/**
	 * 批量审核 幽默笑话
	*/
	public function multiCheckHumorJokes() {
		$artiClassIDs = trim(I('post.aids'), ';');//去掉结尾的;
		$aidsArr = explode(';', $artiClassIDs);//转成数组

		if (empty($artiClassIDs) || empty($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'页面数据错误，请刷新页面重试……！')));
		}
		
		$data = array();
		
		$checkedType = trim(I('post.checkedType'));
		//过滤：已审核/待审核/已驳回
		if (!empty($checkedType)){
			switch ($checkedType){
				case "ys"://已审
					$checkMessageValue = '审核通过';
					$dotype = 1;
					break;
				case "ds"://待审
					$checkMessageValue = '取消审核';
					$dotype = 0;
					break;
				case "bh"://驳回
					$checkMessageValue = substr(trim(I('post.checkMessage')),0,255);//附加消息
					$dotype = -1;
					break;
				default:
					$dotype = 0;//全部
			}
		}else{
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败，请重试……！')));
		}
		
		$data['checked'] = $dotype;
		
		$resModel = D('HumorJokes');
		$succCounts = $failCounts = 0;
		foreach ($aidsArr as $aid) {
			
			$datas = $resModel->where(array('id'=>$aid))->find();
			if (!$datas) {
				$failCounts++;
				continue;
			}else{
				$rid = $datas['id'];//写日志用
			}
			
			
			//$data = array('checked'=>'1');
			$re = $resModel->where(array('id'=>$aid))->setField($data);;
			if ($re !== false) {
				$succCounts++;
				
				//写审核日志 START
				$checkLogModel = D("CheckLog");
				//$checkMessageValue = "审核通过";
				$checkLogModel->writeNewLog($dotype,$checkMessageValue,"resourceHumorJoke",$rid,$aid);
				//写审核日志 END
				
			} else {
				$failCounts++;
			}
		}
		
		if ($failCounts == count($aidsArr)) {
			die(json_encode(array('stat'=>0, 'msg'=>'网络错误导致操作失败，请刷新页面重试……！')));
		} else {
			die(json_encode(array('stat'=>1)));
		}
	}
	
	/**
	 * 英语词条
	 */
	public function englishWords () {
		$this->show('<p style="line-height:472px;text-align:center;font-size:12px;color:#777;">英语词条 … 内容建设中 ……</p>');
	}
	
	/**
	 * 天气预报
	 */
	public function weather () {
		$weatherModel = M('ReslibWeather');
		$dataList = $weatherModel->select();
		if (count($dataList) > 0 && isset($dataList[0])) {
			$this->assign('latestUpDate', trim($dataList[0]['date']));
		}
		$this->assign('dataList', $dataList);
		$this->display('ResManager:weather');
	}
	
	/**
	 * 导入天气数据
	 */
	public function importWeatData() {
		header('Content-Type:text/html;charset=utf-8');
		$importRootDir = trim(C('IMPORT_DATA_PATH'), '/') . '/';
		$importFilepath = $importRootDir . 'weatherEx/data.xls';
	
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			echoMsg('没有可导入数据！请检查数据目录……');
			exit();
		}
	
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$weatData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($weatData);
		if (empty($weatData)) {
			echoMsg('没有可导入数据！请检查数据文件……');
			exit();
		}
		
		$weatInfo = array();
		foreach ($weatData as $ni) {
			$tmp = array();
			$tmp['date'] = trim($ni['B']);
			$tmp['week_day'] = trim($ni['C']);
			$tmp['day_title'] = trim($ni['D']);
			$tmp['weather'] = trim($ni['E']);
			$tmp['wind'] = trim($ni['F']);
			$tmp['temperature'] = trim($ni['G']);
			$tmp['img_1'] = trim($ni['H']);
			$tmp['img_2'] = trim($ni['I']);
			array_push($weatInfo, $tmp);
		}
		//dump($weatInfo);exit();
	
		$weatherModel = M('ReslibWeather');
		$weatherModel->where(1)->delete();
		$item = null;
		foreach ($weatInfo as $item) {
			$nInsRe = $weatherModel->add($item);
		}
		echoMsg('数据导入完成！');
		exit();
	}
	
	/**
	 * 网络获取天气预报信息
	 */
	public function getWeatherData() {
		header('Content-Type:text/html;charset=utf-8');
		$city = I('get.city');
		$url = 'http://api.36wu.com/Weather/GetMoreWeather?district=' . urlencode($city) . '&format=json';
		
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$weatherData = curl_exec($ch);
		curl_close($ch);  
		
		if ($weatherData) {
			$weatherData = json_decode($weatherData, true);
			if ($weatherData['status'] == 200 && $weatherData['message'] == 'OK') {
				$weatherInfo = $weatherData['data'];
				$weatherModel = M('ReslibWeather');
				$weatherModel->where(1)->delete();
				for ($i = 1; $i <= 6; $i++) {
					$data = array();
					$data['date'] = $weatherInfo['date_' . $i];
					$data['week_day'] = getWeekday($data['date']);
					$data['weather'] = $weatherInfo['weather_' . $i];
					$data['wind'] = $weatherInfo['wind_' . $i];
					$data['temperature'] = $weatherInfo['temp_' . $i];
					// $data['fl'] = $weatherInfo['fl_' . $i];
					$data['img_1'] = $weatherInfo['img_' . ($i*2 -1)];
					$data['img_2'] = $weatherInfo['img_' . ($i*2)];
					
					switch ($i) {
						case 1: $data['day_title'] = '今天'; break;
						case 2: $data['day_title'] = '明天'; break;
						case 3: $data['day_title'] = '后天'; break;
						default: $data['day_title'] = '';
					}
					
					$re = $weatherModel->add($data);
					if (!$re) {
						$weatherModel->where(1)->delete();
						$this->error('数据库写入错误，请检查……');
					}
				}
				$this->success('操作成功！');
			} else {
				$this->error('网络请求错误，请刷新页面重试……');
			}
		} else {
			$this->error('网络请求错误，请刷新页面重试……');
		}
		
	}
	
	public function importWeatherData() {
		
		
		// 实例化框架上传类
		import('ORG.Net.UploadFile');
		$uphandle = new UploadFile();
		$uphandle->allowExts = array('xls');
		$uphandle->maxSize = 10485760;
		$uphandle->saveRule = '';
		$savePath = rtrim(C('IMPORT_WEATHER_PATH'), '/') . '/';
		$uphandle->savePath = $savePath;
		$uphandle->uploadReplace = true;
		
		//调用类上传方法
		$upResult = $uphandle->uploadOne($_FILES['myUpfile']);
		
		if(!$upResult) {
			$reInfo = array(
					'stat'	=>	0,
					'msg'	=>	$uphandle->getErrorMsg()
			);
			
			die(json_encode($reInfo));
		} else {
			
			$excelPath = $savePath . utf8ToGbk($upResult[0]['savename']);
			if (is_file($excelPath)) {
				
				// 从excel导入数据
				import('@.ORG.PHPExcel', '', '.php');
				import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
				$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
				$PHPExcel = $PHPExcelReader->load($excelPath);
				$sheetData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
				if (!empty($sheetData)) {
					array_shift($sheetData);
					array_shift($sheetData);
					$weatherModel = M('ReslibWeather');
					$re = $weatherModel->where(1)->delete();
					foreach ($sheetData as $item) {
						$data = array();
						$data = array(
								'date'				=>	trim($item['A']),
								'weather'			=>	trim($item['B']),
								'wind'				=>	trim($item['C']),
								'temperature'		=>	trim($item['D']),
								'dayPictureUrl'		=>	'',
								'nightPictureUrl'	=>	''
						);
						$re = $weatherModel->add($data);
						if (!$re) {
							$re = $weatherModel->where(1)->delete();
							die(json_encode(array('stat'=>0, 'msg'=>'数据导入失败！请重试……')));
						}
					}
					die(json_encode(array('stat'=>1, 'msg'=>'数据导入成功！')));
				} else {
					die(json_encode(array('stat'=>0, 'msg'=>'没有可导入的数据，请检查数据……！')));
				}
			} else {
				die(json_encode(array('stat'=>0, 'msg'=>'文件错误！请重试……')));
			}
		}
		
	}
	
	public function exportHistoricData() {
	    set_time_limit(0);
	
	    // 获取天气数据
	    $resModel = M('ReslibHistoric');
	    $dataList = $resModel->field(array('today_month', 'today_day', 'event_title', 'event_content', 'res_source'))->select();
	    
	    if (!$dataList) {
	        echoMsg('<font color="#800">[失败]</font>没有可导出的数据！');
	        exit();
	    }
	
	    // 导入PHPExcel类库
	    import('@.ORG.PHPExcel', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.Writer.Excel5', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
	
	    $objExcel = new PHPExcel();
	
	    //设置文档属性
	    $objExcel->getProperties()->setCreator("andy");
	    $objExcel->getProperties()->setLastModifiedBy("andy");
	    $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
	    $objExcel->getProperties()->setKeywords("office 2003 openxml php");
	    $objExcel->getProperties()->setCategory("Test result file");
	
	
	    $objExcel->setActiveSheetIndex(0);
	
	    //表头
	    $objExcel->getActiveSheet()->setCellValue('A1', "月份");
	    $objExcel->getActiveSheet()->setCellValue('B1', "日期");
	    $objExcel->getActiveSheet()->setCellValue('C1', "事件标题");
	    $objExcel->getActiveSheet()->setCellValue('D1', "事件内容");
	    $objExcel->getActiveSheet()->setCellValue('E1', "内容来源");
	
	    $i=0;
	    foreach($dataList as $k=>$v) {
	        $u1=$i+2;
	        /*----------写入内容-------------*/
	        $objExcel->getActiveSheet()->setCellValue('A'.$u1, $v["today_month"]);
	        $objExcel->getActiveSheet()->setCellValue('B'.$u1, $v["today_day"]);
	        $objExcel->getActiveSheet()->setCellValue('C'.$u1, $v["event_title"]);
	        $objExcel->getActiveSheet()->setCellValue('D'.$u1, $v["event_content"]);
	        $objExcel->getActiveSheet()->setCellValue('E'.$u1, $v["res_source"]);
	        $i++;
	    }
	
	    // 高置列的宽度
	    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
	    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
	    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(80);
	    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(200);
	    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
	
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
	
	    // 设置页方向和规模
	    $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	    $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	
	
	    $saveDir = trim(C('EXPORT_DATA_PATH'), '/') . '/';
	    if (!is_dir($saveDir)) {
	        @mkdir($saveDir, 0777, true);
	    }
	
	   
	    $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
	    $savePath = $saveDir . '历史上的今天_'. date('YmdHis') .'.xls';
	    if (is_file(utf8ToGbk($savePath))) {
	        @unlink(utf8ToGbk($savePath));
	    }
	
	    try {
	        	
	        $re = $objWriter->save($savePath);
	        echoMsg('<font color="#080">[成功]</font>数据已成功导出到：' . $savePath);
	        exit();
	        	
	    } catch (PHPExcel_Writer_Exception $pwe) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' . $pwe->getMessage());
	        exit();
	    } catch (Exception $e) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' .  $e->getMessage());
	        exit();
	    }
	}
	
	public function exportFamousQuotesData() {
	    set_time_limit(0);
	
	    // 获取天气数据
	    $resModel = D('FamousQuotes');
	    $dataList = $resModel->field(array('author', 'contents'))->select();
	    
	    if (!$dataList) {
	        echoMsg('<font color="#800">[失败]</font>没有可导出的数据！');
	        exit();
	    }
	
	    // 导入PHPExcel类库
	    import('@.ORG.PHPExcel', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.Writer.Excel5', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
	
	    $objExcel = new PHPExcel();
	
	    //设置文档属性
	    $objExcel->getProperties()->setCreator("andy");
	    $objExcel->getProperties()->setLastModifiedBy("andy");
	    $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
	    $objExcel->getProperties()->setKeywords("office 2003 openxml php");
	    $objExcel->getProperties()->setCategory("Test result file");
	
	
	    $objExcel->setActiveSheetIndex(0);
	
	    //表头
	    $objExcel->getActiveSheet()->setCellValue('A1', "名人");
	    $objExcel->getActiveSheet()->setCellValue('B1', "名言");
	
	    $i=0;
	    foreach($dataList as $k=>$v) {
	        $u1=$i+2;
	        /*----------写入内容-------------*/
	        $objExcel->getActiveSheet()->setCellValue('A'.$u1, $v["author"]);
	        $objExcel->getActiveSheet()->setCellValue('B'.$u1, $v["contents"]);
	        $i++;
	    }
	
	    // 高置列的宽度
	    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(80);
	    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(160);
	
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
	
	    // 设置页方向和规模
	    $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	    $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	
	
	    $saveDir = trim(C('EXPORT_DATA_PATH'), '/') . '/';
	    if (!is_dir($saveDir)) {
	        @mkdir($saveDir, 0777, true);
	    }
	
	   
	    $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
	    $savePath = $saveDir . '名人名言_'. date('YmdHis') .'.xls';
	    if (is_file(utf8ToGbk($savePath))) {
	        @unlink(utf8ToGbk($savePath));
	    }
	
	    try {
	        	
	        $re = $objWriter->save($savePath);
	        echoMsg('<font color="#080">[成功]</font>数据已成功导出到：' . $savePath);
	        exit();
	        	
	    } catch (PHPExcel_Writer_Exception $pwe) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' . $pwe->getMessage());
	        exit();
	    } catch (Exception $e) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' .  $e->getMessage());
	        exit();
	    }
	}
	
	public function exportBaikeData() {
	    set_time_limit(0);
	
	    // 获取天气数据
	    $resModel = M('ReslibBaike');
	    $dataList = $resModel->field(array('title', 'contents'))->select();
	    
	    if (!$dataList) {
	        echoMsg('<font color="#800">[失败]</font>没有可导出的数据！');
	        exit();
	    }
	
	    // 导入PHPExcel类库
	    import('@.ORG.PHPExcel', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.Writer.Excel5', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
	
	    $objExcel = new PHPExcel();
	
	    //设置文档属性
	    $objExcel->getProperties()->setCreator("andy");
	    $objExcel->getProperties()->setLastModifiedBy("andy");
	    $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
	    $objExcel->getProperties()->setKeywords("office 2003 openxml php");
	    $objExcel->getProperties()->setCategory("Test result file");
	
	
	    $objExcel->setActiveSheetIndex(0);
	
	    //表头
	    $objExcel->getActiveSheet()->setCellValue('A1', "标题");
	    $objExcel->getActiveSheet()->setCellValue('B1', "内容");
	
	    $i=0;
	    foreach($dataList as $k=>$v) {
	        $u1=$i+2;
	        /*----------写入内容-------------*/
	        $objExcel->getActiveSheet()->setCellValue('A'.$u1, $v["title"]);
	        $objExcel->getActiveSheet()->setCellValue('B'.$u1, $v["contents"]);
	        $i++;
	    }
	
	    // 高置列的宽度
	    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(80);
	    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(160);
	
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
	
	    // 设置页方向和规模
	    $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	    $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	
	
	    $saveDir = trim(C('EXPORT_DATA_PATH'), '/') . '/';
	    if (!is_dir($saveDir)) {
	        @mkdir($saveDir, 0777, true);
	    }
	
	   
	    $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
	    $savePath = $saveDir . '百科知识_'. date('YmdHis') .'.xls';
	    if (is_file(utf8ToGbk($savePath))) {
	        @unlink(utf8ToGbk($savePath));
	    }
	
	    try {
	        	
	        $re = $objWriter->save($savePath);
	        echoMsg('<font color="#080">[成功]</font>数据已成功导出到：' . $savePath);
	        exit();
	        	
	    } catch (PHPExcel_Writer_Exception $pwe) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' . $pwe->getMessage());
	        exit();
	    } catch (Exception $e) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' .  $e->getMessage());
	        exit();
	    }
	}
	
	public function exportHumorJokesData() {
	    set_time_limit(0);
	
	    // 获取天气数据
	    $resModel = D('HumorJokes');
	    $dataList = $resModel->field(array('title', 'contents'))->select();
	    
	    if (!$dataList) {
	        echoMsg('<font color="#800">[失败]</font>没有可导出的数据！');
	        exit();
	    }
	
	    // 导入PHPExcel类库
	    import('@.ORG.PHPExcel', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.Writer.Excel5', '', '.php');
	    import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
	
	    $objExcel = new PHPExcel();
	
	    //设置文档属性
	    $objExcel->getProperties()->setCreator("andy");
	    $objExcel->getProperties()->setLastModifiedBy("andy");
	    $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
	    $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
	    $objExcel->getProperties()->setKeywords("office 2003 openxml php");
	    $objExcel->getProperties()->setCategory("Test result file");
	
	
	    $objExcel->setActiveSheetIndex(0);
	
	    //表头
	    $objExcel->getActiveSheet()->setCellValue('A1', "标题");
	    $objExcel->getActiveSheet()->setCellValue('B1', "内容");
	
	    $i=0;
	    foreach($dataList as $k=>$v) {
	        $u1=$i+2;
	        /*----------写入内容-------------*/
	        $objExcel->getActiveSheet()->setCellValue('A'.$u1, $v["title"]);
	        $objExcel->getActiveSheet()->setCellValue('B'.$u1, $v["contents"]);
	        $i++;
	    }
	
	    // 高置列的宽度
	    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(80);
	    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(160);
	
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
	    $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
	
	    // 设置页方向和规模
	    $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	    $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	
	
	    $saveDir = trim(C('EXPORT_DATA_PATH'), '/') . '/';
	    if (!is_dir($saveDir)) {
	        @mkdir($saveDir, 0777, true);
	    }
	
	   
	    $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
	    $savePath = $saveDir . '幽默笑话_'. date('YmdHis') .'.xls';
	    if (is_file(utf8ToGbk($savePath))) {
	        @unlink(utf8ToGbk($savePath));
	    }
	
	    try {
	        	
	        $re = $objWriter->save($savePath);
	        echoMsg('<font color="#080">[成功]</font>数据已成功导出到：' . $savePath);
	        exit();
	        	
	    } catch (PHPExcel_Writer_Exception $pwe) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' . $pwe->getMessage());
	        exit();
	    } catch (Exception $e) {
	        
	        echoMsg('<font color="#800">[失败]</font>数据导出失败！' .  $e->getMessage());
	        exit();
	    }
	}
	
	
	
    /**
     * 审核节目、资源
     */	
	public function resCheckIndex() {      
		//待审核、已审核、已驳回
		$checked = I("request.checked","","trim");
		if (empty($checked)){
			$checked = "ds";	
		}
		$this->assign("checked",$checked);
		
		$treeid = I("request.treeid","","intval");
		if (!$treeid){
			$treeid = 7;
		}
		$this->assign("treeid",$treeid);
		
		//节目、栏目组、栏目、文章、资源库：世界要闻、历史上的今天、幽默笑话、名人名言
		$type = I("request.type","","trim");
		if (empty($type)){
			$type = "ResLibWorldNews";	
		}
		$this->assign("type",$type);		
	
		//树形菜单
		//$treeData = '[{"id":124,"dir_name":"\u6d4b\u8bd5\u6570\u636e","classid":"f7b4cfdf-c730-dcbf-c3ab-bb591df057bb","parent_classid":"","ROW_NUMBER":"1","name":"\u6d4b\u8bd5\u6570\u636e"},{"id":125,"dir_name":"\u97f3\u9891","classid":"e26494dd-8f2c-b837-03d9-c0a6638b1e81","parent_classid":"","ROW_NUMBER":"2","name":"\u97f3\u9891"},{"id":126,"dir_name":"\u89c6\u9891","classid":"9587ac8c-8e74-77f8-6d26-6865461d503f","parent_classid":"","ROW_NUMBER":"3","name":"\u89c6\u9891"},{"id":127,"dir_name":"\u56fe\u7247","classid":"581d4c8c-e60f-ad03-c5d3-87b039f39722","parent_classid":"","ROW_NUMBER":"4","name":"\u56fe\u7247"}]';

		$programsModel = M('Programs');
		$pdgModel = M('ProgramsDirsGroups');
		$columnModel = D('ProgramsDirs');
		$articleModel = M('ProgramsArticles');
	
		// 加载数据分页类
		import('ORG.Util.Page');
		
		$where = array();
		switch ($checked){
			case "ds":
				$where['_string']  = " checked = 0 or checked is null ";
				break;
			case "ys":
				$where['checked'] = 1;
				break;				
			case "bh":
				$where['checked'] = "-1";
				break;				
				
		}

		switch ($type){
			
			case "programs":
				$where['bevalid'] = 1;

				$count_ds = $programsModel->where("(checked = 0 or checked is null ) and bevalid = 1")->count();
				$count_ys = $programsModel->where("checked = 1 and bevalid = 1")->count();
				$count_bh = $programsModel->where("checked = -1 and bevalid = 1")->count();
				
				$totals = $programsModel->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);

				$list = $programsModel->field(array('program_classid as tid', 'program_name as name', 'program_classid as classid','program_classid','checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
			case "group":
				$count_ds = $pdgModel->where("checked = 0 or checked is null")->count();
				$count_ys = $pdgModel->where("checked = 1")->count();
				$count_bh = $pdgModel->where("checked = -1")->count();
			
				$totals = $pdgModel->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				$list = $pdgModel->field(array('dirgroup_classid as tid','dirgroup_classid', 'dirgroup_name as name', 'program_id as classid','program_id as program_classid', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
			case "column":
				$count_ds = $columnModel->where("checked = 0 or checked is null")->count();
				$count_ys = $columnModel->where("checked = 1")->count();
				$count_bh = $columnModel->where("checked = -1")->count();
			
				$totals = $columnModel->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				
				$list = $columnModel->field(array('classid as tid', 'parent_classid','dir_name as name', 'dirgroup_classid','classid as classid', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
			case "article":
				$count_ds = $articleModel->where("checked = 0 or checked is null")->count();
				$count_ys = $articleModel->where("checked = 1")->count();
				$count_bh = $articleModel->where("checked = -1")->count();
			
				$totals = $articleModel->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);

				$list = $articleModel->field(array('article_classid as tid', 'article_name as name', 'article_classid','program_dir_classid', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				foreach ($list as $k=>$v){
					$column = $columnModel->where("classid = '".$v['program_dir_classid']."'")->field('dirgroup_classid')->find();
					$list[$k]['groupId'] = $column['dirgroup_classid'];
					
					//根据栏目组Id获取节目ID
					if (!empty($column['dirgroup_classid'])){
						$programs = $pdgModel->where("dirgroup_classid='".$column['dirgroup_classid']."'")->field("program_id")->find();
						$list[$k]['program_id'] = $programs['program_id'];
					}
				}
				break;
				
			case "ResLibWorldNews"://资源库：全球要闻
				$model = M('ReslibNews');
				$count_ds = $model->where("checked = 0 or checked is null")->count();
				$count_ys = $model->where("checked = 1")->count();
				$count_bh = $model->where("checked = -1")->count();
			
				$totals = $model->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				$list = $model->field(array('id','id as tid', 'news_title as name', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
	
			case "ResLibHistoric"://资源库：历史上的今天
				$model = M('ReslibHistoric');
				$count_ds = $model->where("checked = 0 or checked is null")->count();
				$count_ys = $model->where("checked = 1")->count();
				$count_bh = $model->where("checked = -1")->count();
			
				$totals = $model->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				$list = $model->field(array('id','id as tid', 'event_title as name','event_content', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
				
			case "ResLibFamousQuotes":
				$model = D('FamousQuotes');
				$count_ds = $model->where("checked = 0 or checked is null")->count();
				$count_ys = $model->where("checked = 1")->count();
				$count_bh = $model->where("checked = -1")->count();
			
				$totals = $model->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				$list = $model->field(array('id','id as tid', 'contents as name','author', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
				
			case "ResLibBaike":
				$model = M('ReslibBaike');
				$count_ds = $model->where("checked = 0 or checked is null")->count();
				$count_ys = $model->where("checked = 1")->count();
				$count_bh = $model->where("checked = -1")->count();
			
				$totals = $model->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				$list = $model->field(array('id','id as tid', 'title as name','contents', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;

			case "ResLibHumorJokes":
				$model = D('HumorJokes');
				$count_ds = $model->where("checked = 0 or checked is null")->count();
				$count_ys = $model->where("checked = 1")->count();
				$count_bh = $model->where("checked = -1")->count();
			
				$totals = $model->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				$list = $model->field(array('id','id as tid', 'title as name','contents', 'checked'))->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();	
				break;
			default:
				;
		}	
	
		$this->assign("count_ds",$count_ds);//待审统计
		$this->assign("count_ys",$count_ys);//已审统计
		$this->assign("count_bh",$count_bh);//已驳回统计
	
		$this->assign("treeData",$treeData);
		$this->assign("list",$list);
		$this->display("ResManager/ResCheckIndex");  
		//$this->display("School/schoolList");
        
    }	
	
	
	
}