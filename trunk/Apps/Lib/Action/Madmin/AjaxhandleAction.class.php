<?php
class AjaxhandleAction extends CommonAction {
	
	public function _initialize() {
		/* if (!$_SESSION[C('USER_AUTH_KEY')]) {
			$this->redirect('Login/index');
		} */
		
		parent::_initialize();
	}
	
	/**
	 * jQuery.form（单张上传）
	 */
	public function uploadImage() {
	
		// 参数过滤
		$savename = I('post.savename', '');
		$savepath = I('post.savepath', '');
		$writeToDB = I('post.isDBWrite', 0, 'int');
		$dataDBModel = I('post.dataDBModel');
		$dataID = I('post.dataID', 0, 'int');
		$isMyUpRoot = I('post.isMyUpRoot', 0, 'int');
	
		// 实例化框架上传类
		import('ORG.Net.UploadFile');
		$uphandle = new UploadFile();
		$uphandle->allowExts = C('UPIMG_ALLOW_TYPES');
		$uphandle->maxSize = C('UPIMAGE_MAX_SIZE');
		$uphandle->savePath = $isMyUpRoot ? C('UPLOAD_COMM_PATH') : C('UPLOAD_ROOT_PATH');
		$uphandle->uploadReplace = true;
	
		// 处理上传文件保存路径
		$savePath = '';
		if ($savepath) {
			$savePath = $savepath . '/';
			$uphandle->savePath .= $savePath;
		}
	
		// 处理文件保存名称
		if (!empty($savename))
			$uphandle->saveRule = $savename;
		else
			$uphandle->saveRule = uniqid() . '_' . time();
	
		//调用类上传方法
		$upResult = $uphandle->uploadOne($_FILES['gcover']);
			
		if(!$upResult) {
			$reInfo = array(
					'stat'	=>	0,
					'msg'	=>	$uphandle->getErrorMsg()
			);
		} else {
			$upResult = $upResult[0];
			$dbSavePath = $savePath . $upResult['savename'];
			$resID = generateUniqueID();
			if ($writeToDB) {
				
				if ($dataDBModel && $dataID) {
					$mediaLibModel = D('MediaLib');
					$data = array('filepath'=>$dbSavePath,'resourceid'=>$resID);
					$mediaLibResult = $mediaLibModel->add($data);
					if ($mediaLibResult !== false) {
						
						$lastID = $mediaLibModel->getLastInsID();
						
						$pluginsDataModel = D($dataDBModel);
						$resourceID = $pluginsDataModel->where(array('id'=>$dataID))->getField('resourceid');
						$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array('resourceid'=>trim($resourceID . ';' . $resID, ';')));
						if ($pluginsDataResult !== false) {
							$reInfo = array(
									'stat'		=> 1,
									'url'		=>	'/' . C('UPLOAD_ROOT_PATH') . $savePath . $upResult['savename'],
									'savePath'	=>	$dbSavePath,
									'pic'		=>	$upResult['savename'],
									'original'	=>	$upResult['name'],
									'size'		=>	$upResult['size'],
									'resourceid'=>	$resID,
									'id'		=>	$lastID
							);
						} else {
							$reInfo = array(
									'stat'	=>	0,
									'msg'	=>	'上传失败！[原因]：数据库写入错误！'
							);
							
							$mediaLibModel->delete($lastID);
							
							// 此处目前仅支持删除本地图片，远程其他待
							$imgPath = C('UPLOAD_ROOT_PATH') . $dbSavePath;
							if (file_exists($imgPath)) {
								@unlink($imgPath);
							}
						}
					} else {
						$reInfo = array(
								'stat'	=>	0,
								'msg'	=>	'上传失败！[原因]：数据库写入错误！'
						);
						
						// 此处目前仅支持删除本地图片，远程其他待
						$imgPath = C('UPLOAD_ROOT_PATH') . $dbSavePath;
						if (file_exists($imgPath)) {
							@unlink($imgPath);
						}
					}
				} else {
					$reInfo = array(
						'stat'	=>	0,
						'msg'	=>	'上传失败！[原因]：参数传递写入错误！'
					);
				}
				
			} else {
				$reInfo = array(
						'stat'		=> 1,
						'url'		=>	'/' . ($isMyUpRoot ? C('UPLOAD_COMM_PATH') : C('UPLOAD_ROOT_PATH')) . $savePath . $upResult['savename'],
						'savePath'	=>	$dbSavePath,
						'pic'		=>	$upResult['savename'],
						'original'	=>	$upResult['name'],
						'size'		=>	$upResult['size']
				);
			}
		}
			
