<?php
/**
 * 媒体库模型
 * @author Skeam TJ
 *
 */
class MediaLibModel extends Model {
	protected $trueTableName = 'TB_MediaLib';
	
	/**
	 * 根据条件参数删除媒体资源（同时删除数据库记录和物理文件）
	 * @param array $where
	 * @return number
	 */
	public function deleteMediaByParams($where = array()) {
		
		$resList = $this->where($where)->field('filepath')->select();
		foreach ($resList as $res) {
			if (trim($res['filepath']) != '') {
				@!unlink(rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . str_replace('\\', '/', $res['filepath']));
			}
		}
		
		$re = $this->where($where)->delete();
		return $re !== false ? 1 : 0;
	}
}