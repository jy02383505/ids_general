<?php
/**
 * 公共虚拟模型
 * @author zjh
 * 返回一些常用的设置（未入库的）
 * $publicVisualModel = D("PublicVisual");
 */
class PublicVisualModel extends Model {
	protected $autoCheckFields = false;
	
	/**
	 * 支持的分辨率
	 * 返回格式：数组
	*/
	public function getAllResolution(){
		//横屏
		$arr1 = array("1920*1080","1440*900", "1366*768", "1024*768","800*600");
		
		//纵屏
		$arr2 = array( "1080*1920", "900*1440", "768*1366", "768*1024", "600*800");
		
		//合并横屏和纵屏的分辨率，并返回
		$arrAll = array_merge($arr1,$arr2);
		return $arrAll;
	}
}