		echo json_encode($reInfo);
	}
	
	public function UEUploadImage() {
		
		$action = I('get.action');
		$artiClassID = I('get.artiClassID');
		$progClassID = I('get.progClassID');
		 
		if ($action == 'config') {
			$config = array(
					'imageActionName'		=>	'uploadimage', /* 执行上传图片的action名称 */
					'imageFieldName'		=>	'artipic', /* 提交的图片表单名称 */
					'imageMaxSize'			=>	2048000, /* 上传大小限制，单位B */
					'imageAllowFiles'		=>	array('.png', '.jpg', '.jpeg', '.bmp'), /* 上传图片格式显示 */
					'imageCompressEnable'	=>	true, /* 是否压缩图片,默认是true */
					'imageCompressBorder'	=>	1600, /* 图片压缩最长边限制 */
					'imageInsertAlign'		=>	'none', /* 插入的图片浮动方式 */
					'imageUrlPrefix'		=>	'' /* 图片访问路径前缀 */
			);
			die(json_encode($config));
		}
	
		// 实例化框架上传类
		import('ORG.Net.UploadFile');
		$uphandle = new UploadFile();
		$uphandle->allowExts = C('UPIMG_ALLOW_TYPES');
		$uphandle->maxSize = C('UPIMAGE_MAX_SIZE');
		$uphandle->savePath = C('UPLOAD_ROOT_PATH');
		$uphandle->uploadReplace = true;
		$uphandle->saveRule = generateUniqueID();
	
		// 处理上传文件保存路径
		$savePath = 'program/';
		$savePath .= empty($progClassID) ? '' : $progClassID . '/';
		$savePath .= 'article/';
		$savePath .= empty($artiClassID) ? '' : $artiClassID . '/';
		$uphandle->savePath .= $savePath;
	
		//调用类上传方法
		$upResult = $uphandle->uploadOne($_FILES['artipic']);
			
		if(!$upResult) {
			$reInfo = array(
					'state'	=>	'FAILED',
					'msg'	=>	$uphandle->getErrorMsg()
			);
		} else {
			$upResult = $upResult[0];
			$dbSavePath = $savePath . $upResult['savename'];
			$resID = generateUniqueID();
			
			// 将本次上传的文件信息写入数据库媒体数据表
			$mediaLibModel = D('MediaLib');
			$data['filepath'] = str_replace('/', '\\', $dbSavePath);
			$data['resourceid'] = $artiClassID;
			//$mediaLibResult = $mediaLibModel->add($data);
			$mediaLibResult = $mediaLibModel->execute("insert into TB_MediaLib (filepath, resourceid) values ('" . $data['filepath'] . "', '" . $data['resourceid'] . "')");
				
			if ($mediaLibResult !== false) {
				
				$lastestID = $mediaLibModel->getLastInsID();
					
				$artiModel = M('ProgramsArticlesTemp');
				$data['tmp_article_classid'] = $artiClassID;
				$data['res_id'] = $lastestID;
				$data['filename'] = utf82gbk($upResult['savename']);
				$data['filetype'] = 'image';
				$data['filepath'] = utf82gbk($dbSavePath);
				$data['act'] = 'add';
				$data['ctime'] = time();
				$artiModel->add($data);
				
				$reInfo = array(
						'state'		=> 'SUCCESS',
						'url'		=>	'/' . $uphandle->savePath . $upResult['savename'] . '?' . $lastestID,
						'savePath'	=>	$dbSavePath,
						'title'		=>	$upResult['savename'],
						'original'	=>	$upResult['name'],
						'size'		=>	$upResult['size'],
						'type'		=>	substr($upResult['savename'], strrpos($upResult['savename'], '.')),
						'resourceid'=>	$resID
				);
			} else {
				$reInfo = array(
						'state'	=>	'FAILED',
						'msg'	=>	'上传失败！[原因]：数据库写入错误！'
				);
					
				// 此处目前仅支持删除本地图片，远程其他待
				$imgPath = $uphandle->savePath . $upResult['savename'];
				if (file_exists($imgPath)) {
					@unlink($imgPath);
				}
			}
		}
			
		echo json_encode($reInfo);
	}
	
	/**
	 * Uploadify（文件上传处理）
	 */
	public function uploadify() {
	
		// 参数过滤
		$savename = trim(I('post.savename'));
		$dataType = trim(I('post.dataType'));
		$appModel = trim(I('post.appModel'));
		$progClassID = trim(I('post.progClassID'));
		$coluClassID = trim(I('post.coluClassID'));
		$artiClassID = trim(I('post.artiClassID'));
		$tmpClassID = trim(I('post.tmpClassID'));
		$type = trim(I('post.type'));
		$folderName = trim(I('post.folderName'));
		$writeToDB = I('post.isDBWrite', 0, 'int');
		$dataDBModel = trim(I('post.dataDBModel'));
		$dataID = I('post.dataID', 0, 'int');
		$pid = I('post.pid');
		$isMyUpRoot = I('post.isMyUpRoot', 0, 'int');

		// 实例化框架上传类
		import('ORG.Net.UploadFile');
		$uphandle = new UploadFile();
		
		// 设置上传文件允许的格式，以及文件大小
		$allowExts = array();
		$maxSize = 0;
		switch ($dataType) {
			case 'image' :
				$allowExts = C('UPIMG_ALLOW_TYPES');
				break;
			case 'video' :
				$allowExts = C('UPVIDEO_ALLOW_TYPES');
				break;
			case 'music' :
				$allowExts = C('UPMUSIC_ALLOW_TYPES');
				break;
			case 'soft' :
				$allowExts = C('UPSOFT_ALLOW_TYPES');
				break;
			case 'text' :
				$allowExts = C('UPTEXT_ALLOW_TYPES');
				break;
			case 'imagevideo' :
				$allowExts = array_merge(C('UPIMG_ALLOW_TYPES'), C('UPVIDEO_ALLOW_TYPES'));
				break;
			default :
				$allowExts = array();
		}
		$uphandle->allowExts = $allowExts;
		$uphandle->maxSize = C('UP'.strtoupper(in_array($dataType, array('all', 'imagevideo')) ? 'video' : $dataType).'_MAX_SIZE');
		
		
		// 处理上传文件保存路径  && 处理文件保存名称
		$uploadRoot = '';
		if ($isMyUpRoot) {
			$uploadRoot = C('UPLOAD_COMM_PATH');
		} elseif ($dataType == 'soft') {
			$uploadRoot = C('a_main_upfile');
		} else {
			$uploadRoot = C('UPLOAD_ROOT_PATH');
		}
		
		$uphandle->savePath = rtrim($uploadRoot, '/') . '/';
		$savePath = '';
		if ($folderName != '') {
			$savePath = trim($folderName, '/') . '/';
			$uphandle->savePath .= $savePath;
			$uphandle->saveRule = '';
			$uphandle->uploadReplace = false;
		} elseif (in_array($appModel, array('column', 'article'))) {
			if (!empty($progClassID)) {
				$savePath = 'program/' . $progClassID . '/';
			}
			$savePath .= $appModel . '/';
			if ($appModel == 'article' && !empty($artiClassID)) {
				$savePath .= $artiClassID . '/';
			}
			$uphandle->savePath .= $savePath;
			$uphandle->saveRule = generateUniqueID();
			$uphandle->uploadReplace = true;
		} elseif ($appModel == 'mall') {
		    $savePath .= 'mall/';
			$uphandle->savePath .= $savePath;
			$uphandle->saveRule = generateUniqueID();
			$uphandle->uploadReplace = true;
		} elseif ($appModel == 'store') {
		    $savePath .= 'mall/' . $tmpClassID . '/';
			$uphandle->savePath .= $savePath;
			$uphandle->saveRule = generateUniqueID();
			$uphandle->uploadReplace = true;
		} elseif ($appModel == 'ads') {
		    $savePath .= 'ads/' . $tmpClassID . '/';
			$uphandle->savePath .= $savePath;
			$uphandle->saveRule = generateUniqueID();
			$uphandle->uploadReplace = true;
		} else {
			if (!empty($savename))
				$uphandle->saveRule = ($savename == 'logo') ? $savename . '_' . uniqid() : $savename;
			elseif ($dataType == 'soft')
				$uphandle->saveRule = '';
			else
				$uphandle->saveRule = generateUniqueID();
			$uphandle->uploadReplace = true;
		}
		
		$newDataID = '';
		if ($pid) {
			$uphandle->uploadReplace = false;
			
			if ($dataDBModel == 'SlideIV') {
				$newDataID = generateUniqueID();
				$savePath .= $newDataID . '/';
				$uphandle->savePath .= $newDataID . '/';
			}
		}
		
		//调用类上传方法
		$upResult = $uphandle->uploadOne($_FILES['myUpfile']);
		
		if(!$upResult) {
			$reInfo = array(
					'stat'	=>	0,
					'msg'	=>	$uphandle->getErrorMsg()
			);
		} else {
			$upResult = $upResult[0];
			$dbSavePath = utf82gbk($savePath . $upResult['savename']);
			$resID = generateUniqueID();
			if ($writeToDB) {
			
				if ($dataDBModel && ($dataID || $pid)) {
					
					$pluginsDataModel = D($dataDBModel);
					if ($dataDBModel == 'SlideIV' && $dataID) {
						$resourceID = trim($pluginsDataModel->where(array('id'=>$dataID))->getField('resourceid'));
					}
					
					// 将本次上传的文件信息写入数据库媒体数据表
					$mediaLibModel = D('MediaLib');
					$data['filepath'] = str_replace('/', '\\', $dbSavePath);
					$data['resourceid'] = empty($resourceID) ? $resID : $resourceID;
					$ext = substr($dbSavePath, strrpos($dbSavePath, '.') + 1);
					//$mediaLibResult = $mediaLibModel->add($data);
					$mediaLibResult = $mediaLibModel->execute("insert into TB_MediaLib (filepath, type, resourceid) values ('" . $data['filepath'] . "', '" . $ext . "' , '" . $data['resourceid'] . "')");
					
					if ($mediaLibResult !== false) {
			
						$lastID = $mediaLibModel->getLastInsID();
			
						$resFieldName = in_array($dataType, array('all', 'music', 'text')) ? 'fileid' : 'resourceid';
						
						if ($dataID) {

							if ($dataDBModel == 'ButtonsBox') {
								$dataIdStr = $pluginsDataModel->where(array('id'=>$dataID))->getField('dataid');
								$bbData = array(
										'buttonitem_imgid'		=>	$resID,
										'belong_dataid'			=>	$dataIdStr,
										'buttonitem_classid'	=>	generateUniqueID(),
										'buttonitem_name'		=>	utf82gbk('新按钮' . date('Y/n/j H:i:s'))
									);
								$pluginsDataResult = D('ButtonsBoxItem')->add($bbData);
							} elseif ($dataDBModel == 'SlideIV') {
								if (empty($resourceID)) {
									$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array('resourceid'=>$resID));
								}
							} else {
								$resourceID = $pluginsDataModel->where(array('id'=>$dataID))->getField($resFieldName);
								if (in_array($dataType, array('all', 'text'))) { // 单文件数据处理
									$txtFilepath = $mediaLibModel->where(array('resourceid'=>trim($resourceID, ';')))->getField('filepath');
									if (trim($txtFilepath) != '' && file_exists($uploadRoot . $savePath . $txtFilepath)) {
										@unlink($uploadRoot . $savePath . $txtFilepath);
									}
									$mediaLibModel->where(array('resourceid'=>trim($resourceID, ';')))->delete();
									$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>$resID));		
								} else {
									$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>trim(trim($resourceID) . ';' . $resID, ';')));
								}
							}
						} else {
							$dataname = utf82gbk(mb_substr($upResult['name'], 0, strrpos($upResult['name'], '.')));
							if ($dataType == 'all') {
								$pdata = array(
									'dataid'=>generateUniqueID(),
									'dataname'=>$dataname,
									$resFieldName=>$resID,
									'itemclassid'=>$pid,
									'isdefault'=>'False',
									'isroate'=>'False',
									'itembgid'=>'',
									'winsizerate'=>1,
									'animationtype'=>'Fade',
									'tendpoint_groupid'=>'0000-0000'
								);
							} elseif ($dataType == 'imagevideo') {
								$pdata = array(
									'dataid'=>$newDataID,
									'dataname'=>$dataname,
									$resFieldName=>$resID,
									'itemclassid'=>$pid,
									'isdefault'=>'False',
									'isfolder'=>'False',
									'tendpoint_groupid'=>'0000-0000'
								);
							} else {
								$pdata = array(
									'dataid'=>generateUniqueID(),
									'dataname'=>$dataname,
									$resFieldName=>$resID,
									'itemclassid'=>$pid,
									'isdefault'=>'False',
									'isfolder'=>'False',
									'tendpoint_groupid'=>'0000-0000'
								);
							}
							$pluginsDataResult = $pluginsDataModel->add($pdata);
						}

						if ($pluginsDataResult !== false) {
							$reInfo = array(
									'stat'		=> 1,
									'url'		=>	'/' . $uploadRoot . $savePath . $upResult['savename'],
									'savePath'	=>	$dbSavePath,
									'pic'		=>	$upResult['savename'],
									'original'	=>	$upResult['name'],
									'size'		=>	$upResult['size'],
									'resourceid'=>	$resID,
									'id'		=>	$lastID
							);
						} else {
							$reInfo = array(
									'stat'	=>	0,
									'msg'	=>	'上传失败！[原因]：数据库写入错误！'
							);
								
							$mediaLibModel->delete($lastID);
								
							// 此处目前仅支持删除本地图片，远程其他待
							$imgPath = $uploadRoot . $dbSavePath;
							if (file_exists($imgPath)) {
								@unlink($imgPath);
							}
						}
					} else {
						$reInfo = array(
								'stat'	=>	0,
								'msg'	=>	'上传失败！[原因]：数据库写入错误！'
						);
			
						// 此处目前仅支持删除本地图片，远程其他待
						$imgPath = $uploadRoot . $dbSavePath;
						if (file_exists($imgPath)) {
							@unlink($imgPath);
						}
					}
				} elseif (in_array($appModel, array('column', 'article', 'store', 'mall', 'ads'))) {  // 处理节目图片
					
					// 将本次上传的文件信息写入数据库媒体数据表
					$mediaLibModel = D('MediaLib');
					$data['filepath'] = str_replace('/', '\\', $dbSavePath);
					$data['resourceid'] = $appModel != 'ads' ? $resID : $tmpClassID;
					$ext = substr($dbSavePath, strrpos($dbSavePath, '.') + 1);
					//$mediaLibResult = $mediaLibModel->add($data);
					$mediaLibResult = $mediaLibModel->execute("insert into TB_MediaLib (filepath, type, resourceid) values ('" . $data['filepath'] . "', '" . $ext . "' , '" . $data['resourceid'] . "')");
					
					if ($mediaLibResult !== false) {
						
					    if ($appModel != 'mall' && $appModel != 'ads') {
    					    if ($appModel == 'store' && $type == 'gallery') {
    					        $storeModel = D('SCStore');
    					        $galleryVal = $storeModel->where(array('classid'=>$tmpClassID))->getField('figurechart');
    					        $storeModel->where(array('classid'=>$tmpClassID))->save(array('figurechart'=>!empty($galleryVal) ? trim($galleryVal, ',') . ',' . $resID . ',' : $resID . ','));
    					    } else {
        						$artiModel = M('ProgramsArticlesTemp');
        						$data['tmp_article_classid'] = $appModel == 'article' ? $artiClassID : ($appModel == 'store' ? $tmpClassID : $coluClassID);
        						$data['res_id'] = $mediaLibModel->getLastInsID();
        						$data['filename'] = utf82gbk($upResult['savename']);
        						$data['filetype'] = 'image';
        						$data['filepath'] = utf82gbk($dbSavePath);
        						$data['act'] = $type;
        						$data['ctime'] = time();
        						$artiModel->add($data);
    					    }
						}
						
						$reInfo = array(
								'stat'		=> 1,
								'url'		=>	'/' . $uploadRoot . $savePath . $upResult['savename'],
								'savePath'	=>	$dbSavePath,
								'pic'		=>	$upResult['savename'],
								'original'	=>	$upResult['name'],
								'size'		=>	$upResult['size'],
								'resourceid'=>	$resID,
						        'resid'     =>  $mediaLibModel->getLastInsID()
						);
					} else {
						$reInfo = array(
								'stat'	=>	0,
								'msg'	=>	'上传失败！[原因]：数据库写入错误！'
						);
					
						// 此处目前仅支持删除本地图片，远程其他待
						$imgPath = $uploadRoot . $dbSavePath;
						if (file_exists($imgPath)) {
							@unlink($imgPath);
						}
					}
					
				} else {
					$reInfo = array(
							'stat'	=>	0,
							'msg'	=>	'上传失败！[原因]：参数传递写入错误！'
					);
				}
			
				// 数据库写入结束....
			} else {


				$reInfo = array(
						'stat'		=> 1,
						'url'		=>	'/' . $uploadRoot . $savePath . $upResult['savename'],
						'savePath'	=>	$dbSavePath,
						'pic'		=>	$upResult['savename'],
						'original'	=>	$upResult['name'],
						'size'		=>	$upResult['size']
				);
				
				if ($dataType == 'soft') {
					$sysModel = M('Syscfg');
					$result = $sysModel->where(array('id'=>1))->save(array($_POST['endType'] . '_latest_upfile'=>utf82gbk($upResult['savename'])));

					if ($result === false) {
						$filePath = $uploadRoot . $dbSavePath;
						if (file_exists($filePath)) {
							@unlink($filePath);
						}

						$reInfo = array(
								'stat'	=>	0,
								'msg'	=>	'上传失败！[原因]：数据库写入错误！'
						);
					} else {
						C($_POST['endType'] . '_latest_upfile', $upResult['savename']);
					}
				}
			}
		}
		
		// 生成缩略图
		if ($reInfo['stat'] == 1 && $dataType == 'image') {
		    if ($appModel == 'ads') {
		        $reInfo['thumbURL'] = '/' . $this->createThumbImg($uploadRoot . $dbSavePath, $savePath);
		    }
		}
			
		echo json_encode($reInfo);
	}
	
	public function commonUploadify() {
	
		// 参数过滤
		$catID = I('post.catID', 0, 'int');
	
		// 实例化框架上传类
		import('ORG.Net.UploadFile');
		$uphandle = new UploadFile();
		$uphandle->maxSize = -1;
		$uphandle->saveRule = '';
		$savePath = '';
		$rmdModel = D('ResmanagerDirs');
		if ($catID) {
			$savePath .= trim($rmdModel->genFileSavePath($catID), '/') . '/';
		}
		$uphandle->savePath = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/' . $savePath;
	
		//调用类上传方法
		$upResult = $uphandle->uploadOne($_FILES['myUpfile']);
			
		if(!$upResult) {
			$reInfo = array(
					'stat'	=>	0,
					'msg'	=>	$uphandle->getErrorMsg()
			);
		} else {
			$upResult = $upResult[0];
			$dbSavePath = utf82gbk($savePath . $upResult['savename']);
			$resID = generateUniqueID();
			$now = time();

			$rmfModel = M('ResmanagerFiles');
			$data['classid'] = $resID;
			$data['filename'] = utf82gbk($upResult['savename']);
			$data['filepath'] = $dbSavePath;
			$data['filetype'] = getResType($upResult['savename']);
			if ($data['filetype'] == 'video' || $data['filetype'] == 'music') {
				$videoInfo = video_info(iconv('UTF-8', 'GBK', $uphandle->savePath . $upResult['savename']), rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . C('MY_PUBLIC_PATH') . '/tools/' . C('SYSTEM_BIT_TYPE') . '/ffmpeg.exe');
				if ($videoInfo) {
					$data['file_size'] = round($videoInfo['size']/1024/1024, 1);
					$data['play_time'] = $videoInfo['seconds'];
				} else {
					$data['file_size'] = '';
					$data['play_time'] = '';
				}
			} else {
				$data['file_size'] = '';
				$data['play_time'] = '';
			}
			$data['create_time'] = date('Y-m-d H:i:s', $now);
			$data['ctime_stamps'] = $now;
			$data['status'] = 1;
			$data['belong_dirclassid'] = $catID ? $rmdModel->where(array('id'=>$catID))->getField('classid') : '';
			
			$addRe = $rmfModel->add($data);
			if ($addRe !== false) {
				$lastID = $rmfModel->getLastInsID();

				// 生成缩略图
				if ($data['filetype'] == 'image') {
				    $thumbPath = $this->createThumbImg($uphandle->savePath . $upResult['savename'], $savePath);
				}
				
				$reInfo = array(
						'stat'		=> 1,
						'url'		=>	$data['filetype'] == 'image' ? '/' . $thumbPath : '/' . $uphandle->savePath . $upResult['savename'],
						'savePath'	=>	$dbSavePath,
						'pic'		=>	$upResult['savename'],
						'original'	=>	$upResult['name'],
						'size'		=>	$upResult['size'],
						'resourceid'=>	$resID,
						'type'		=>	getResType($upResult['savename']),
						'id'		=>	$lastID
				);
				
			} else {
				
				$reInfo = array(
					'stat'	=>	0,
					'msg'	=>	'上传失败！[原因]：数据库写入错误！'
				);
	
				$rmfModel->delete($lastID);
	
				// 此处目前仅支持删除本地文件，远程其他待
				$imgPath = $uphandle->savePath . utf82gbk($upResult['savename']);
				if (file_exists($imgPath)) {
					@unlink($imgPath);
				}
			}
						
		}
			
		echo json_encode($reInfo);
	}

	public function plupload() {
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// 5 minutes execution time
		@set_time_limit(5 * 60);
		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// 参数过滤
		$savename = trim(I('post.savename'));
		$dataType = trim(I('post.dataType'));
		$folderName = trim(I('post.folderName'));
		$writeToDB = I('post.isDBWrite', 0, 'int');
		$dataDBModel = trim(I('post.dataDBModel'));
		$dataID = I('post.dataID', 0, 'int');
		$pid = I('post.pid');
		$isMyUpRoot = I('post.isMyUpRoot', 0, 'int');
		
		// 设置上传文件允许的格式，以及文件大小
		$allowExts = array();
		$maxSize = 0;
		switch ($dataType) {
			case 'image' :
				$allowExts = C('UPIMG_ALLOW_TYPES');
				break;
			case 'video' :
				$allowExts = C('UPVIDEO_ALLOW_TYPES');
				break;
			case 'music' :
				$allowExts = C('UPMUSIC_ALLOW_TYPES');
				break;
			case 'soft' :
				$allowExts = C('UPSOFT_ALLOW_TYPES');
				break;
			case 'imagevideo' :
				$allowExts = array_merge(C('UPIMG_ALLOW_TYPES'), C('UPVIDEO_ALLOW_TYPES'));
				break;
			default :
				$allowExts = array();
		}
		$allowMaxSize = C('UP'.strtoupper(in_array($dataType, array('all', 'imagevideo')) ? 'video' : $dataType).'_MAX_SIZE');
		
		
		// 处理上传文件保存路径  && 处理文件保存名称
		$targetDir = '';
		if ($isMyUpRoot) {
			$targetDir = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/';
		} elseif ($dataType == 'soft') {
			$targetDir = rtrim(C('a_main_upfile'), '/') . '/';
		} else {
			$targetDir = rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/';
		}

		$isReplace = false;
		$fileName = $fileExt = '';
		if (isset($_REQUEST["name"])) {
			$fileName = utf82gbk($_REQUEST["name"]);
		} elseif (!empty($_FILES)) {
			$fileName = utf82gbk($_FILES["file"]["name"]);
		} else {
			$fileName = uniqid() . '_' . time();
		}

		$fileExt = substr($fileName, strrpos($fileName, '.'));
		
		$savePath = '';
		if ($folderName != '') {
			$savePath = trim($folderName, '/') . '/';
		}
		
		if ($savename != '') {
			$fileName = $savename . $fileExt;
		}
		
		// Create target dir
		if (!file_exists($targetDir . $savePath)) {
			@mkdir($targetDir . $savePath, 0777, true);
		}

		// Get a file name
		$filePath = $targetDir . $savePath . $fileName;

		if (file_exists($filePath)) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 776, "message": "已存在同名文件！"}, "id" : "id"}');
		}

		// Chunking might be enabled
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

		// Remove old temp files	
		if ($cleanupTargetDir) {
			if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "打开目录失败！"}, "id" : "id"}');
			}

			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				/* if (substr($file, -5) == '.part') {
					@unlink($tmpfilePath);
				} */
				
				// If temp file is current file proceed to the next
				if ($tmpfilePath == "{$filePath}.part") {
					continue;
				}

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
					@unlink($tmpfilePath);
				}
			}
			closedir($dir);
		}	


		// Open temp file
		if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "打开文件失败！"}, "id" : "id"}');
		}

		if (!empty($_FILES)) {
			if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "上传失败！"}, "id" : "id"}');
			}

			// Read binary input stream and append it to temp file
			if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "打开文件失败！"}, "id" : "id"}');
			}
		} else {	
			if (!$in = @fopen("php://input", "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "打开文件失败！"}, "id" : "id"}');
			}
		}

		$isWrote = 1;
		while ($buff = fread($in, 4096)) {
			if (fwrite($out, $buff) === false) {
				$isWrote = 0;
				break;
			}
		}
		
		if ($isWrote) {
			$tmpMLModel = M('MedialibTemp');
			$fileInDB = $tmpMLModel->where(array('filename'=>$fileName))->find();
			
			if ($fileInDB) {
				$tmpMLModel->where(array('id'=>$fileInDB['id']))->save(array('chunk'=>$chunk + 1, 'create_time'=>date('Y-m-d H:i:s')));
			} else {
				$tmpMLModel->add(array('filename'=>$fileName, 'chunk'=>$chunk + 1, 'create_time'=>date('Y-m-d H:i:s')));
			}
		}

		@fclose($out);
		@fclose($in);

		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			// Strip the temp .part suffix off
			$newDataID = '';
			if ($folderName != '') {
				if ($pid) {
					$newDataID = generateUniqueID();
					$savePath .= $newDataID . '/';
					if (!file_exists($targetDir . $savePath)) {
						@mkdir($targetDir . $savePath, 0777, true);
					}
					rename("{$filePath}.part", $targetDir . $savePath . $fileName);
				} else {
					rename("{$filePath}.part", $filePath);
				}
				$dbSavePath = $savePath . $fileName;
			} else {
				$newFileName = generateUniqueID() . $fileExt;
				rename("{$filePath}.part", $targetDir . $newFileName);
				$dbSavePath = $newFileName;
			}
			
			if ($chunks) {
				$tmpMLModel->where(array('filename'=>$fileName))->delete();
			}

			$resID = generateUniqueID();
			if ($writeToDB) {
			
				if ($dataDBModel && ($dataID || $pid)) {
					
					$pluginsDataModel = D($dataDBModel);
					if ($dataDBModel == 'SlideIV' && $dataID) {
						$resourceID = trim($pluginsDataModel->where(array('id'=>$dataID))->getField('resourceid'));
					}
					
					// 将本次上传的文件信息写入数据库媒体数据表
					$mediaLibModel = D('MediaLib');
					$data['filepath'] = str_replace('/', '\\', $dbSavePath);
					$data['resourceid'] = empty($resourceID) ? $resID : $resourceID;
					//$mediaLibResult = $mediaLibModel->add($data);
					$mediaLibResult = $mediaLibModel->execute("insert into TB_MediaLib (filepath, resourceid) values ('" . $data['filepath'] . "', '" . $data['resourceid'] . "')");
					
					if ($mediaLibResult !== false) {
			
						$lastID = $mediaLibModel->getLastInsID();
			
						$resFieldName = in_array($dataType, array('all', 'music', 'text')) ? 'fileid' : 'resourceid';
						
						if ($dataID) {

							if ($dataDBModel == 'ButtonsBox') {
								$dataIdStr = $pluginsDataModel->where(array('id'=>$dataID))->getField('dataid');
								$bbData = array(
										'buttonitem_imgid'		=>	$resID,
										'belong_dataid'			=>	$dataIdStr,
										'buttonitem_classid'	=>	generateUniqueID(),
										'buttonitem_name'		=>	utf82gbk('新按钮' . date('Y/n/j H:i:s'))
									);
								$pluginsDataResult = D('ButtonsBoxItem')->add($bbData);
							} elseif ($dataDBModel == 'SlideIV') {
								if (empty($resourceID)) {
									$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array('resourceid'=>$resID));
								}
							} else {
								$resourceID = $pluginsDataModel->where(array('id'=>$dataID))->getField($resFieldName);
								if (in_array($dataType, array('all', 'text'))) {
									$txtFilepath = $mediaLibModel->where(array('resourceid'=>trim($resourceID, ';')))->getField('filepath');
									if (trim($txtFilepath) != '' && file_exists($targetDir . $txtFilepath)) {
										@unlink($targetDir . $txtFilepath);
									}
									$mediaLibModel->where(array('resourceid'=>trim($resourceID, ';')))->delete();
									$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>$resID));		
								} else {
									$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>trim(trim($resourceID) . ';' . $resID, ';')));
								}
							}
						} else {
							$dataname = mb_substr($fileName, 0, strrpos($fileName, '.'));
							if ($dataType == 'all') {
								$pdata = array(
									'dataid'=>generateUniqueID(),
									'dataname'=>$dataname,
									$resFieldName=>$resID,
									'itemclassid'=>$pid,
									'isdefault'=>'False',
									'isroate'=>'False',
									'itembgid'=>'',
									'winsizerate'=>1,
									'animationtype'=>'Fade',
									'tendpoint_groupid'=>'0000-0000'
								);
							} elseif ($dataType == 'imagevideo') {
								$pdata = array(
										'dataid'=>$newDataID,
										'dataname'=>$dataname,
										$resFieldName=>$resID,
										'itemclassid'=>$pid,
										'isdefault'=>'False',
										'isfolder'=>'False',
										'tendpoint_groupid'=>'0000-0000'
								);
							} else {
								$pdata = array(
									'dataid'=>generateUniqueID(),
									'dataname'=>$dataname,
									$resFieldName=>$resID,
									'itemclassid'=>$pid,
									'isdefault'=>'False',
									'isfolder'=>'False',
									'tendpoint_groupid'=>'0000-0000'
								);
							}
							$pluginsDataResult = $pluginsDataModel->add($pdata);
						}

						if ($pluginsDataResult === false) {

							$mediaLibModel->delete($lastID);
								
							// 此处目前仅支持删除本地文件，远程其他待
							$fpath = $targetDir . $dbSavePath;
							if (file_exists($fpath)) {
								@unlink($fpath);
							}

							die('{"jsonrpc" : "2.0", "error" : {"code": 777, "message": "数据库写入错误！"}, "id" : "id"}');
						}
					} else {

						// 此处目前仅支持删除本地文件，远程其他待
						$fpath = $targetDir . $dbSavePath;
						if (file_exists($fpath)) {
							@unlink($fpath);
						}
						die('{"jsonrpc" : "2.0", "error" : {"code": 777, "message": "数据库写入错误！"}, "id" : "id"}');
					}
				} else {

					die('{"jsonrpc" : "2.0", "error" : {"code": 777, "message": "参数传递写入错误！"}, "id" : "id"}');
				}
			
			} // WriteToDB End ....
		}

		// Return Success JSON-RPC response
		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
	}
	
	public function commonPlupload() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// 5 minutes execution time
		@set_time_limit(5 * 60);
		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// 参数过滤
		$catID = I('post.catID', 0, 'int');
		
		// 文件上传参数设置
		$allowMaxSize =-1;
		
		
		// 处理上传文件保存路径  && 处理文件保存名称
		$targetDir = $savePath = '';
		$rmdModel = D('ResmanagerDirs');
		if ($catID) {
			$savePath .= trim($rmdModel->genFileSavePath($catID), '/') . '/';
		}
		
		$targetDir = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/' . $savePath;
		
		$fileName = $fileExt = '';
		if (isset($_REQUEST["name"])) {
			$fileName = utf8ToGbk($_REQUEST["name"]);
		} elseif (!empty($_FILES)) {
			$fileName = utf8ToGbk($_FILES["file"]["name"]);
		} else {
			$fileName = uniqid() . '_' . time();
		}

		$fileExt = substr($fileName, strrpos($fileName, '.'));
		
		// Create target dir
		if (!file_exists($targetDir)) {
			@mkdir($targetDir, 0777, true);
		}

		// Get a file name
		$filePath = $targetDir . $fileName;
		if (file_exists($filePath)) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 776, "message": "已存在同名文件！"}, "id" : "id"}');
		}

		// Chunking might be enabled
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

		// Remove old temp files	
		if ($cleanupTargetDir) {
			if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "打开目录失败！"}, "id" : "id"}');
			}

			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				/* if (substr($file, -5) == '.part') {
					@unlink($tmpfilePath);
				} */
				
				// If temp file is current file proceed to the next
				if ($tmpfilePath == "{$filePath}.part") {
					continue;
				}

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
					@unlink($tmpfilePath);
				}
			}
			closedir($dir);
		}	


		// Open temp file
		if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "打开文件失败！"}, "id" : "id"}');
		}

		if (!empty($_FILES)) {
			if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "上传失败！"}, "id" : "id"}');
			}

			// Read binary input stream and append it to temp file
			if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "打开文件失败！"}, "id" : "id"}');
			}
		} else {	
			if (!$in = @fopen("php://input", "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "打开文件失败！"}, "id" : "id"}');
			}
		}

		$isWrote = 1;
		while ($buff = fread($in, 4096)) {
			if (fwrite($out, $buff) === false) {
				$isWrote = 0;
				break;
			}
		}
		
		$tmpMLModel = M('ResmanagerFilesTemp');
		$dbFileName = gbkToUtf8($fileName);
		$fileInDB = $tmpMLModel->where(array('filename'=>$dbFileName))->find();
		
		if ($fileInDB) {
			$tmpMLModel->where(array('id'=>$fileInDB['id']))->save(array('chunk'=>$chunk + 1, 'create_time'=>date('Y-m-d H:i:s')));
		} else {
			$tmpMLModel->add(array('filename'=>$dbFileName, 'chunk'=>$chunk + 1, 'create_time'=>date('Y-m-d H:i:s')));
		}

		@fclose($out);
		@fclose($in);

		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			
			// Strip the temp .part suffix off
			rename("{$filePath}.part", $filePath);
			
			if ($chunks) {
				$tmpMLModel->where(array('filename'=>$dbFileName))->delete();
			}
			
			$resID = generateUniqueID();
			$now = time();
			$rmfModel = M('ResmanagerFiles');
			$data['classid'] = $resID;
			$data['filename'] = $dbFileName;
			$data['filepath'] = $savePath . $dbFileName;
			$data['filetype'] = getResType($dbFileName);
			if ($data['filetype'] == 'video' || $data['filetype'] == 'music') {
				$videoInfo = video_info($filePath, rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . C('MY_PUBLIC_PATH') . '/tools/' . C('SYSTEM_BIT_TYPE') . '/ffmpeg.exe');
				if ($videoInfo) {
					$data['file_size'] = round($videoInfo['size']/1024/1024, 1);
					$data['play_time'] = $videoInfo['seconds'];
				} else {
					$data['file_size'] = '';
					$data['play_time'] = '';
				}
			} else {
				$data['file_size'] = '';
				$data['play_time'] = '';
			}
			$data['create_time'] = date('Y-m-d H:i:s', $now);
			$data['ctime_stamps'] = $now;
			$data['status'] = 1;
			$data['belong_dirclassid'] = $catID ? $rmdModel->where(array('id'=>$catID))->getField('classid') : '';
			
			$addRe = $rmfModel->add($data);
			if ($addRe !== false) {
				$lastID = $rmfModel->getLastInsID();
				
				// 生成缩略图
				if ($data['filetype'] == 'image') {
				    $thumbPath = $this->createThumbImg(gbkToUtf8($filePath), $savePath);
				}
				
				$reInfo = array(
						'stat'		=> 1,
						'url'		=>	$data['filetype'] == 'image' ? '/' . $thumbPath : '/' . $targetDir . $dbFileName,
						'savePath'	=>	$savePath . $dbFileName,
						'pic'		=>	$dbFileName,
						'original'	=>	$dbFileName,
						'size'		=>	filesize($filePath),
						'resourceid'=>	$resID,
						'type'		=>	getResType($dbFileName),
						'id'		=>	$lastID
				);
			} else {
				// 此处目前仅支持删除本地文件，远程其他待
				if (file_exists($filePath)) {
					//@unlink($filePath);
				}
				die('{"jsonrpc" : "2.0", "error" : {"code": 777, "message": "数据库写入错误！"}, "id" : "id"}');
			}	
		}

		// Return Success JSON-RPC response
		//die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
		$returnArr = array('jsonrpc'=>'2.0', 'result'=>$reInfo, 'id'=>'id');
		die(json_encode($returnArr));
	}
	
	public function importDataPlupload() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	
		// 5 minutes execution time
		set_time_limit(0);
		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds
	
		// 参数过滤
		$resType = I('post.restype');
	
		// 文件上传参数设置
		$allowMaxSize =-1;
	
	
		// 处理上传文件保存路径  && 处理文件保存名称
		$targetDir = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/tmp/';
	
		$fileName = $fileExt = '';
		if (isset($_REQUEST["name"])) {
			$fileName = utf8ToGbk($_REQUEST["name"]);
		} elseif (!empty($_FILES)) {
			$fileName = utf8ToGbk($_FILES["file"]["name"]);
		} else {
			$fileName = uniqid() . '_' . time();
		}
	
		$fileExt = substr($fileName, strrpos($fileName, '.'));
	
		// Create target dir
		if (!file_exists($targetDir)) {
			@mkdir($targetDir, 0777, true);
		}
	
		// Get a file name
		$filePath = $targetDir . $fileName;
		if (file_exists($filePath)) {
			//die('{"jsonrpc" : "2.0", "error" : {"code": 776, "message": "已存在同名文件！"}, "id" : "id"}');
			@unlink($filePath);
		}
	
		// Chunking might be enabled
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
	
		// Remove old temp files
		if ($cleanupTargetDir) {
			if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "打开目录失败！"}, "id" : "id"}');
			}
	
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
	
				/* if (substr($file, -5) == '.part') {
				 @unlink($tmpfilePath);
				} */
	
				// If temp file is current file proceed to the next
				/* if ($tmpfilePath == "{$filePath}.part") {
					continue;
				} */
	
				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
					@unlink($tmpfilePath);
				}
			}
			closedir($dir);
		}
	
	
		// Open temp file
		if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "打开文件失败！"}, "id" : "id"}');
		}
	
		if (!empty($_FILES)) {
			if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "上传失败！"}, "id" : "id"}');
			}
	
			// Read binary input stream and append it to temp file
			if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "打开文件失败！"}, "id" : "id"}');
			}
		} else {
			if (!$in = @fopen("php://input", "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "打开文件失败！"}, "id" : "id"}');
			}
		}
	
		$isWrote = 1;
		while ($buff = fread($in, 4096)) {
			if (fwrite($out, $buff) === false) {
				$isWrote = 0;
				break;
			}
		}
	
		$tmpMLModel = M('ResmanagerFilesTemp');
		$dbFileName = gbkToUtf8($fileName);
		$fileInDB = $tmpMLModel->where(array('filename'=>$dbFileName))->find();
	
		if ($fileInDB) {
			$tmpMLModel->where(array('id'=>$fileInDB['id']))->save(array('chunk'=>$chunk + 1, 'create_time'=>date('Y-m-d H:i:s')));
		} else {
			$tmpMLModel->add(array('filename'=>$dbFileName, 'chunk'=>$chunk + 1, 'create_time'=>date('Y-m-d H:i:s')));
		}
	
		@fclose($out);
		@fclose($in);
	
		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
				
			// Strip the temp .part suffix off
			rename("{$filePath}.part", $filePath);
				
			if ($chunks) {
				$tmpMLModel->where(array('filename'=>$dbFileName))->delete();
			}
			
			if (in_array($resType, array('news', 'weat'))) {     // 处理zip数据包
    			
			    if ($this->eZipFile($filePath)) {
    				@unlink($filePath);
    				if ($resType == 'news') {
    					if (!$this->importNewsDataFromExcel(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim(dirname($filePath)))) {
    						die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
    					}
    				} elseif ($resType == 'weat') {
    					if (!$this->importWeatherDataFromExcel(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim(dirname($filePath)))) {
    						die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
    					}
    				}
    			} else {
    				die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
    			}
    			
			} else {     // 处理Excel文件
			    
			    if ($resType == 'humo') {
			        if (!$this->importHumoDataFromExcel(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($filePath))) {
			            die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
			        }
			    } else if ($resType == 'famo') {
			        if (!$this->importFamoDataFromExcel(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($filePath))) {
			            die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
			        }
			    } else if ($resType == 'baike') {
			        if (!$this->importBaikeDataFromExcel(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($filePath))) {
			            die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
			        }
			    } else if ($resType == 'hist') {
			        if (!$this->importHistDataFromExcel(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($filePath))) {
			            die('{"jsonrpc" : "2.0", "error" : {"code": 701, "message": "数据包解压失败！"}, "id" : "id"}');
			        }
			    }
			}
			
		}
	
		// Return Success JSON-RPC response
		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
		//$returnArr = array('jsonrpc'=>'2.0', 'result'=>array('savepath'=>$filePath), 'id'=>'id');
		//die(json_encode($returnArr));
	}
	
	public function eZipFile($filepath) {
	
		// 压缩包保存路径
		$savePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($filepath);
		// 将压缩包解压到的目录
		$destDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim(dirname($filepath));
	
		$zip = new ZipArchive();
		if ($zip->open($savePath) === TRUE) {
			return $zip->extractTo($destDir);
		} else {
			return false;
		}
		$zip->close();
	}
	
	public function importNewsDataFromExcel($filepath) {
		header('Content-Type:text/html;charset=utf-8');
		$importFilepath = rtrim($filepath, '/') . '/data.xls';
		$importImagepath = rtrim($filepath, '/') . '/images/';
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			return false;
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$newsData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($newsData);
		if (empty($newsData)) {
			return false;
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
		$ni = null;
		foreach ($news as $ni) {
			$ndata = $ni;
			unset($ndata['ex_id']);
			$ndata['news_title'] = str_replace("'", "\"", strip_tags($ndata['news_title']));
			$ndata['news_content'] = str_replace("'", "\"", strip_tags($ndata['news_content']));
			
			// 如果已存在同标题的新闻，则不导入
			if ($newsModel->where(array('news_title'=>$ndata['news_title']))->count() > 0) {
			    continue;
			}
			
			$nInsRe = $newsModel->add($ndata);
			if ($nInsRe && !empty($ndata['gallery'])) {
				$latestNewsId = $newsModel->getLastInsID();
		
				$sni = null;
				foreach ($ndata['gallery'] as $sni) {
					$gdata = $sni;
					unset($gdata['ex_id']);
					$gdata['news_id'] = $latestNewsId;
					$gdata['note'] = str_replace("'", "\"", strip_tags($gdata['note']));
					$re = $galleryModel->add($gdata);
					if ($re) {
						$imgPath = trim($sni['image']);
						if (!empty($imgPath)) {
							$sourcePath = $importImagepath . ltrim($imgPath, '/');
							if (is_file($sourcePath)) {
								$imgDirname = trim(dirname($imgPath), '/');
								$imgBasename = basename($imgPath);
								$targetDir = 'Uploads/reslib/news/' . $imgDirname;
								if (!is_dir($targetDir)) {
									@mkdir($targetDir, 0777, true);
								}
								@copy($sourcePath, $targetDir . '/' . $imgBasename);
							}
						}
					}
						
				}
			}
			/*  else {
				die($newsModel->getDbError());
			} */
		}
		@unlink($importFilepath);
		deldir($importImagepath);
		return true;
	}
	
	public function importWeatherDataFromExcel($filepath) {
		header('Content-Type:text/html;charset=utf-8');
		$importFilepath = rtrim($filepath, '/') . '/data.xls';
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			return false;
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$weatData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($weatData);
		if (empty($weatData)) {
			return false;
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
			$tmp['dayPictureUrl'] = trim($ni['H']);
			$tmp['nightPictureUrl'] = trim($ni['I']);
			array_push($weatInfo, $tmp);
		}
		//dump($weatInfo);exit();
		
		$weatherModel = M('ReslibWeather');
		$weatherModel->where(1)->delete();
		$item = null;
		foreach ($weatInfo as $item) {
			$nInsRe = $weatherModel->add($item);
		}
		@unlink($importFilepath);
		return true;
	}
	
	public function importHumoDataFromExcel($filepath) {
		header('Content-Type:text/html;charset=utf-8');
		$importFilepath = $filepath;
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			return false;
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$resData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($resData);
		if (empty($resData)) {
			return false;
		}
		
		$resInfo = array();
		foreach ($resData as $ni) {
			$tmp = array();
			$tmp['title'] = trim($ni['A']);
			$tmp['contents'] = trim($ni['B']);
			$tmp['ctime'] = time();
			array_push($resInfo, $tmp);
		}
		
		$resModel = D('HumorJokes');
		$item = null;
		foreach ($resInfo as $item) {
		    // 如果已存在同标题的内容，则不导入
		    if ($resModel->where(array('title'=>$item['title']))->count() > 0) {
		        continue;
		    }
			$insRe = $resModel->add($item);
		}
		@unlink($importFilepath);
		return true;
	}
	
	public function importFamoDataFromExcel($filepath) {
		header('Content-Type:text/html;charset=utf-8');
		$importFilepath = $filepath;
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			return false;
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$resData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($resData);
		if (empty($resData)) {
			return false;
		}
		
		$resInfo = array();
		foreach ($resData as $ni) {
			$tmp = array();
			$tmp['author'] = trim($ni['A']);
			$tmp['contents'] = trim($ni['B']);
			$tmp['ctime'] = time();
			array_push($resInfo, $tmp);
		}
		
		$resModel = D('FamousQuotes');
		$item = null;
		foreach ($resInfo as $item) {
		    if (mb_strlen($item['contents'], 'UTF-8') > 100) {
		        continue;
		    }
		    // 如果已存在同标题的内容，则不导入
		    if ($resModel->where(array('contents'=>trim($item['contents'])))->count() > 0) {
		        continue;
		    }
			$insRe = $resModel->add($item);
		}
		@unlink($importFilepath);
		return true;
	}
	
	public function importBaikeDataFromExcel($filepath) {
		header('Content-Type:text/html;charset=utf-8');
		$importFilepath = $filepath;
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			return false;
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$resData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($resData);
		if (empty($resData)) {
			return false;
		}
		
		$resInfo = array();
		foreach ($resData as $ni) {
			$tmp = array();
			$tmp['title'] = trim($ni['A']);
			$tmp['contents'] = trim($ni['B']);
			$tmp['ctime'] = time();
			array_push($resInfo, $tmp);
		}
		
		$resModel = M('ReslibBaike');
		$item = null;
		foreach ($resInfo as $item) {
		    // 如果已存在同标题的内容，则不导入
		    if ($resModel->where(array('title'=>$item['title']))->count() > 0) {
		        continue;
		    }
			$insRe = $resModel->add($item);
		}
		@unlink($importFilepath);
		return true;
	}
	
	public function importHistDataFromExcel($filepath) {
		header('Content-Type:text/html;charset=utf-8');
		$importFilepath = $filepath;
		
		if (!file_exists($importFilepath) || !is_file($importFilepath)) {
			return false;
		}
		
		import('@.ORG.PHPExcel', '', '.php');
		import('@.ORG.PHPExcel180.Classes.PHPExcel.IOFactory', '', '.php');
		$PHPExcelReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPExcel = $PHPExcelReader->load($importFilepath);
		$PHPExcel->setActiveSheetIndex(0);
		$resData = $PHPExcel->getActiveSheet()->toArray(null, true, true, true);
		array_shift($resData);
		if (empty($resData)) {
			return false;
		}
		
		$resInfo = array();
		foreach ($resData as $ni) {
			$tmp = array();
			$tmp['today_month'] = ltrim(trim($ni['A']), '0')*1;
			$tmp['today_day'] = ltrim(trim($ni['B']), '0')*1;
			$tmp['today_month_day'] = ($tmp['today_month'] > 9 ? $tmp['today_month'] : '0' . $tmp['today_month']) . ($tmp['today_day'] > 9 ? $tmp['today_day'] : '0' . $tmp['today_day']);
			$tmp['event_title'] = trim($ni['C']);
			$tmp['event_content'] = trim($ni['D']);
			$tmp['res_source'] = trim($ni['E']);
			$tmp['create_time'] = date('Y-m-d H:i:s');
			$tmp['ctime'] = time();
			array_push($resInfo, $tmp);
		}
		
		$resModel = M('ReslibHistoric');
		$item = null;
		foreach ($resInfo as $item) {
		    // 如果已存在同标题的内容，则不导入
		    if ($resModel->where(array('event_title'=>$item['event_title']))->count() > 0) {
		        continue;
		    }
			$insRe = $resModel->add($item);
		}
		@unlink($importFilepath);
		return true;
	}
	
	public function createThumbImg($img, $folderName = '') {
		
		import('@.ORG.Image');
		
		// 检查上传目录
		$thumbSavePath = rtrim(C('UPLOAD_THUMB_PATH'), '/') . '/' . trim($folderName, '/') . '/';
        if(!is_dir($thumbSavePath)) {
            mkdir($thumbSavePath, 0777, true);
        }
		
		$img = utf8ToGbk($img);
		$thumbName = getFilename($img);
		
		if (file_exists($img)) {
			$imgInfo = Image::getImageInfo($img);
			//if ($imgInfo['width'] > C('THUMB_MAX_WIDTH') || $imgInfo['height'] > C('THUMB_MAX_HEIGHT')) {
				Image::thumb($img, $thumbSavePath . $thumbName, '', C('THUMB_MAX_WIDTH'), C('THUMB_MAX_HEIGHT'));
			//}
		}
		
		return $thumbSavePath . gbkToUtf8($thumbName);
	}
	
	public function genSucaiThumb() {
	    set_time_limit(0);
	    
	    $rmfModel = M('ResmanagerFiles');
	    $resList = $rmfModel->where(array('filetype'=>'image'))->select();
	    $sucaiRootPath = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/';
	    $thumbRootPath = rtrim(C('UPLOAD_THUMB_PATH'), '/') . '/';
	    
	    foreach ($resList as $res) {
	        if (is_file($sucaiRootPath . utf8ToGbk($res['filepath']))) {
	            $this->createThumbImg($sucaiRootPath . $res['filepath'], trim(dirname($res['filepath']), '/'));
	        } else {
	            dump('#E404  File not found.');
	        }
	    }
	}
	
	public function checkExisting() {

		if (file_exists(C('a_main_upfile') . utf8ToGbk($_POST['filename']))) {
			echo 1;
		} else {
			echo 0;
		}
	}
	
	public function checkFileParts() {
		$filename = I('post.filename', '');
		if (!$filename) {
			echo 0;
		}
		
		$tempMLModel = M('MedialibTemp');
		$hasUpChunks = $tempMLModel->where(array('filename'=>utf82gbk($filename)))->getField('chunk');

		if ($hasUpChunks*1 > 0) {
			echo $hasUpChunks;
		} else {
			echo 0;
		}
	}
	
	public function checkFilePartsComm() {
		$filename = I('post.filename', '');
		if (!$filename) {
			echo 0;
		}
		
		$tempMLModel = M('ResmanagerFilesTemp');
		$hasUpChunks = $tempMLModel->where(array('filename'=>utf82gbk($filename)))->getField('chunk');

		if ($hasUpChunks*1 > 0) {
			echo $hasUpChunks;
		} else {
			echo 0;
		}
	}
	
	public function checkFileExisting() {

		if (file_exists(C('UPLOAD_ROOT_PATH') . $_POST['filepath'])) {
			echo 1;
		} else {
			echo 0;
		}
	}
	
	public function delMediaByID() {
		$ids = explode('-', trim(I('post.ids'), '-'));
		
		if (!empty($ids)) {
			$mediaLibModel = D('MediaLib');
			$where = array('id'=>array('in', $ids));
			$fileLists = $mediaLibModel->where($where)->getField('filepath', true);
			
			if (!empty($fileLists)) {
				// 删除数据库记录
				$re = $mediaLibModel ->where($where)->delete();
				if ($re) {
					// 删除物理文件
					foreach ($fileLists as $file) {
						//此处目前仅支持删除本地图片，远程其他待
						$fpath = C('UPLOAD_ROOT_PATH') . $file;
						if (file_exists($fpath)) {
							@unlink($fpath);
						}
					}
					
					echo json_encode(array('stat'=>1));
				} else {
					echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $mediaLibModel->getError()));
				}
			
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
	}
	
	/*
	 * 删除本地文件
	 */
	public function delFiles() {
		$files = I('post.dataFiles');
		$files = trim($files);
		$files = trim($files, ';');
		if (!empty($files)) {
			$filesArr = explode(';', $files);
			if (count($filesArr) <= 0) {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
			} else {
				$failed = 0;
				foreach ($filesArr as $file) {
					$file = iconv('UTF-8','gb2312',$file);  // 处理中文文件名
					if (file_exists($file)) {
						if (!unlink($file)) {
							$failed++;
						}
					}
				}
				
				if ($failed > 0) {
					echo json_encode(array('stat'=>0, 'msg'=>$failed . '个删除失败！'));
				} else {
					echo json_encode(array('stat'=>1));
				}
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
	}
	
	/**
	 * 更新实体属性字段值
	 * {model : dataDBModel, id : dataId, field : fieldName, value : newVal}
	 */
	public function setFieldValue() {
		$model = I('post.model');
		$id = I('post.id', 0, 'int');
		$field = I('post.field');
		$value = I('post.value', '', 'strip_tags');
		
		if (!empty($model) && !empty($id) && !empty($field) && !empty($value)) {
			$dbModel = D($model);
			$result = $dbModel->where(array('id'=>$id))->save(array($field=>utf82gbk($value)));
			if ($result !== false) {
				echo json_encode(array('stat'=>1));
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $dbModel->getError()));
			}
		} else if (empty($value)) {
			echo json_encode(array('stat'=>0, 'msg'=>'保存内容不能为空！'));
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据错误，请刷新页面重试....'));
		}
	}
	
	/**
	 * 修改文件名称
	 * {fpath : fpath, oldname : oldVal, fname : newVal}
	 */
	public function setFileName() {
		$fpath = I('post.fpath', '', 'strip_tags');
		$oldname = I('post.oldname', '', 'strip_tags');
		$newname = I('post.fname', '', 'strip_tags');
		
		if (!empty($fpath) && !empty($oldname) && !empty($newname)) {
			$result = rename($fpath . $oldname, $fpath . $newname);
			if ($result !== false) {
				echo json_encode(array('stat'=>1));
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！'));
			}
		} else if (empty($newname)) {
			echo json_encode(array('stat'=>0, 'msg'=>'文件名称不能为空！'));
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据错误，请刷新页面重试....'));
		}
	}
	
	public function setEndGroup() {
		$tids = trim(I('post.tids'), '-');
		$gid = I('post.gid', 0, 'int');
		
		if (!empty($tids) || !$gid) {
			$tidsArr = explode('-', $tids);
			if (count($tidsArr > 0)) {
				// 获取终端组信息
				$epgModel = D('EndpointsGroups');
				$groups = $epgModel->field(array('id', 'groupname', 'groupclassid'))->where(array('id'=>$gid))->find();
				if ($groups) {
					$tendModel = D('Endpoint');
					$upRe = $tendModel->where(array('tid'=>array('in', $tidsArr)))->save(array('touchEndPoint_GroupClassId'=>$groups['groupclassid']));
					if ($upRe !== false) {
						echo json_encode(array('stat'=>1));
					} else {
						echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $tendModel->getError()));
					}
				} else {
					echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
				}
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据请求错误，请刷新页面重试....'));
		}
	}
	
	public function checkUniName () {
		$type = trim(I('post.type'));
		$inputName = trim($_POST['inputName']);
		$ID = I('post.ID', 0, 'int');
		$PID = I('post.PID');
		$dataModel = trim(I('post.dataModel'));
		
		if (empty($inputName)) {
			echo json_encode(array('stat'=>0, 'msg'=>'名称不能为空！'));
		}
		
		$options = array();
		switch ($type) {
			case 'scence' : $options = array('scenID'=>$ID, 'PID'=>$PID); break;
			case 'item' : $options = array('itemID'=>$ID); break;
			case 'data' : $options = array('dataModel'=>$dataModel, 'dataID'=>$ID); break;
		}
		
		if (!filterInputName($inputName, true, $type, $options)) {
			echo json_encode(array('stat'=>0, 'msg'=>'该名称已存在！请重新输入'));
		} else {
			echo json_encode(array('stat'=>1));
		}
	}
	
	/**
	 * 修改文字滚动框内容
	 */
	public function setMarqueeTxt() {
		if (IS_POST) {
				
			// 数据过滤及验证
			$dataId = I('post.did', 0, 'int');
			$ledString = I('post.ledstring', '', 'strip_tags');
				
			if (!empty($dataId) && !empty($ledString)) {
	
				// 初始化数据模型
				$marqueeModel = D('MqText');
				$data['id'] = $dataId;
				$data['ledstring'] = utf82gbk($ledString);
				$upResult = $marqueeModel->save($data);
	
				if ($upResult !== false) {
					echo json_encode(array('stat'=>1));
				} else {
					echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $marqueeModel->getError()));
				}
	
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'数据错误，请刷新页面重试....'));
			}
	
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据错误，请刷新页面重试....'));
		}
	}
	
	public function getEndInfo() {
		$id = I('post.tid', 0, 'int');
			
		if ($id) {
			$tendModel = D('Endpoint');
			$tend = $tendModel->where(array('tid'=>$id))->find();
		
			if ($tend) {
				$tend['touchEndPointName'] = gbk2utf8($tend['touchEndPointName']);
				$tend['touchEndPoint_ComputerName'] = gbk2utf8($tend['touchEndPoint_ComputerName']);
				$tend['touchEndPoint_DiskTotalSize'] = gbk2utf8($tend['touchEndPoint_DiskTotalSize']);
				$tend['lastShotSnap'] = basename($tend['lastShotSnap']);
				$tend['groupName'] = D('EndpointsGroups')->getGroupPath($tend['touchEndPoint_GroupClassId']);
					
				// 该终端的任务列表列表
				$taskModel = D('EPTask');
				$tendTasks = $taskModel->where(array('touchMainId'=>$tend['touchMainId'], 'isFinished'=>array('neq', '1')))->order('taskId desc')->select();
				if ($tendTasks) {
					foreach ($tendTasks as &$item) {
						$item['commandNote'] = gbk2utf8($item['commandNote']);
					}
					$tend['taskList'] = $tendTasks;
				}
				
				echo json_encode(array('stat'=>1, 'data'=>$tend));
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'请求失败，请刷新页面重试....'));
			}
		
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据错误，请刷新页面重试....'));
		}
	}
	
	public function getResListData() {
		
		$resType = trim(I('post.type'));
		$columnClassId = trim(I('post.classid'));
		
		$resModel = null;
		$fields = $where = array();
		$order = '';
		switch ($resType) {
			case 'news' :
				$resModel = M('ReslibNews');
				$fields = array('id', 'news_title', 'news_date');
				$order = 'news_date desc, id desc';
				break;
			case 'famousQuotes' :
				$resModel = D('FamousQuotes');
				$fields = array('id', 'author', 'contents');
				$order = 'id desc';
				break;
			case 'humorJokes' :
				$resModel = D('HumorJokes');
				$fields = array('id', 'title', 'contents');
				$order = 'id desc';
				break;
			case 'baike' :
				$resModel = M('ReslibBaike');
				$fields = array('id', 'title', 'contents');
				$order = 'id desc';
				break;
			case 'historic' :
				$resModel = M('ReslibHistoric');
				$fields = array('id', 'today_month_day', 'event_title', 'event_content');
				$order = 'today_month_day asc';
				break;
			case 'stores' :
				$resModel = D('SCStore');
				$fields = array('Id', 'sname', 'floor', 'adress');
				$where['program_dir_classid'] = array('notlike', '%,' . $columnClassId . ',%');
				$floor = I('request.floor', 0, 'int');
				$floor && $where['floor'] = $floor;
				$typeID = I('request.type_id', 0, 'int');
				if (!empty($typeID)) {
    				$storeTypeModel = D('SCStoretype');
				    $inIds = $storeTypeModel->getChildrenTypes($typeID, true);
				    $where['type_id'] = array('in', $inIds);
				}
				$order = 'Id desc';
				break;
			default :
				die(json_encode(array('stat'=>0, 'msg'=>'数据请求失败，请刷新页面重试……')));
		}
		
		// 加载数据分页类
		import('ORG.Util.Page');
		
		// 数据分页
		$totals = $resModel->where($where)->count();
		$Page = new Page($totals, 10);
		$show = $Page->show();
		
		$dataList = $resModel->where($where)->field($fields)->order($order)->limit($Page->firstRow. ',' .$Page->listRows)->select();
		
		if ($resType == 'news') {
		    foreach ($dataList as &$item) {
		        $item['news_title'] = stripslashes($item['news_title']);
		    }
		}
		
		if (!empty($dataList)) {
			die(json_encode(array('stat'=>1, 'data'=>array('resList'=>$dataList, 'pager'=>$show))));
		} else {
			die(json_encode(array('stat'=>0, 'msg'=>'无资源数据！')));
		}
	}
	
	public function test() {
		print_r( video_info(iconv('UTF-8', 'GBK', C('UPLOAD_COMM_PATH') . '/sucai/ygrdkk.mp4'), rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . C('MY_PUBLIC_PATH') . '/tools/ffmpeg.exe'));
	}
	
	public function importResDataToArticle() {
		
		$coluClassID = trim(I('post.coluClassID'));
		$resType = trim(I('post.type'));
		
		if (empty($coluClassID) || empty($resType) || !in_array($resType, array('famousQuotes','humorJokes','baike', 'historic'))) {
			die(json_encode(array('stat'=>0, 'msg'=>'数据请求失败，请刷新页面重试……')));
		}
		
		$resModel = null;
		$fields = array();
		$order = '';
		switch ($resType) {
			case 'famousQuotes' :
				$resModel = D('FamousQuotes');
				$fields = array('id', 'author', 'contents');
				$order = 'id desc';
				break;
			case 'humorJokes' :
				$resModel = D('HumorJokes');
				$fields = array('id', 'title', 'contents');
				$order = 'id desc';
				break;
			case 'baike' :
				$resModel = M('ReslibBaike');
				$fields = array('id', 'title', 'contents');
				$order = 'id desc';
				break;
			case 'historic' :
				$resModel = M('ReslibHistoric');
				$fields = array('id', 'today_month_day', 'event_title', 'event_content');
				$order = 'today_month_day asc';
				break;
			default :
				die(json_encode(array('stat'=>0, 'msg'=>'数据请求失败，请刷新页面重试……')));
		}
		
		$articleModel = M('ProgramsArticles');
		$articleModel->where(array('program_dir_classid'=>$coluClassID))->delete();
		if (in_array($resType, array('famousQuotes','humorJokes','baike'))) {
			
			$resIDList = $resModel->getField('id', true);
			$randIds = array_rand($resIDList, 100);
			foreach ($randIds as $resId) {
				$resData = $resModel->field($fields)->where(array('id'=>$resId))->find();
				if ($resData) {
					$artiData = array();
					$artiData['article_classid'] = generateUniqueID();
					$artiData['program_dir_classid'] = $coluClassID;
					
					if ($resType == 'famousQuotes') {
						$artiData['article_name'] = $resData['author'];
						$artiData['article_content'] = $resData['contents'];
					} elseif ($resType == 'humorJokes') {
						$artiData['article_name'] = $resData['title'];
						$artiData['article_content'] = $resData['contents'];
					} elseif ($resType == 'baike') {
						$resData['contents'] = trim(str_replace(array('&nbsp;', '　'), '', strip_tags($resData['contents'])));
						$resData['contents'] = str_replace('０', '0', $resData['contents']);
						$resData['contents'] = str_replace('１', '1', $resData['contents']);
						$resData['contents'] = str_replace('２', '2', $resData['contents']);
						$resData['contents'] = str_replace('３', '3', $resData['contents']);
						$resData['contents'] = str_replace('４', '4', $resData['contents']);
						$resData['contents'] = str_replace('５', '5', $resData['contents']);
						$resData['contents'] = str_replace('６', '6', $resData['contents']);
						$resData['contents'] = str_replace('７', '7', $resData['contents']);
						$resData['contents'] = str_replace('８', '8', $resData['contents']);
						$resData['contents'] = str_replace('９', '9', $resData['contents']);
						$resData['contents'] = str_replace('А', 'A', $resData['contents']);
						$resData['contents'] = str_replace('Б', 'B', $resData['contents']);
						$resData['contents'] = str_replace('С', 'C', $resData['contents']);
						$resData['contents'] = str_replace('Ｆ', 'F', $resData['contents']);
						$resData['contents'] = str_replace('Ｇ', 'G', $resData['contents']);
						$resData['contents'] = str_replace('Ｒ', 'R', $resData['contents']);
						$resData['contents'] = str_replace('Ｎ', 'N', $resData['contents']);
						$resData['contents'] = str_replace('Ｉ', 'I', $resData['contents']);
						$resData['contents'] = str_replace('У', 'Y', $resData['contents']);
						$artiData['article_name'] = $resData['title'];
						$artiData['article_content'] = $resData['contents'];
					}
					
					$artiData['article_content_type'] = 'txt';
					$articleModel->add($artiData);
				}
			}
			
		} else {
			for ($i=0; $i<7; $i++) {
				$resDataList = $resModel->field($fields)->where(array('today_month_day'=>date('md', strtotime('+'.$i.'day'))))->order($order)->select();
				foreach ($resDataList as &$resData) {
					$resData['event_title'] = trim(str_replace(array('&nbsp;', '　'), '', strip_tags($resData['event_title'])));
					$resData['event_content'] = trim(str_replace(array('&nbsp;', '　'), '', strip_tags($resData['event_content'])));
					$resData['event_content'] = str_replace('０', '0', $resData['event_content']);
					$resData['event_content'] = str_replace('１', '1', $resData['event_content']);
					$resData['event_content'] = str_replace('２', '2', $resData['event_content']);
					$resData['event_content'] = str_replace('３', '3', $resData['event_content']);
					$resData['event_content'] = str_replace('４', '4', $resData['event_content']);
					$resData['event_content'] = str_replace('５', '5', $resData['event_content']);
					$resData['event_content'] = str_replace('６', '6', $resData['event_content']);
					$resData['event_content'] = str_replace('７', '7', $resData['event_content']);
					$resData['event_content'] = str_replace('８', '8', $resData['event_content']);
					$resData['event_content'] = str_replace('９', '9', $resData['event_content']);
					
					$artiData = array();
					$artiData['article_classid'] = generateUniqueID();
					$artiData['program_dir_classid'] = $coluClassID;
					$artiData['article_name'] = $resData['today_month_day'];
					$artiData['article_content'] = strip_tags(stripslashes($resData['event_title']));
					$artiData['article_content_type'] = 'txt';
					$articleModel->add($artiData);
				}
			}
			
		}
		
		die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
	}
	
	public function getResManagerByType() {
		$type = I('post.type');
		$dirClassId = I('post.classid');
		$return = array();
		$where = array('status'=>1);
		$where['filetype'] = $type;
		$where['belong_dirclassid'] = $dirClassId;
		
		$resList = M('ResmanagerFiles')->field(array('id','filename','filetype','filepath'))->where($where)->select();
		
		if (!empty($resList)) {
			foreach ($resList as &$file) {
				$file['filename'] = gbk2utf8($file['filename']);
			}
			$return = array('stat'=>1, 'data'=>$resList);
		} else {
			$return = array('stat'=>0, 'msg'=>'没有获取到可用的资源！请检查您的素材库……');
		}
		
		echo json_encode($return);
	}
	
	//选择资源到临时表？
	//素材资源选择器弹窗的“添加”按钮
	public function selectResToTemptbl() {
		$type = I('post.type');
		$porgId = I('post.porg_id');
		$tempId = I('post.temp_id');
		$resId = I('post.res_id', 0, 'int');
		$return = array();
		
		if (!$tempId) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！')));
		}
		
		if (!$resId) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！')));
		}
		
		$res = M('ResmanagerFiles')->field(array('id','classid','filename','filetype','filepath', 'file_desc'))->where(array('id'=>$resId))->find();
		//file_put_contents("debug.txt",PHP_EOL."debug:".PHP_EOL.serialize($res).PHP_EOL,FILE_APPEND);//zjh
		
		if (!$res) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！')));
		}else{
			//zjh 原始文件名
			$resFileName = $res['filename'];
			$resFileDesc = $res['file_desc'];	
		}
		
		$sourcePath = rtrim(C('UPLOAD_COMM_PATH'), '/') . '/sucai/' .utf8ToGbk($res['filepath']);
		if (!file_exists($sourcePath)) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：文件不存在！')));
		}
		
		$dbSavePath = 'program/' . $porgId . '/article/' . $tempId . '/';
		$destPath = rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . $dbSavePath;
		if (!is_dir($destPath)) {
			if (!mkdir($destPath, 0777, true)) {
				die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：文件目录' . $destPath .'不存在！')));
			}
		}
		
		//$basename = getFilename($res['filepath']);
		/* if (file_exists($destPath . utf8ToGbk($basename))) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：同名的文件已被添加！')));
		} */
		
		$basename = generateUniqueID() . strrchr($res['filepath'], '.');
		if (!copy($sourcePath, $destPath . $basename)) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：文件复制失败！')));
		}
		
		$mediaLibModel = D('MediaLib');
		$data['filepath'] = str_replace('/', '\\', $dbSavePath . $basename);
		$data['resourceid'] = $tempId;
		$data['news_note'] = addslashes($res['file_desc']);
		$ext = substr($basename, strrpos($basename, '.') + 1);
		
		if ($ext =="pdf"){//zjh add
			$pdfpath = $data['filepath'];
		}else{
			$pdfpath = NULL;
		}
		
		//TB_MediaLib表增加一条记录
		$mediaLibResult = $mediaLibModel->execute("insert into TB_MediaLib (filepath, type, resourceid, news_note,pdfpath,origin_filename,file_desc) values ('" . $data['filepath'] . "', '" . $ext . "' ,'" . $data['resourceid'] . "', '" . $data['news_note'] . "','".$pdfpath."','".$resFileName."','".$resFileDesc."' )");
		
		if ($mediaLibResult !== false) {
			
			$lastestID = $mediaLibModel->getLastInsID();
			
			//tb_programs_articles_temp表增加一条记录
			$artiModel = M('ProgramsArticlesTemp');//节目文章临时表
			$data['tmp_article_classid'] = $tempId;
			$data['res_id'] = $lastestID;
			$data['filename'] = $res['filename'];
			$data['filetype'] = $res['filetype'];
			$data['filepath'] = $dbSavePath . $basename;
			$data['url'] = '/' . $destPath . $basename;
			$data['act'] = $type;
			$data['ctime'] = time();
			$artiModel->add($data);
			/* if ($re === false) {
				$return = array('stat'=>0, 'msg'=>'操作失败！请刷新页面重试……');
			} */
			$return = array('stat'=>1, 'data'=>$data);
			echo json_encode($return);
		} else {
			unlink($destPath . utf8ToGbk($basename));
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据库写入失败[N001]！')));
		}
	}
	
	//
	public function removeResToTemptbl() {
		$type = I('post.type');
		$tempId = I('post.temp_id');
		$resId = I('post.res_id', 0, 'int');
		$return = array();
		
		if (!$tempId) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！')));
		}
		
		if (!$resId) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！')));
		}
		
		$mediaLibModel = D('MediaLib');
		$resInfo = $mediaLibModel->where(array('id'=>$resId))->find();
		if ($resInfo) {
			$filepath = rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . utf8ToGbk($resInfo['filepath']);
			if(I('post.article_type') == 'videos'){
				unlink($filepath);
			}

			$artiModel = M('ProgramsArticlesTemp');
			$isNewAdd = $artiModel->where(array('res_id'=>$resId, 'tmp_article_classid'=>$tempId, 'act'=>'add'))->count();
			
			if ($isNewAdd) {
				
				$re = $mediaLibModel->where(array('id'=>$resId))->delete();
				if ($re !== false) {
					unlink($filepath);
					$artiModel->where(array('res_id'=>$resId, 'tmp_article_classid'=>$tempId, 'act'=>'add'))->delete();
					die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
				} else {
					die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据库操作失败[N001]！')));
				}
				
			} else {
				
				$data['tmp_article_classid'] = $tempId;
				$data['res_id'] = $resId;
				$data['filename'] = '';
				$data['filetype'] = '';
				$data['filepath'] = '';
				$data['act'] = $type;
				$data['ctime'] = time();
				$re = $artiModel->add($data);
				if ($re !== false) {
					die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
				} else {
					die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据库操作失败[N002]！')));
				}
			}
		} else {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！请刷新页面重试……')));
		}
	}
	
	public function cancelSaveArticle() {
		
		$tempId = I('post.temp_id');
		
		if (!$tempId) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据错误！')));
		}
		
		$artiModel = M('ProgramsArticlesTemp');
		$delIds = $artiModel->where(array('tmp_article_classid'=>$tempId, 'act'=>'add'))->getField('res_id', true);
		if (count($delIds) > 0) {
			$mediaLibModel = D('MediaLib');
			$mediaLibModel->where(array('id'=>array('in', $delIds)))->delete();
		}
			
		$artiModel->where(array('tmp_article_classid'=>$tempId))->delete();
		
		die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
	}

	public function resDownload() {
		import('ORG.Net.Http');
		
		$fid = I('get.fid', 0, 'int');
		if (!$fid) {
			$this->error('下载文件不存在！');
		} 

		$mediaLibModel = D('MediaLib');
		$filename = $mediaLibModel->where(array('id'=>$fid))->getField('filepath');
		
		if (!$filename) {
			$this->error('下载文件不存在！');
		}

		Http::download(C('UPLOAD_ROOT_PATH') . $filename);
	}

	public function resDownloadByFilePath() {
		import('ORG.Net.Http');
		$fpath = I('get.fpath');
		$fname = I('get.fname');
		
		if (empty($fname)) {
			$this->error('下载文件不存在！');
		} 

		Http::download(C('UPLOAD_ROOT_PATH') . $fpath . '/' . utf82gbk($fname));
	}
	
	public function delResFromMedialib() {
		
		$resourceid = trim(I('post.resid'));
		if (empty($resourceid)) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：请求数据错误！')));
		}
		
		$mediaLibModel = D('MediaLib');
		$where = array('resourceid'=>$resourceid);
		$resList = $mediaLibModel->where($where)->field('filepath')->select();
		foreach ($resList as $res) {
			if (trim($res['filepath']) != '') {
				if (!unlink(rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . str_replace('\\', '/', $res['filepath']))) {
					die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：无法删除文件！')));
				}
			}
		}
		
		$re = $mediaLibModel->where($where)->delete();
		if ($re !== false) {
			die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
		} else {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $mediaLibModel->getError())));
		}
	}
	
	public function delResFromMedialibByIncreID() {
		
		$resId = I('post.resid', 0, 'int');
		if (!$resId) {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：请求数据错误！')));
		}
		
		$mediaLibModel = D('MediaLib');
		$where = array('id'=>$resId);
		$resList = $mediaLibModel->where($where)->field('filepath')->select();
		foreach ($resList as $res) {
			if (trim($res['filepath']) != '') {
			    $filepath = ltrim(str_replace('\\', '/', $res['filepath']), '/');
			    $originFilepath = rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . $filepath;
				if (!unlink($originFilepath)) {
					die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：无法删除文件！')));
				}
				
				$thumbFilepath = rtrim(C('UPLOAD_THUMB_PATH'), '/') . '/' . $filepath;
				if (is_file($thumbFilepath)) {
				    unlink($thumbFilepath);
				}
			}
		}
		
		$re = $mediaLibModel->where($where)->delete();
		if ($re !== false) {
			die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
		} else {
			die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $mediaLibModel->getError())));
		}
	}

	/**
	 * 播放音乐
	 */	
	public function musicPLayer() {
		$id = I('get.id', 0, 'int');
		$mediaLibModel = D('MediaLib');
		$filepath = $mediaLibModel->where(array('id'=>$id))->getField('filepath');
		$this->assign('isAdvanceBrowser', isAdvanceBrowser());
		$this->assign('filepath', $filepath);
		$this->assign('mediatype', 'music');
		$this->display('Plugins:mediaPlayer');
	}
	
	public function resDescText() {
		
		$resID = I('post.id', 0, int);
		$action = trim(I('post.action')) == '' ? 'r' : trim(I('post.action'));
		$fileDesc = trim(I('post.file_desc'));
		
		if (!$resID) {
			die(json_encode(array('stat'=>0, 'msg'=>'参数错误！')));
		}
		
		$model = M('ResmanagerFiles');
		$where = array('id'=>$resID);
		$re = null;
		
		if ($action == 'r') {
			$re = $model->where($where)->getField('file_desc');
			die(json_encode(array('stat'=>$re ? 1 : 0, 'msg'=>$re ? stripslashes($re) : '')));
		} else {
			$re = $model->where($where)->save(array('file_desc'=>$fileDesc));
			die(json_encode(array('stat'=>$re ? 1 : 0)));
		}
		
	}
	
	/**
	 * 获取终端组信息
	 */
	public function getEndGroupInfo() {
		
		$id = I('post.id', 0, 'int');
		$et = I('post.et');
			
		if (!$id)
			die(json_encode(array('stat'=>0, 'msg'=>'参数错误！')));
			
		$epgModel = D('EndpointsGroups');
		$groupi = $epgModel->where(array('id'=>$id))->find();
		if ($groupi) {
			if (trim($groupi['tplclassid']) != '') {
				if (trim($et) == 'x86') {
					$tplInfo = D('Tpls')->where(array('tplclassid'=>$groupi['tplclassid']))->find();
					if (!empty($tplInfo)) {
						$groupi['tplname'] = $tplInfo['tplname'];
						if (trim($tplInfo['binding_program_classid']) != '') {
							$projname = M('Programs')->where(array('program_classid'=>trim($tplInfo['binding_program_classid'])))->getField('program_name');
							$groupi['projname'] = $projname ? $projname : '未绑定';
						}
					} else {
						$groupi['tplname'] = '未指定';
						$groupi['projname'] = '未绑定';
					}
				} else {
					$projname =  D('TBAPlaylists')->where(array('pl_classid'=>$groupi['tplclassid']))->getField('pl_name');
					$groupi['projname'] = $projname ? $projname : '未绑定';
				}
				
			} else {
				$groupi['tplname'] = '未指定';
				$groupi['projname'] = '未绑定';
			}

			$gpath = '';
			$gpathArr = $epgModel->getGroupPath($groupi['groupclassid']);
			foreach ($gpathArr as $key=>$grp) {
				$gpath .= ($key > 0 ? '&nbsp;&nbsp;&gt;&nbsp;' : '') . $grp['groupname'];
			}
			$groupi['gpath'] = $gpath;
						
			$groupi['has_sub'] = $epgModel->where(array('parent_classid'=>$groupi['groupclassid']))->count();
			die(json_encode(array('stat'=>1, 'data'=>$groupi)));
		} else {
			die(json_encode(array('stat'=>0, 'msg'=>'没有获取到数据！')));
		}
	}
	
	/**
	 * 获取终端配置信息
	 */
	public function getTEndpointCfgInfo() {
	    $tid = I('post.tid', 0, 'int');
	    
	    if (empty($tid)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'请求参数错误1！')));
	    }
	    
	    $tendModel = D('Endpoint');
	    $tend = $tendModel->field(array('touchMainCloseTime','touchMainAdsDelayTime','touchMainExitCode','touchEndPointInterval','touchEndPointName','isAutoClose','isAutoRun','isShowVerTips','isAdsPlay','exitPassword','needPassword', 'touchEndPointOpenTime'))->where(array('tid'=>$tid))->find();
	    
	    if ($tend) {
	        die(json_encode(array('stat'=>1, 'data'=>$tend)));
	    } else {
	        die(json_encode(array('stat'=>0, 'msg'=>'无数据！')));
	    }
	}
	
	/**
	 * 获取广告安卓机的节目
	 */
	public function getTBAPrograms() {
		$dataList = D('TBAPrograms')->where(array('bevalid'=>1))->order('id asc')->field('id, tplname, tplclassid')->select();
		die(json_encode($dataList));
	}
	
	/**
	 * 删除商铺相册图片
	 */
	public function delStoreGalleryItem() {
	    $storeClassId = I('post.storeclassid');
	    $resourceId = I('post.resourceid');
	    
	    if (empty($storeClassId) || empty($resourceId)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'请求参数错误1！')));
	    }
	    
	    $storeModel = D('SCStore');
	    $where = array('classid'=>$storeClassId);
	    $storeInfo = $storeModel->where($where)->find();
	    if (empty($storeInfo) || empty($storeInfo['figurechart'])) {
	        die(json_encode(array('stat'=>0, 'msg'=>'请求参数错误2！')));
	    }
	    
	    $mediaLibModel = D('MediaLib');
	    $where2 = array('resourceid'=>$resourceId);
	    $filepath = $mediaLibModel->where($where2)->getField('filepath');
        if (trim($filepath) != '') {
    	    $re = $mediaLibModel->where($where2)->delete();
    	    if ($re) {
                @!unlink(rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . str_replace('\\', '/', $filepath));
    	    } else {
    	        die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $mediaLibModel->getError())));
    	    }
        }
	    
        $galleryInfo = str_replace($resourceId . ',', '', $storeInfo['figurechart']);
        $data['figurechart'] = $galleryInfo;
        if ($resourceId == $storeInfo['store_cover']) {
            $data['store_cover'] = !empty($storeInfo['logo']) ? $storeInfo['logo'] : '';
        }
        $upResult = $storeModel->where($where)->save($data);
        if ($upResult !== false) {
            die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
        } else {
            die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $storeModel->getError())));
        }
	    
	}
	
	/**
	 * 设置商铺相册图片为商铺封面图
	 */
	public function setStoreCover() {
	    $storeClassId = I('post.storeclassid');
	    $resourceId = I('post.resourceid');
	    
	    if (empty($storeClassId) || empty($resourceId)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'请求参数错误1！')));
	    }
	    
	    $storeModel = D('SCStore');
	    $where = array('classid'=>$storeClassId, 'figurechart'=>array('like', '%' . $resourceId . ',%'));
	    $storeInfo = $storeModel->where($where)->find();
	    if (empty($storeInfo)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'请求参数错误2！')));
	    }
	    
        $upResult = $storeModel->where(array('Id'=>$storeInfo['Id']))->save(array('store_cover'=>$resourceId));
        if ($upResult !== false) {
            die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
        } else {
            die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $storeModel->getError())));
        }
	    
	}
	
	/**
	 * 绑定商铺到栏目
	 */
	public function bingStoresToColumn() {
	
	    $columnClassID = trim(I('post.coluClassID'));
	    $postData = trim(I('post.postData'), ',');
	    
	    if (empty($columnClassID)) {
            die(json_encode(array('stat'=>0, 'msg'=>'[ColumnClassID]网络数据错误，请刷新页面重试……！')));
	    }
	
        if (empty($postData) || count(explode(',', $postData)) <= 0) {
            die(json_encode(array('stat'=>0, 'msg'=>'[PostData]网络数据错误，请刷新页面重试……！')));
        }
        
        $columnInfo = M('ProgramsDirs')->where(array('classid'=>$columnClassID))->find();
        if (!$columnInfo) {
            die(json_encode(array('stat'=>0, 'msg'=>'[Not Valid Column]网络数据错误，请刷新页面重试……！')));
        }
        
	    $storeModel = D('SCStore');
	    $where = array('Id'=>array('in', explode(',', $postData)));
	    $stores = $storeModel->field(array('Id, program_dir_classid'))->where($where)->select();
	    if (empty($stores)) {
            die(json_encode(array('stat'=>0, 'msg'=>'[Not Valid StoreIds]网络数据错误，请刷新页面重试……！')));
	    }
	    
	    $toBindColumnClassID = ',' . $columnClassID . ',';
	    foreach ($stores as $st) {
	        if (empty($st['program_dir_classid'])) {
    	       $data = array('program_dir_classid'=>$toBindColumnClassID);
	        } else {
	           if (strpos($st['program_dir_classid'], $toBindColumnClassID) === false) {
	               $data = array('program_dir_classid'=>rtrim($st['program_dir_classid'], ',') . $toBindColumnClassID);
	           } else {
	               continue;
	           }
	        }
    	    $bindResult = $storeModel->where(array('Id'=>$st['Id']))->save($data);
    	    /* if ($bindResult !== false) {
                die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
    	    } else {
                die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $storeModel->getDBError())));
    	    } */
	    }
	    
        die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
	}
	
	/**
	 * 从栏目解绑商铺
	 */
	public function unbingStoresToColumn() {
	    
	    $columnClassID = trim(I('post.coluClassID'));
	    $postData = trim(I('post.postData'), ';');
	     
	    if (empty($columnClassID)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'[ColumnClassID]网络数据错误，请刷新页面重试……！')));
	    }
	    
	    if (empty($postData) || count(explode(';', $postData)) <= 0) {
	        die(json_encode(array('stat'=>0, 'msg'=>'[PostData]网络数据错误，请刷新页面重试……！')));
	    }
	    
	    $storeModel = D('SCStore');
	    $where = array('Id'=>array('in', explode(';', $postData)));
	    $stores = $storeModel->field(array('Id, program_dir_classid'))->where($where)->select();
	    if (empty($stores)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'[Not Valid StoreIds]网络数据错误，请刷新页面重试……！')));
	    }
	     
	    $unBindColumnClassID = ',' . $columnClassID . ',';
	    foreach ($stores as $st) {
            if (strpos($st['program_dir_classid'], $unBindColumnClassID) !== false) {
                $data = array('program_dir_classid'=>str_replace($unBindColumnClassID, ',', $st['program_dir_classid']));
            } else {
                continue;
            }
	        $bindResult = $storeModel->where(array('Id'=>$st['Id']))->save($data);
	        /* if ($bindResult !== false) {
	         die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
	         } else {
	         die(json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $storeModel->getDBError())));
	        } */
	    }
	     
	    die(json_encode(array('stat'=>1, 'msg'=>'操作成功！')));
	    
	}
	
	public function autoGenAdsRes() {
	    set_time_limit(0);
	    
	    $adsClassID = I('post.classid');
	    $sizeType = I('post.sizetype');
	    
	    if (empty($adsClassID)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'网络数据错误，请刷新页面重试……！')));
	    }
	    
	    $resModel = D('FamousQuotes');
	    $mediaLibModel = D('MediaLib');
	    $fields = array('id', 'author', 'contents');
	    $resIDList = $resModel->getField('id', true);
	    $fontType = 'Public/MSYH.TTF';
	    $originResPath = 'Public/images/bg/';
	    $dbSavepath = '/ads/' . $adsClassID . '/';
	    $dirSavepath = rtrim(C('UPLOAD_ROOT_PATH'), '/') . $dbSavepath;
	    if (!is_dir($dirSavepath) && !mkdir($dirSavepath, 0777, true)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'无法创建本地存储目录，请检查目录权限……！')));
	    }
	    if (!empty($resIDList)) {
	       $randIds = array_rand($resIDList, 10);
	       $resData = $resModel->field($fields)->where(array('id'=>array('in', $randIds)))->select();
	       for ($i=1; $i<=10; $i++) {
	           $text = '　　';
	           $text .= trim($resData[$i-1]['contents']);
	           mb_strlen($resData[$i-1]['author'], 'utf-8') > 10 && $resData[$i-1]['author'] = '';
	           $text .= trim($resData[$i-1]['author']) == '' ? '' : ' ——' . $resData[$i-1]['author'];
	           $markText = autowrap(20, 0, $fontType, $text, ($sizeType == 'h' ? 1180 : 720));
	           $filename = generateUniqueID() . '.jpg';
	           if (setWater($originResPath . ($sizeType == 'h' ? '' : 'v/') . $i . '.jpg', $dirSavepath . $filename, '', $markText, '85,85,85', '5', 20, $fontType, 'text')) {
	               
	               $data['filepath'] = str_replace('/', '\\', ltrim($dbSavepath, '/')) . $filename;
	               $data['resourceid'] = $adsClassID;
	               $ext = 'jpg';
	               $mediaLibModel->execute("insert into TB_MediaLib (filepath, type, resourceid) values ('" . $data['filepath'] . "', '" . $ext . "' , '" . $data['resourceid'] . "')");
	               
	               $this->createThumbImg($dirSavepath . $filename, $dbSavepath);
	           }
	       }
	    } else {
	        for ($i=1; $i<=10; $i++) {
	            $filename = generateUniqueID() . '.jpg';
    	        if (copy($originResPath . ($sizeType == 'h' ? '' : 'v/') . $i . '.jpg', $dirSavepath . $filename)) {
    	            
    	            $data['filepath'] = str_replace('/', '\\', ltrim($dbSavepath, '/')) . $filename;
    	            $data['resourceid'] = $adsClassID;
    	            $ext = 'jpg';
    	            $mediaLibModel->execute("insert into TB_MediaLib (filepath, type, resourceid) values ('" . $data['filepath'] . "', '" . $ext . "' , '" . $data['resourceid'] . "')");
    	            
    	            $this->createThumbImg($dirSavepath . $filename, $dbSavepath);
    	        }
	        }
	    }
	    
    	die(json_encode(array('stat'=>1)));
	}
	
	/**
	 * 批量回收or恢复文章，从前端页面接收到以逗号分隔的文章id字符串，挨个处理
	 * @return [string] ['1'表示成功；'0'表示失败]
	 */
	public function ajaxMultiTrashOrRestore(){
		$operation = $_POST[operation];
		$ids = explode(',', $_POST[ids]);
		foreach($ids as $id){
			if($id && $id!='on'){
				if($operation == 'trash'){
					$data = M('ProgramsArticles')->find($id);
					unset($data[id]);
					$data[recycle_time] = date('Y-m-d H:i:s');
					$trashModel = M('ProgramsTrasharticles');
					$trashModel->create($data);
					if($trashModel->add()){
						if(!M('ProgramsArticles')->delete($id)){
							die('文章删除失败！');
						}
					}else{
						die('文章回收失败！');
					}
				}else if($operation == 'restore'){
					$data = M('ProgramsTrasharticles')->find($id);
					unset($data[id]);
					$articleModel = M('ProgramsArticles');
					$articleModel->create($data);
					if($articleModel->add()){
						if(!M('ProgramsTrasharticles')->delete($id)){
							die('回收文章删除失败！');
						}
					}else{
						die('文章恢复失败！');
					}
				}
			}
		}
		die('1');
	}

	/**
	 * ajax批量删除开关机月计划
	 * @return [成功返回字符串1] [否则返回字符串0]
	 */
	public function ajaxMultiDelPowerDayArrange(){
		$ids = $_POST['ids'];
		foreach($ids as $id){
			if($id && M()->table('TB_PowerDatePlans')->delete($id)){
				$r++;
			}
		}
		count($ids) == $r ? die('1') : die('0');
	}
}