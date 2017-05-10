<?php
/**
 * 广告管理控制器
 * @author Skeam Tj
 *
 */
class AdsAction extends CommonAction {
	
	public function index() {

		$adGroups = D('Ads')->getField('touchendpoint_groupclassid', true);
		$where['groupclassid'] = array('in', $adGroups);

		// 数据分页
		$epgroupModel = D('EPGroup');
		import('ORG.Util.Page');
		$totals = $epgroupModel->where($where)->count();
		$Page = new Page($totals, 12);
		$show = $Page->show();

		$epgroups = $epgroupModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		if ($epgroups) {
			foreach ($epgroups as &$gro) {
				$gro['groupname'] = gbk2utf8($gro['groupname']);
			}
		}
		$this->assign('epgroups', $epgroups);
		$this->assign('page', $show);
		$this->display();
	}

	public function preview() {
		$this->assign('actname', 'preview');
		$this->assign('isWrite', 0);
		$this->adsData();
	}

	public function edit() {
		$this->assign('actname', 'edit');
		$this->assign('isWrite', 1);
		$this->adsData();
	}

	private function adsData() {
		$gid = I('get.gid');

		if (!empty($gid)) {

			$epgroup = D('EPGroup')->where(array('groupclassid'=>$gid))->find();
			if ($epgroup) {
				$epgroup['groupname'] = gbk2utf8($epgroup['groupname']);
				$this->assign('epgroup', $epgroup);
			} else {
				$this->error('非法操作！');
			}

			$ads = D('Ads')->where(array('touchendpoint_groupclassid'=>$gid))->find();
			if ($ads && trim($ads['dir_resourceid'])) {
				
				$ads['filepath'] = D('MediaLib')->where(array('resourceid'=>$ads['dir_resourceid']))->getField('filepath');
				
				if (trim($ads['filepath']) != '' && file_exists(C('UPLOAD_ROOT_PATH') . $ads['filepath'])) {

					$fileFilter =  C('UPIMG_ALLOW_TYPES');
					foreach (glob(C('UPLOAD_ROOT_PATH') . $ads['filepath'] . "/*") as $file) {
						$tmp = explode('.', $file);
						$ext = end($tmp);
						if (is_file($file) && in_array($ext, $fileFilter)) {
							$resFiles[] = basename(gbk2utf8($file));
						}
					}
					
					// 排序
					$ord = I('get.ord', 0, 'int');
					if ($ord) {
						rsort($resFiles);
					} else {
						sort($resFiles);
					}

					// 分页
					import('ORG.Util.Page');
					$Page = new Page(count($resFiles), 5);
					$show = $Page->show();
					$adsFiles = array_slice($resFiles, $Page->firstRow, $Page->listRows);
					$this->assign('adsFiles', $adsFiles);
					$this->assign('page', $show);

				} else {
					$this->error('非法操作！');
				}

				$this->assign('ads', $ads);
			} else {
				$this->error('非法操作！');
			}

		} else {
			$this->error('非法操作！');
		}

		$this->display('edit');
	}
}