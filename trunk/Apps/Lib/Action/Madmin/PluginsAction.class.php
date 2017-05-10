<?php
/**
 * 插件控制器
 * @author Skeam TJ
 *
 */
class PluginsAction extends CommonAction {
	
	private $pluginsTypesModels = array(
		// itemtype_classid		=>		model_name
		'Widget-Box-0000'		=>		'SlideIV', 				// 图片视频滑动框
		'Widget-Box-0001'		=>		'VedioLoop',			// 视频循环播放器
		'Widget-Box-0002'		=>		'TxtRtf',				// 文本显示框
		'Widget-Box-0003'		=>		'SlideUc',				// 图片幻灯框
		'Widget-Box-0004'		=>		'BookFlip',				// 虚拟电子翻书
		'Widget-Box-0006'		=>		'MusicPlayer',			// 音乐播放器
		'Widget-Box-0007'		=>		'WebPC',				// 网页浏览器
		'Widget-Box-0008'		=>		'FSAlert',				// 全屏内容弹窗
		'Widget-Self-0001'		=>		'MqText',				// 文字滚动框
		'Widget-Box-PhotoRing'	=>		'PhotoRing',			// 多点互动图片框
		'Widget-Box-0011'		=>		'ButtonsBox',			// 图片按钮箱
	);

	public function _initialize() {
		
		parent::_initialize();

		// 定义大文件上传类型过滤器
		$hugeMimeTypes = array(
				'image'			=>	implode(',', C('UPIMG_ALLOW_TYPES')),
				'video' 		=>	implode(',', C('UPVIDEO_ALLOW_TYPES')),
				'text'			=>	'xps,txt,rtf,pdf'
			);
		$this->assign('hugeMimeTypes', $hugeMimeTypes);
	}
	
	public function index() {
		$pluginsID = I('get.pid', 0, 'int');
		if ($pluginsID) {
			
			// 根据插件id获取插件信息，以及相应的场景信息
			$pluginsModel = D('Plugins');
			$mediaLibModel = D('MediaLib');
			$plugins = $pluginsModel->field(array('id','classid','name','belong_scenceid','itemtype_classid'))->find($pluginsID);
			if ($plugins) {
				$this->assign('mediaPath', $plugins['belong_scenceid'] . '/' . $plugins['classid']);
				
				$plugins['name'] = gbk2utf8($plugins['name']);
				$plugins['belong_scencename'] = gbk2utf8(D('Scences')->where(array('classid'=>$plugins['belong_scenceid']))->getField('scencename'));
				$plugins['belong_scence_id'] = gbk2utf8(D('Scences')->where(array('classid'=>$plugins['belong_scenceid']))->getField('id'));
				
				// 获取场景级联数据，作为面包屑
				if (trim($plugins['belong_scenceid']) != '') {
					$belongScenceInfo = D('Scences')->field(array('id', 'scencename', 'parentscence_id'))->where(array('classid'=>$plugins['belong_scenceid']))->find();
					$SCrumb = array();
					if (trim($belongScenceInfo['parentscence_id']) != '') {
						$this->generateScenceCrumb($SCrumb, $belongScenceInfo['parentscence_id']);
					}
					$SCrumb[] = array('id'=>$belongScenceInfo['id'], 'scencename'=>gbk2utf8($belongScenceInfo['scencename']));
					$this->assign('crumb', $SCrumb);
				}
				
				$pluginsTypes = $this->pluginsTypes();
				$plugins['type_name'] = $pluginsTypes[$plugins['itemtype_classid']];
				
				// 实例化插件数据模型
				$pluginsDataModel = D($this->pluginsTypesModels[$plugins['itemtype_classid']]);
				$this->assign('dataModelName', $this->pluginsTypesModels[$plugins['itemtype_classid']]);
				
				// 通用搜索条件
				$where['itemclassid'] = $plugins['classid'];
				
				// 加载数据分页类
				import('ORG.Util.Page');
				// 数据分页
				$totals = $pluginsDataModel->where($where)->count();
				$Page = new Page($totals, 12);
				$show = $Page->show();
				$this->assign('page', $show);
				
				// 根据不同的插件类型获取数据，并加载不同模板
				if ($plugins['itemtype_classid'] == 'Widget-Box-0000') {  // 图片视频滑动框
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as &$item) {
							$item['dataname'] = gbk2utf8($item['dataname']);
							if ($item['isfolder'] == 'True') {
								$item['dataType'] = '文件夹';
							} else if ($item['isfolder'] == 'False') {
								$resData = $mediaLibModel->where(array('resourceid'=>$item['resourceid']))->getField('filepath');
								$item['dataTypeCode'] = getFileType($resData);
								switch ($item['dataTypeCode']) {
									case 'image':
										$item['dataType'] = '图片';
										break;
									case 'video':
										$item['dataType'] = '视频';
										break;
									default:
										$item['dataType'] = '--';
								}
							}
						}
					}
					
					$this->assign('dataType', 'imagevideo');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0001') {  // 视频循环播放器
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as $key=>&$item) {
							if ($item['isfolder'] == 'True' && trim($item['resourceid']) == '') {
								unset($pluginsData[$key]);
								continue;
							}
							
							$item['dataname'] = gbk2utf8($item['dataname']);
							if ($item['isfolder'] == 'True') {
								$item['dataType'] = '文件夹';
							} else if ($item['isfolder'] == 'False') {
								$item['dataType'] = '视频';
							}
						}
					}
					
