<?php
class TemplateManageAction extends CommonAction {
    /**
     * 模板管理页面的展示:TB_Tpls表和TB_TEndpoint_Type表
     */
    public function template_index() {    		
        $tType = $_GET[tType]?0:'x86';
        $tpls = M('tpls')->where("tpltype='$tType'")->order('id')->select();
        $etypes = M('TendpointType')->select();
        $programs = M('programs')->select();
        foreach ($tpls as $key => &$value) {
            foreach ($programs as $k => $v) {
                if(trim($value[binding_program_classid]) == trim($v[program_classid])){
                    $value[program_name] = $v[program_name];
                }
                if(!$value[binding_program_classid]){
                    $value[program_name] = '';
                }
            }
        }
        foreach ($tpls as $key1 => $value1) {
            foreach($etypes as $k1 => &$v1){
                if(trim($value1[tpltype]) == trim($v1[typecode])){
                    $v1[tpl][] = $value1;
                }
            }
        }
        $this->rows = $etypes;
		$this->display("index");
    }	



    /**
     * x86,安卓广告机,安卓触摸机
    */

    public function showEach(){
		$tType = I("request.tType","","trim");//var_dump($tType);
		$this->assign("tType",$tType);
		
        // $this->rows = D('TemplateView')->select();
        $this->rows = D('TemplateView')->where("typecode='".$tType."'")->select();
        $this->display("showEach");
    }
    
	
	
	// 场景 start ****************************************
	
    /**
     * 场景页面的展示。通过表tpls中的tplclassid字段关联到TB_Scences表中的tplclassid字段
     * （该字段对应的记录通常具有多条，并且这多条记录定会存在父子关系，场景页面即是将该父子关系展示出来。）
     * 展示scencename字段。（scences，注意，这是一个奇怪的表名，即不是英文场景的意思也不是拼音，在此大胆猜测原作者可能是想要表达英文scene场景的意思）。
     */
    public function scene_index() {
        $tplclassid = $_GET['tplclassid'];
        $tpls = M('scences')->where("tplclassid='".$tplclassid."'")->order('playorder')->select();
        $data = $this->getSerialize($tpls);
        $this->rows = $data;
        $this->display("scene_index");
    }   

    // 采用递归函数对数据序列化,在结构上体现父子关系
    public function getSerialize($data, $pid='', $parentfieldname='parentscence_id', $fieldname='classid'){
        foreach ($data as $key => $value) {
            if($value[$parentfieldname]==$pid){
                $arr[$key] = $value;
                $arr[$key][children] = $this->getSerialize($data, $value[$fieldname]);
            }
        }
        return $arr;
    }

    // 紧急场景的设置，采用了ajax技术实现。点击即可修改。设计需求:只能有一个紧急场景。设置了新的之后，之前的自动取消。
    public function setExigent(){
        if(IS_POST){
            $id = $_POST[id];
            $oldId = $_POST[oldId];
            $model = M('scences');
            $oldOne = $model->find($oldId);
            $isCancel = $_POST[isCancel];
            if($oldOne){
                $oldOne[isExigentScene] = 0;
                $resultOld = $model->save($oldOne);
            }

            if($isCancel){
                if($resultOld){
                    echo '取消成功！';
                }else{
                    echo 0;
                }
            }else{
                $changeOne = $model->find($id);
                $changeOne[isExigentScene] = 1;
                $result = $model->save($changeOne);
                if($result){
                    echo '修改成功';
                }else{
                    echo 0;
                }
            }
        }
    }

    /**
     * 设置播放顺序以及播放时长。表单验证：
     * 播放顺序，必须为数字，范围>0,0优先。(以启用拖动修改的方式)
     * 播放时长，必须为数字，范围>0。
     */
    // 对应拖动修改playorder的方式
    public function ajax_setPlayorder(){
        if(IS_POST){
            $r = 0;
            $model = D('Scences');
            $obj_list = $_POST[result];
            foreach($obj_list as $value){
                if(!$obj=$model->create($value)){
                    echo $model->getError();
                    return;
                }else{
                    $model->save($obj);
                    $r++;
                }
            }
            echo $r;
        }
    }
	
    // 对应双击修改playorder数值的方式
    public function ajax_changePlayorder(){
        if(IS_POST){
            $model = D('Scences');
            $tplclassid = $_POST[tplclassid];
            $max = $model->where("tplclassid='".$tplclassid."'")->count();
            $origin_order_val = $_POST[origin_order_val] > $max ? $max-1 : $_POST[origin_order_val];
            $playorder = $_POST[playorder] < $max ? $_POST[playorder] : $max-1;
            $rows_all = $model->where("tplclassid='".$tplclassid."'")->getField('id, playorder');
            $temp_arr = array();
            if($origin_order_val < $playorder){
                for ($i=$origin_order_val; $i <= $playorder; $i++) { 
                    $data = array();
                    foreach($rows_all as $key => $value){
                        if($value == $i){
                            $data[id] = $key;
                            $data[playorder] = --$value;
                            break;
                        }
                    }
                    array_push($temp_arr, $data);
                }
                $temp_arr[0][playorder] = $playorder;
            }else{
                for ($i=$playorder; $i <= $origin_order_val; $i++) { 
                    $data = array();
                    foreach($rows_all as $key => $value){
                        if($value == $i){
                            $data[id] = $key;
                            $data[playorder] = ++$value;
                            break;
                        }
                    }
                    array_push($temp_arr, $data);
                }
                $temp_arr[count($temp_arr)-1][playorder] = $playorder;
            }
            foreach ($temp_arr as $value) {
                if(!$obj = $model->create($value)){
                    echo $model->getError();
                    return;
                }else{
                    $model->save($obj);
                    $r++;
                }
            }
            echo $r-1;
        }
    }

