<?php
/**
 * 场景控制器
 * @author Skeam TJ
 *
 *	删除旧的背景图片 ？？
 */
class ScencesAction extends CommonAction {
	
	public function index() {
		
		if (IS_POST) {
			$this->saveData();
		} else {
			$sid = I('get.sid', 0, 'int');
			if ($sid) {
				
				// 获取场景数据
				$scencesModel = D('Scences');
				$scence = $scencesModel->field(array('id,classid,scencename,parentscence_id,ishomescence'))->where(array('id'=>$sid))->find();
				if ($scence) {
					$scence['scencename'] = gbk2utf8($scence['scencename']);
					$scence['short_scencename'] = (mb_strlen($scence['scencename'], 'UTF-8') > 14) ? mb_substr($scence['scencename'], 0, 14, 'UTF-8') . '....' : $scence['scencename'];
					$scencesDataModel = D('ScencesData');
					$scenceBgID = $scencesDataModel->where(array('scenceclassid'=>$scence['classid']))->getField('scencebgid');
					if ($scenceBgID) {
						$mediaLibModel = D('MediaLib');
						$scence['bgInfo'] = $mediaLibModel->field(array('id,filepath'))->where(array('resourceid'=>$scenceBgID))->find();
						//$uniName = substr($scence['bgInfo']['filepath'], 0, strripos($scence['bgInfo']['filepath'], '.'));
					}
					
					// 获取场景级联数据，作为面包屑
					$SCrumb = array();
					if (trim($scence['parentscence_id']) != '') {
						$this->generateScenceCrumb($SCrumb, $scence['parentscence_id']);
					}
					$this->assign('crumb', $SCrumb);
					
				}
				
				$uniName = generateUniqueID();
				$this->assign('scence', $scence);
				$this->assign('uniName', $uniName);
				$this->display();
			} else {
				$this->error('非法请求！');
			}
		}
	}
	
	private function saveData() {
		if (IS_POST) {
			$sid = I('post.sid');
			$sbgid = I('post.sbgid', 0, 'int');
			$scenceName = I('post.scencename', '', '');
			$sbgPath = I('post.cover_image');
			
			if ($sid && $scenceName) {
				
				$scencesModel = D('Scences');
				$scence = $scencesModel->where(array('classid'=>$sid))->find();
				if (!$scence) {
					$this->error('非法请求！');
				}
				
				// 处理场景名称：过滤特殊字符 && 验证唯一性
				if ($scence['ishomescence'] == 'False') {
					$scenceName = filterInputName($scenceName, true, 'scence', array('scenID'=>$scence['id'], 'PID'=>$scence['parentscence_id']));
					if (!$scence) {
						$this->error('已存在名称为“' . $scenceName .'”的场景！请重新输入');
					}
				} else {
					$scenceName = filterInputName($scenceName, false, 'scence', array('scenID'=>$scence['id']));
				}
				
				$upScence = $scencesModel->where(array('classid'=>$sid))->save(array('scencename'=>utf82gbk(htmlspecialchars($scenceName))));
				if ($upScence !== false) {
					
					$mediaLibModel = D('MediaLib');
					$mediaLibResult = false;
					if ($sbgid && $sbgPath) {
						$mediaLibResult = $mediaLibModel->where(array('id'=>$sbgid))->save(array('filepath'=>$sbgPath));
						if ($mediaLibResult === false) {
							$this->error('场景名称修改成功，但背景图片保存失败！[原因]：' . $mediaLibModel->getError());
						}
					} else if (!$sbgid && $sbgPath) {
						$resourceID = generateUniqueID();
						$mediaLibResult = $mediaLibModel->add(array('filepath'=>$sbgPath, 'resourceid'=>$resourceID));
						if ($mediaLibResult !== false) {
							$scenceDataResult = D('ScencesData')->where(array('scenceclassid'=>$sid))->save(array('scencebgid'=>$resourceID));
							if ($scenceDataResult === false) {
								$this->error('场景名称修改成功，但背景图片保存失败！[原因]：' . $mediaLibModel->getError());
							}
						} else {
							$this->error('场景名称修改成功，但背景图片保存失败！[原因]：' . $mediaLibModel->getError());
						}
					}
					
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！[原因]：'.$scencesModel->getError());
				}
			} else {
				$this->error('非法请求！');		
			}
		} else {
			$this->error('非法请求！');		
		}
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
	
	public function emptyCon() {
		$this->display();
	}
}