					$this->assign('dataType', 'video');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0002') {  // 文本显示框
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as &$item) {
							$item['dataname'] = gbk2utf8($item['dataname']);
							$item['dataType'] = '文件';
						}
					}
					
					$this->assign('dataType', 'text');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0003') {  // 图片幻灯框
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as $key=>&$item) {
							if ($item['isfolder'] == 'True' && trim($item['resourceid']) == '') {
								unset($pluginsData[$key]);
								continue;
							}
							
							$item['dataname'] = gbk2utf8($item['dataname']);
							if ($item['isfolder'] == 'True') {
								$item['dataType'] = '文件夹';
							} else if ($item['isfolder'] == 'False') {
								$item['dataType'] = '图片';
							}
						}
					}
					
					$this->assign('dataType', 'image');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0004') {  // 虚拟电子翻书
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as $key=>&$item) {
							if (trim($item['picpathid']) == '') {
								unset($pluginsData[$key]);
							} else {
								$item['dataname'] = gbk2utf8($item['dataname']);
								$item['dataType'] = '文件夹';
							}
						}
					}
					
					$this->assign('dataType', 'image');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-PhotoRing') {  // 多点互动图片框
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as $key=>&$item) {
							if (trim($item['picpathid']) == '') {
								unset($pluginsData[$key]);
							} else {
								$item['dataname'] = gbk2utf8($item['dataname']);
								$item['dataType'] = '文件夹';
							}
						}
					}
					
					$this->assign('dataType', 'image');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0006') {  // 音乐播放器
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as &$item) {
							$item['dataname'] = gbk2utf8($item['dataname']);
							$item['dataType'] = '音乐';
						}
					}
					
					$this->assign('dataType', 'music');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0007') {  // 网页浏览器
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as &$item) {
							$item['dataname'] = gbk2utf8($item['dataname']);
							$item['dataType'] = '网址URL';
							
							if (substr($item['homesite'], 0, 7) != 'http://') {
								$item['homesite'] = 'http://' . $item['homesite'];
							}
						}
					}
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0008') {  // 全屏内容弹窗
					
					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

					if ($pluginsData) {
						foreach ($pluginsData as &$item) {
							$item['dataname'] = gbk2utf8($item['dataname']);
							$resData = $mediaLibModel->where(array('resourceid'=>$item['fileid']))->getField('filepath');
							$item['dataTypeCode'] = getFileType($resData);
							switch ($item['dataTypeCode']) {
								case 'image':
									$item['dataType'] = '图片';
									break;
								case 'video':
									$item['dataType'] = '视频';
									break;
								default:
									$item['dataType'] = '文件';
							}
						}
					}
					
					$this->assign('dataType', 'all');
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Self-0001') {  // 文字滚动框
					
					$pluginsData = $pluginsDataModel->where($where)->select();
					if (count($pluginsData) && !empty($pluginsData[0])) {
						$pluginsData = $pluginsData[0];
						$pluginsData['ledstring'] = gbk2utf8($pluginsData['ledstring']);
						$pluginsData['dataname'] = gbk2utf8($pluginsData['dataname']);
					}
					
				} else if ($plugins['itemtype_classid'] == 'Widget-Box-0011') {   // 图片按钮箱

					$pluginsData = $pluginsDataModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
					if ($pluginsData) {
						foreach ($pluginsData as &$item) {
							$item['dataname'] = gbk2utf8($item['dataname']);
							$item['dataType'] = '图片';
						}
					}
					
					$this->assign('dataType', 'image');
				}
			}