    public function ajax_setPlaytime(){
        if(IS_POST){
            $model = D('Scences');
            if(!$obj=$model->create()){
                echo $model->getError();
                return;
            }else{
                $obj[playtime] = $obj[playtime] * 60;
                $model->save($obj);
                echo '1';
            }
        }
    }

    // 针对安卓广告机单独起一个模板和操作
    public function index_azad() {
        $tplclassid = $_GET['tplclassid'];

        import('ORG.Util.Page');
        $count = M('scences')->where("tplclassid='".$tplclassid."'")->count();
        $numPerPage = $_GET[numPerPage]?:50;
        $page = new Page($count, $numPerPage);
        $this->show = $page->show();
        $this->totalNum = $count;
        $this->lastPage = ceil($count/$numPerPage);

        $this->rows = M('scences')->where("tplclassid='".$tplclassid."'")->order('playorder')->limit($page->firstRow, $numPerPage)->select();
        $this->display();
    }   

    // 以自然索引顺序jQuery.each中的i索引为顺序，彻底初始化playorder字段
    public function ajax_initPlayOrder(){
        $init_arr = $_POST[after_repeat_list];
        $model = M('scences');
        foreach ($init_arr as $value) {
            if(!$obj = $model->create($value)){
                echo $model->getError();
                return;
            }else{
                $model->save($obj);
            }
        }
        echo 1;
    }
	
	/**
	 * author:zjh
	 * 递归查询显示scene
	 * 
	*/
	public function scene_list(){
		header("Content-type: text/html; charset=utf-8");
		
		$tType = I("request.tpltype","","trim");//var_dump($tType);
		$this->assign("tType",$tType);		
		
		$datas_origin = array();
		$tplclassid = I("request.tplclassid","","trim");
		
		$tplsModel = D("Tpls");//模板
		$sceneModel = D('Scences');//场景
		
		if (!empty($tplclassid)){
			$tpl = 	$tplsModel->where("tplclassid='".$tplclassid."'")->find();
			if ($tpl !== false){
				$this->assign("tpl",$tpl);
			}
		}else{
			$this->error("参数为空");	
		}	

		$datas = array();
		$map = array();
		$map['tplclassid'] = $tplclassid;
		//$map['ishomescence'] = 'True';
		$datas_origin = $sceneModel->where($map)->select();//搜索场景全部记录，注意这儿必须是全部（当然，如果能在搜索时得到当前及全部下级亦可）

		//挑出需要的部分
		foreach ($datas_origin as $k=>$v){
			if ($v['tplclassid'] == $tplclassid){
				//var_dump($v);
				//$datas = $v;
				
			}
		}
		
		$sceneModel->sortedTypes($datas/*空数组，存放最终排序结果*/, $datas_origin/*待排序的原始数组*/);


		//首页场景
		$map = array();
		$map['tplclassid'] = $tplclassid;
		$map['ishomescence'] = 'True';
		$datas_home = $sceneModel->where($map)->find();//var_dump($datas_home);

		$this->assign("datas_home",$datas_home);

		$this->assign("datas",$datas);
		$this->display("scene_list");
	
		
	}
	
	/*
	$originTypes = $banjiClassModel->order('sort asc,pid asc, id asc')->select();
	$datas = array();//排序后的结果，也就是最终要用的
	$banjiClassModel->sortedTypes($datas, $originTypes);	
	
	public function sortedTypes(&$newList, $dataList, $parentID = 0, $level = 1) {
		foreach ($dataList as &$item) {
			if ($item['parentscence_id'] == $parentscence_id) {
				if ($parentID) {
					if ($newList[$parentscence_id]) {
						$newList[$parentscence_id]['has_children'] = 1;
						$item['path'] = $newList[$parentscence_id]['path'] . '_' . $item['ID'];
					} else {
						$item['path'] = $parentscence_id . '_' . $item['ID'];
					}
				} else {
					$item['path'] = $item['ID'];
				}
				$item['level'] = $level;
				$item['space'] = str_repeat('&nbsp;&nbsp;', ($level-1)*4);
				$newList[$item['ID']] = $item;
				$this->sortedTypes($newList, $dataList, $item['ID'], $level+1);
			}
		}
	}
	*/
	
	
	
	// 场景 end ****************************************
	
	
}