<?php
/**
 * 广告管理控制器
 * @author Skeam Tj
 *
 */
class AdsAction extends CommonAction {
	
	public function index() {

		$adsModel = D('Ads');
		$mediaLibModel = D('MediaLib');
		$ads = $adsModel->find();
		if ($ads && trim($ads['dir_resourceid'])) {
		
			$ads['filepath'] = $mediaLibModel->where(array('resourceid'=>$ads['dir_resourceid']))->getField('filepath');
		
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
		
			} 
		
		} else {
			$ads['dir_resourceid'] = generateUniqueID();
			$ads['filepath'] = generateUniqueID();
			
			$adsUpRe = $adsModel->where(array('id'=>$ads['id']))->save(array('dir_resourceid'=>$ads['dir_resourceid']));
			if ($adsUpRe !== false) {
				$mediaLibInsRe = $mediaLibModel->add(array('resourceid'=>$ads['dir_resourceid'], 'filepath'=>$ads['filepath']));
				if (!$mediaLibInsRe) {
					$this->error('数据错误！');
				}
			} else {
				$this->error('数据错误！');
			}
		}
		
		$this->assign('ads', $ads);
		$this->display();
	}
	
}