// 			dump($plugins);
// 			dump($pluginsData);
			$this->assign('plugins', $plugins);
			$this->assign('pluginsData', $pluginsData);
			$this->display();
		} else {
			$this->error('非法请求！');
		}
	}
	
	/**
	 * 修改插件名称
	 */
	public function modifyPluginsNameByAjax() {
		$pluginsID = I('post.pid', 0, 'int');
		$pluginsName = I('post.pname', '', '');
		
		if ($pluginsID && $pluginsName) {
			
			// 处理插件名称：过滤特殊字符 && 验证唯一性
			$pluginsName = filterInputName($pluginsName, true, 'item', array('itemID'=>$pluginsID));
			if (!$pluginsName) {
				$this->error('已存在名称为“' . $pluginsName .'”的插件！请重新输入');
			}
			
			$pluginsModel = D('Plugins');
			$result = $pluginsModel->where(array('id'=>$pluginsID))->setField(array('name'=>utf82gbk(htmlspecialchars($pluginsName))));
			if ($result !== false) {
				echo json_encode(array('stat'=>1, 'msg'=>$pluginsName));
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $pluginsModel->getError()));
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据错误！'));
		}
	}
	
	/**
	 * 编辑管理插件数据
	 */
	public function editData() {
		$pluginsID = I('get.pid');
		$dataID = I('get.did', 0, 'int');
		
		if ($pluginsID && $dataID) {
			
			// 获取插件场景信息
			$pluginsModel = D('Plugins');
			$plugins = $pluginsModel->field(array('id', 'name','belong_scenceid','itemtype_classid'))->where(array('classid'=>$pluginsID))->find();
			if ($plugins) {
				$plugins['name'] = gbk2utf8($plugins['name']);
				
				// 获取场景级联数据，作为面包屑
				if (trim($plugins['belong_scenceid']) != '') {
					$belongScenceInfo = D('Scences')->field(array('id', 'scencename', 'parentscence_id'))->where(array('classid'=>$plugins['belong_scenceid']))->find();
					$SCrumb = array();
					if (trim($belongScenceInfo['parentscence_id']) != '') {
						$this->generateScenceCrumb($SCrumb, $belongScenceInfo['parentscence_id']);
					}
					$SCrumb[] = array('id'=>$belongScenceInfo['id'], 'scencename'=>gbk2utf8($belongScenceInfo['scencename']));
					$this->assign('crumb', $SCrumb);
				}
				
				// 实例化数据模型
				$pluginsDataModel = D($this->pluginsTypesModels[$plugins['itemtype_classid']]);
				$this->assign('dataModelName', $this->pluginsTypesModels[$plugins['itemtype_classid']]);
				
				$pluginsData = $pluginsDataModel->find($dataID);
				if ($pluginsData) {
					$this->assign('mediaPath', $plugins['belong_scenceid'] . '/' . $pluginsData['itemclassid'] . '/' . $pluginsData['dataid'] . '/');
					
					$mediaLibModel = D('MediaLib');
					$pluginsData['dataname'] = gbk2utf8($pluginsData['dataname']);
					if ($pluginsData['isfolder'] == 'True') {
						
						$pluginsData['dataType'] = '文件夹';
						
						$pluginsData['resData'] = $mediaLibModel->field(array('id,name,filepath,resourceid'))->where(array('resourceid'=>$pluginsData['resourceid']))->find();
						
						$resFiles = $fileFilter = array();
						if ($plugins['itemtype_classid'] == 'Widget-Box-0000') {  // 图片视频滑动框
							
							$fileFilter =  array_merge(C('UPIMG_ALLOW_TYPES'), C('UPVIDEO_ALLOW_TYPES'));
							$this->assign('dataType', 'imagevideo');
							
						} else if ($plugins['itemtype_classid'] == 'Widget-Box-0001') {  // 视频循环播放器
							
							$fileFilter =  C('UPVIDEO_ALLOW_TYPES');
							$this->assign('dataType', 'video');
							
						} else if ($plugins['itemtype_classid'] == 'Widget-Box-0003') {  // 图片幻灯框
							
							$fileFilter =  C('UPIMG_ALLOW_TYPES');
							$this->assign('dataType', 'image');
							
						} else if ($plugins['itemtype_classid'] == 'Widget-Box-0007') {  // 网页浏览器
						}
						
						foreach (glob(C('UPLOAD_ROOT_PATH') . $pluginsData['resData']['filepath'] . "/*") as $file) {
							$tmp = explode('.', $file);
							$ext = end($tmp);
							if (is_file($file) && in_array($ext, $fileFilter)) {
								$resFiles[] = basename(gbk2utf8($file));
							}
						}
						
						$ord = I('get.ord', 0, 'int');
						if ($ord) {
							rsort($resFiles);
						} else {
							sort($resFiles);
						}
						import('ORG.Util.Page');
						$Page = new Page(count($resFiles), 5);
						$show = $Page->show();
						$pluginsData['resFiles'] = array_slice($resFiles, $Page->firstRow, $Page->listRows);
						if($pluginsData['resFiles']) {
							$item = null;
							foreach ($pluginsData['resFiles'] as &$item) {
								$temp = $item;
								$item = array();
								$item['filepath'] = $temp;
								$item['filetype'] = getFileType($temp);
							}
						}
						$this->assign('page', $show);
						
					} else if ($pluginsData['isfolder'] == 'False') {
						
						if ($plugins['itemtype_classid'] == 'Widget-Box-0000') {  // 图片视频滑动框
							
							$pluginsData['dataType'] = '图片视频';
							$this->assign('dataType', 'imagevideo');
							
						} else if ($plugins['itemtype_classid'] == 'Widget-Box-0001') {  // 视频循环播放器
							
							$pluginsData['dataType'] = '视频';
							$this->assign('dataType', 'video');
							
						} else if ($plugins['itemtype_classid'] == 'Widget-Box-0003') {  // 图片幻灯框
							
							$pluginsData['dataType'] = '图片';
							$this->assign('dataType', 'image');
							
						}
						
						if (trim($pluginsData['resourceid']) != '') {
							$resIDs = explode(';', $pluginsData['resourceid']);
							/* $resData = array();
							foreach ($resIDs as $resID) {
								$resData[] = $mediaLibModel->field(array('id,name,filepath,resourceid'))->where(array('resourceid'=>$resID))->find();
							} */
							$fields = array('id,name,filepath,resourceid');
							$where = array('resourceid'=>array('in', $resIDs));
							
							// 数据分页
							$totals = $mediaLibModel->where($where)->count();
							import('ORG.Util.Page');
							$Page = new Page($totals, 5);
							$show = $Page->show();
							
							$resData = $mediaLibModel->field($fields)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
							if($resData) {
								$item = null;
								foreach ($resData as &$item) {
									$item['filetype'] = getFileType($item['filepath']);
									$item['filepath'] = gbk2utf8($item['filepath']);
									$item['filename'] = basename($item['filepath']);
								}
							}
							
							$pluginsData['resData'] = $resData;
							$this->assign('page', $show);
						}
					} else {
						if ($plugins['itemtype_classid'] == 'Widget-Box-0006') {  // 音乐播放器
							
							$pluginsData['dataType'] = '音乐';
							$this->assign('dataType', 'music');
							
							if (trim($pluginsData['fileid']) != '') {
								$resIDs = explode(';', $pluginsData['fileid']);
								$fields = array('id,name,filepath,resourceid');
								$where = array('resourceid'=>array('in', $resIDs));
								
								// 数据分页
								$totals = $mediaLibModel->where($where)->count();
								import('ORG.Util.Page');
								$Page = new Page($totals, 12);
								$show = $Page->show();
								
								$resData = $mediaLibModel->field($fields)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
								$pluginsData['resData'] = $resData;
								$this->assign('page', $show);
							}
							
						} elseif ($plugins['itemtype_classid'] == 'Widget-Box-0002') {  // 文本显示框
							
							$pluginsData['dataType'] = '文本';
							$this->assign('dataType', 'text');
							
							if (trim($pluginsData['fileid']) != '') {
								$resIDs = explode(';', $pluginsData['fileid']);
								$fields = array('id,name,filepath,resourceid');
								$where = array('resourceid'=>array('in', $resIDs));
								
								// 数据分页
								$totals = $mediaLibModel->where($where)->count();
								import('ORG.Util.Page');
								$Page = new Page($totals, 5);
								$show = $Page->show();
								
								$resData = $mediaLibModel->field($fields)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
								$pluginsData['resData'] = $resData;
								$this->assign('page', $show);
							}
							
						} elseif (($plugins['itemtype_classid'] == 'Widget-Box-0004') OR ($plugins['itemtype_classid'] == 'Widget-Box-PhotoRing')) {  // 虚拟电子翻书 or 多点互动图片框
							
							$pluginsData['dataType'] = '文件夹';
							$pluginsData['isfolder'] = 'True';
							$resFiles = $fileFilter = array();
							$fileFilter =  C('UPIMG_ALLOW_TYPES');
							$this->assign('dataType', 'image');
							
							$pluginsData['resData'] = $mediaLibModel->field(array('id,name,filepath,resourceid'))->where(array('resourceid'=>$pluginsData['picpathid']))->find();
							if ($pluginsData['resData']['filepath']) {
								
								foreach (glob(C('UPLOAD_ROOT_PATH') . $pluginsData['resData']['filepath'] . "/*") as $file) {
									$tmp = explode('.', $file);
									$ext = end($tmp);
									if (is_file($file) && in_array($ext, $fileFilter)) {
										$resFiles[] = basename(gbk2utf8($file));
									}
								}
								
								$ord = I('get.ord', 0, 'int');
								if ($ord) {
									rsort($resFiles);
								} else {
									sort($resFiles);
								}
								import('ORG.Util.Page');
								$Page = new Page(count($resFiles), 5);
								$show = $Page->show();
								$pluginsData['resFiles'] = array_slice($resFiles, $Page->firstRow, $Page->listRows);
								
								$this->assign('page', $show);
								
							} else {
								$this->error('非法请求');
							}
						} elseif ($plugins['itemtype_classid'] == 'Widget-Box-0008') {  // 全屏内容弹窗
							
							$pluginsData['dataType'] = '文件';
							$this->assign('dataType', 'all');
								
							if (trim($pluginsData['fileid']) != '') {
								$resIDs = explode(';', $pluginsData['fileid']);
								$fields = array('id,name,filepath,resourceid');
								$where = array('resourceid'=>array('in', $resIDs));
							
								// 数据分页
								$totals = $mediaLibModel->where($where)->count();
								import('ORG.Util.Page');
								$Page = new Page($totals, 5);
								$show = $Page->show();
							
								$resData = $mediaLibModel->field($fields)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
								foreach ($resData as &$item) {
									$item['filepath'] = gbk2utf8($item['filepath']);
									$item['filetype'] = getFileType($item['filepath']);
								}
								
								$pluginsData['resData'] = $resData;
								$this->assign('page', $show);
							}
						} else if ($plugins['itemtype_classid'] == 'Widget-Box-0011') {  // 图片按钮箱

							$pluginsData['dataType'] = '图片';
							$this->assign('dataType', 'image');

							$buttonItemsModel = D('ButtonsBoxItem');
							$buttonItems = $buttonItemsModel->where(array('belong_dataid'=>$pluginsData['dataid']))->getField('buttonitem_imgid', true);
							$where = array('resourceid'=>array('in', $buttonItems));
							
							// 数据分页
							$totals = $mediaLibModel->where($where)->count();
							import('ORG.Util.Page');
							$Page = new Page($totals, 5);
							$show = $Page->show();

							$resData = $mediaLibModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
							$pluginsData['resData'] = $resData;
							$this->assign('page', $show);
						}
					}
				}
// 				dump($plugins);
// 				dump($pluginsData);
				$this->assign('plugins', $plugins);
				$this->assign('pluginsData', $pluginsData);
				$this->display();
			} else {
				$this->error('请求错误，请刷新页面重试！');
			}
		} else {
			$this->error('请求错误，请刷新页面重试！');
		}
	}
	
	/**
	 * 删除数据
	 */
	public function delData() {
		$dataDBModel = I('post.dataDBModel');
		$dids = I('post.dataID');
		
		$didsArr = null;
		if (trim($dids, '-') != '') {
			$didsArr = explode('-',trim($dids, '-'));
			if (count($didsArr) <= 0) {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
		
		if ($dataDBModel && $didsArr) {
				
			$mediaLibModel = D('MediaLib');
			$pluginsDataModel = D($dataDBModel);
			$failed = 0;
			foreach ($didsArr as $did) {
				$resData = $mediaLibModel->find($dataResID);
				if ($resData) {
					$resFieldName = in_array($dataDBModel, array('MusicPlayer', 'FSAlert')) ? 'fileid' : 'resourceid';
					$resourceID = $pluginsDataModel->where(array('id'=>$dataID))->getField($resFieldName);
					$resourceID = trim($resourceID, ';') . ';';
					$resourceID = str_replace($resData['resourceid'] . ';', '', $resourceID);
					$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>trim($resourceID, ';')));
						
					if ($pluginsDataResult !== false) {
						//此处目前仅支持删除本地图片，远程其他待
						$imgPath = C('UPLOAD_ROOT_PATH') . $resData['filepath'];
						if (file_exists($imgPath)) {
							@unlink($imgPath);
						}
						$mediaLibModel->delete($dataResID);
					} else {
						$failed ++;
					}
				}
			}
				
			if ($failed > 0) {
				echo json_encode(array('stat'=>0, 'msg'=>$failed . '个删除失败！'));
			} else {
				echo json_encode(array('stat'=>1));
			}
				
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
	}
	
	/**
	 * 修改插件数据名称
	 */
	public function modifyPluginsDataNameByAjax() {
		$dataDBModel = I('post.dataDBModel');
		$dataID = I('post.dataID', 0, 'int');
		$dataName = I('post.pname', '', '');
	
		if ($dataDBModel && $dataID && $dataName) {
			
			// 处理数据名称：过滤特殊字符 && 验证唯一性
			$dataName = filterInputName($dataName, true, 'data', array('dataModel'=>$dataDBModel, 'dataID'=>$dataID));
			if (!$dataName) {
				$this->error('已存在名称为“' . $dataName .'”的数据！请重新输入');
			}
			
			$dataModel = D($dataDBModel);
			$result = $dataModel->where(array('id'=>$dataID))->setField(array('dataname'=>utf82gbk(htmlspecialchars($dataName))));
			if ($result !== false) {
				echo json_encode(array('stat'=>1, 'msg'=>$dataName));
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：' . $dataModel->getError()));
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'数据错误！'));
		}
	}
	
	/*
	 * 批量删除数据元素
	 */
	public function delMultiResData() {
		$dataDBModel = I('post.dataDBModel');
		$dataID = I('post.dataID', 0, 'int');
		$dataResIDs = I('post.dataResIDs');
		
		if (trim($dataResIDs, '-') != '') {
			$dataResIDsArr = explode('-',trim($dataResIDs, '-'));
			if (count($dataResIDsArr) <= 0) {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
			}
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
		
		if ($dataDBModel && $dataID && $dataResIDs) {
			
			$mediaLibModel = D('MediaLib');
			$pluginsDataModel = D($dataDBModel);
			$failed = 0;
			foreach ($dataResIDsArr as $dataResID) {
				$resData = $mediaLibModel->find($dataResID);
				if ($resData) {
					if ($dataDBModel == 'ButtonsBox') {
						$pluginsDataResult = D('ButtonsBoxItem')->where(array('buttonitem_imgid'=>$resData['resourceid']))->delete();
					} else {
						$resFieldName = in_array($dataDBModel, array('MusicPlayer', 'FSAlert')) ? 'fileid' : 'resourceid';
						$resourceID = $pluginsDataModel->where(array('id'=>$dataID))->getField($resFieldName);
						$resourceID = trim($resourceID, ';') . ';';
						$resourceID = str_replace($resData['resourceid'] . ';', '', $resourceID);
						$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>trim($resourceID, ';')));
					}
					
					if ($pluginsDataResult !== false) {
						//此处目前仅支持删除本地图片，远程其他待
						$imgPath = C('UPLOAD_ROOT_PATH') . $resData['filepath'];
						if (file_exists($imgPath)) {
							@unlink($imgPath);
						}
						$mediaLibModel->delete($dataResID);
					} else {
						$failed ++;
					}
				}
			}
			
			if ($failed > 0) {
				echo json_encode(array('stat'=>0, 'msg'=>$failed . '个删除失败！'));
			} else {
				echo json_encode(array('stat'=>1));
			}
			
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
	}
	/*
	 * 单个删除数据元素
	 */
	public function delResData() {
		$dataDBModel = I('post.dataDBModel');
		$dataID = I('post.dataID', 0, 'int');
		$dataResID = I('post.dataResID', 0, 'int');
		
		if ($dataDBModel && $dataID && $dataResID) {
			
			$mediaLibModel = D('MediaLib');
			$resData = $mediaLibModel->find($dataResID);
			if ($resData) {
				$pluginsDataModel = D($dataDBModel);
				if ($dataDBModel == 'ButtonsBox') {
					$pluginsDataResult = D('ButtonsBoxItem')->where(array('buttonitem_imgid'=>$resData['resourceid']))->delete();
				} else {

					$resFieldName = in_array($dataDBModel, array('MusicPlayer', 'FSAlert', 'TxtRtf')) ? 'fileid' : 'resourceid';
					$resourceID = $pluginsDataModel->where(array('id'=>$dataID))->getField($resFieldName);
					$resourceID = trim($resourceID, ';') . ';';
					$resourceID = str_replace($resData['resourceid'] . ';', '', $resourceID);
					$pluginsDataResult = $pluginsDataModel->where(array('id'=>$dataID))->save(array($resFieldName=>trim($resourceID, ';')));
				
				}
				if ($pluginsDataResult !== false) {
					//此处目前仅支持删除本地图片，远程其他待
					$imgPath = C('UPLOAD_ROOT_PATH') . $resData['filepath'];
					if (file_exists($imgPath)) {
						@unlink($imgPath);
					}
					$mediaLibModel->delete($dataResID);
					echo json_encode(array('stat'=>1));
				} else {
					echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据库更新失败！'));
				}
								
			} else {
				echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
			}
			
		} else {
			echo json_encode(array('stat'=>0, 'msg'=>'操作失败！[原因]：数据请求错误，请刷新页面重试！'));
		}
	}
	
	public function test() {
		// $this->error('test only....', '', 5000);
		// $this->display();
		header('Content-Type:text/html; charset=utf-8');
		
		
		
		/* $img = 'jolink/docs/medialib/36a7b192d5bbc31442e5e9072e4b6dc9.jpg';
		import('@.ORG.Image');
		$imgInfo = Image::getImageInfo($img);
		dump($imgInfo); */
		/*
		$img = 'e3b4e21a-27c4-4c90-839b-e8fc1ccf6724.jpg';
		$thumbName = C('UPLOAD_ROOT_PATH') . 'thumb_' . $img;
		dump('文件路径：' . $orgImage);
		if (!file_exists($orgImage)) {
			dump('文件不存在！');
		}
		dump($imgInfo['width'] . ' x ' . $imgInfo['height']);
		
		if ($imgInfo['width'] > C('THUMB_MAX_WIDTH') || $imgInfo['height'] > C('THUMB_MAX_HEIGHT')) {
			dump(Image::thumb($orgImage, $thumbName, '', C('THUMB_MAX_WIDTH'), C('THUMB_MAX_HEIGHT')));
		}
		*/
	}
	
	private function generateScenceCrumb(&$SCrumb, $psId) {
		$scenceModel = D('Scences');
		$scenceInfo = $scenceModel->field(array('id', 'scencename', 'parentscence_id'))->where(array('classid'=>$psId))->find();
		
		if ($scenceInfo) {
			
			
			if (trim($scenceInfo['parentscence_id']) != '') {
				$this->generateScenceCrumb($SCrumb, $scenceInfo['parentscence_id']);
			}
			
			$SCrumb[] = array('id'=>$scenceInfo['id'], 'scencename'=>gbk2utf8($scenceInfo['scencename']));
		}
	}
}