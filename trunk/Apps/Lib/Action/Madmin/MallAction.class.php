<?php
class MallAction extends CommonAction {
    
    /**
     * 商铺分类管理
     */
    public function storeTypes() {
        
        // 层级关系格式化分类数据
        $storeTypeModel = D('SCStoretype');
        $originTypes = $storeTypeModel->order('Pid asc, ID asc')->select();
        $sortedTypes = array();
        $storeTypeModel->sortedTypes($sortedTypes, $originTypes);
        //dump($sortedTypes);
        $this->assign('storeTypes', $sortedTypes);
        $this->display();
    }
    
    /**
     * 添加商铺分类
     */
    public function addStoreType() {
        if (IS_POST) {
            $this->saveStoreTypeData();
        } else {
            
            // 获取父级分类数据
            $storeTypeModel = D('SCStoretype');
            $originTypes = $storeTypeModel->order('Pid asc, ID asc')->select();
            $sortedTypes = array();
            $storeTypeModel->sortedTypes($sortedTypes, $originTypes);
            $this->assign('storeTypes', $sortedTypes);
            
            $this->display('EditStoreType');
        }
    }
    
    /**
     * 修改商铺分类信息
     */
    public function editStoreType() {
        if (IS_POST) {
            $this->saveStoreTypeData();
        } else {
            
            $storeTypeID = I('get.tid', 0, 'int');
            if (!$storeTypeID) {
                $this->error('非法操作！');
            }
            $storeTypeModel = D('SCStoretype');
            $storeTypeInfo = $storeTypeModel->where(array('ID'=>$storeTypeID))->find();
            if (!$storeTypeInfo) {
                $this->error('非法操作！');
            }
            
            $this->assign('storeType', $storeTypeInfo);
            // 获取父级分类数据
            $storeTypeModel = D('SCStoretype');
            $originTypes = $storeTypeModel->order('Pid asc, ID asc')->select();
            $sortedTypes = array();
            $storeTypeModel->sortedTypes($sortedTypes, $originTypes);
            $this->assign('storeTypes', $sortedTypes);
            
            $this->display('EditStoreType');
        }
    }
    
    private function saveStoreTypeData() {

       // 处理表单提交参数
       $parentTypeID = I('post.ptype_id', 0, 'int');
       $typeName = I('post.tName');
       $typeID = I('post.storetype_id', 0, 'int');
       
       // 实例化分类模型
       $storeTypeModel = D('SCStoretype');
       $data['tName'] = $typeName;
       $data['Pid'] = $parentTypeID;
       $funcName = '';
       if ($typeID) {
           $data['ID'] = $typeID;
           $funcName = 'save';
       } else {
           $funcName = 'add';
       }
       
       // 执行操作
       $storeTypeResult = $storeTypeModel->$funcName($data);
       if ($storeTypeResult !== FALSE) {
           $this->success('操作成功！', U('Mall/storeTypes'));
       } else {
           $this->error('操作失败！[原因]：' . $storeTypeModel->getError());
       }
    }
    
    /**
     * 删除商铺分类
     */
    public function delStoreType() {
        $storeTypeID = I('get.tid', 0, 'int');
        if (!$storeTypeID) {
            $this->error('非法操作！');
        }
        
        $storeTypeModel = D('SCStoretype');
        $storeTypeInfo = $storeTypeModel->where(array('ID'=>$storeTypeID))->find();
        if (!$storeTypeInfo) {
            $this->error('非法操作！');
        }
        
        // 包含子分类的父级分类不能删除
        $childrenType = $storeTypeModel->where(array('Pid'=>$storeTypeID))->find();
        $childrenStore = D('SCStore')->where(array('type'=>$storeTypeInfo['tName']))->find();
        if ($childrenType || $childrenStore) {
            $this->error('该分类非空，不允许删除！');
        }
        
        $deleteStoretypeResult = $storeTypeModel->where(array('ID'=>$storeTypeID))->delete();
        if ($deleteStoretypeResult !== false) {
           $this->success('操作成功！', U('Mall/storeTypes'));
        } else {
           $this->error('操作失败！[原因]：' . $storeTypeModel->getError());
        }
    }
    
    /**
     * 商铺列表
     */
    public function stores() {
        
        $storeModel = D('SCStore');
        $where = array();
         
        // 构建搜索条件
        $storeName = I('get.sname');
        $spotType = I('get.sc', 0, 'int');
        $floor = I('get.floor', 0, 'int');
        $typeID = I('get.type_id', 0, 'int');
        $where['spottype'] = array($spotType ? 'eq' : 'neq', 'shop');
        !empty($storeName) && $where['sname'] = array('like', '%' . $storeName . '%');
        !empty($floor) && $where['floor'] = $floor;
        $storeTypeModel = D('SCStoretype');
        if (!empty($typeID)) {
            $inIds = $storeTypeModel->getChildrenTypes($typeID, true);
            $where['type_id'] = array('in', $inIds);
        }
         
        // 加载数据分页类
        import('ORG.Util.Page');
        
        // 数据分页
        $totals = $storeModel->where($where)->count();
        $Page = new Page($totals, 12);
        $show = $Page->show();
        $this->assign('page', $show);
        
        $stores = $storeModel->where($where)->limit($Page->firstRow. ',' .$Page->listRows)->select();
        $this->assign("stores", $stores);
        
        // 获取分类数据
        $originTypes = $storeTypeModel->order('Pid asc, ID asc')->select();
        $sortedTypes = array();
        $storeTypeModel->sortedTypes($sortedTypes, $originTypes);
        $this->assign('storeTypes', $sortedTypes);
        
        // 获取商场相关配置选项
        $mallSyscfgModel = D('SCSyscfg');
        $mallFloors = $mallSyscfgModel->where(array('id'=>1))->getField('floors');
        $this->assign('mallFloors', $mallFloors);
        
        $this->display();
    }
    
    /**
     * 添加商铺
     */
    public function addStore() {
        
        if (IS_POST) {
            $this->saveStoreData();
        } else {
        
            // 获取分类数据
            $storeTypeModel = D('SCStoretype');
            $originTypes = $storeTypeModel->order('Pid asc, ID asc')->select();
            $sortedTypes = array();
            $storeTypeModel->sortedTypes($sortedTypes, $originTypes);
            $this->assign('storeTypes', $sortedTypes);
            
            // 获取商场相关配置选项
            $mallSyscfgModel = D('SCSyscfg');
            $mallFloors = $mallSyscfgModel->where(array('id'=>1))->getField('floors');
            $this->assign('mallFloors', $mallFloors);
            
            $this->assign('unid', generateUniqueID());
            
            $templateFile = '';
            switch ($_GET['spottype']) {
                case 'service':
                    $templateFile = 'editPubService';
                    break;
                case 'tep':
                    $templateFile = 'editTepPosition';
                    $tends = D('Endpoint')->field('touchMainId, touchEndPointName')->order('touchMainId asc')->select();
                    foreach ($tends as &$tep) {
                        if (empty($tep['touchEndPointName'])) {
                            $tep['touchEndPointName'] = ''; 
                        }
                    }
                    $this->assign('tends', $tends);
                    break;
                default:
                    $templateFile = 'editStore';
            } 
            $this->display($templateFile);
        }
        
    }
    
    /**
     * 修改商铺信息
     */
    public function editStore() {
        
        if (IS_POST) {
            $this->saveStoreData();
        } else {
            
            $storeID = I('get.id', 0, 'int');
            if (!$storeID) {
                $this->error('非法操作！');
            }
            
            $storeModel = D('SCStore');
            $storeInfo = $storeModel->where(array('Id'=>$storeID))->find();
            if (!$storeInfo) {
                $this->error('非法操作！');
            }
            $medias = D('MediaLib')->where(array('resourceid'=>array('in', array($storeInfo['logo'], $storeInfo['hotico'], $storeInfo['store_video']))))->getField('resourceid, filepath');
            if (!empty($medias)) {
                $storeInfo['note'] = stripslashes($storeInfo['note']);
                $storeInfo['logoPath'] = str_replace('\\', '/', $medias[$storeInfo['logo']]);
                $storeInfo['hoticoPath'] = str_replace('\\', '/', $medias[$storeInfo['hotico']]);
                $storeInfo['storeVideoPath'] = basename(str_replace('\\', '/', $medias[$storeInfo['store_video']]));
            }
            //dump($storeInfo);
            // 获取分类数据
            $storeTypeModel = D('SCStoretype');
            $originTypes = $storeTypeModel->order('Pid asc, ID asc')->select();
            $sortedTypes = array();
            $storeTypeModel->sortedTypes($sortedTypes, $originTypes);
            $this->assign('storeTypes', $sortedTypes);
            
            // 获取商场相关配置选项
            $mallSyscfgModel = D('SCSyscfg');
            $mallFloors = $mallSyscfgModel->where(array('id'=>1))->getField('floors');
            $this->assign('mallFloors', $mallFloors);
            
            $this->assign("store", $storeInfo);            
            $this->assign('unid', $storeInfo['classid']);
            $templateFile = '';
            switch ($_GET['spottype']) {
                case 'service':
                    $templateFile = 'editPubService';
                    break;
                case 'tep':
                    $templateFile = 'editTepPosition';
                    $tends = D('Endpoint')->field('touchMainId, touchEndPointName')->order('touchMainId asc')->select();
                    $this->assign('tends', $tends);
                    break;
                default:
                    $templateFile = 'editStore';
            } 
            $this->display($templateFile);
        }
    }
    
    private function saveStoreData() {
        
        // 处理表单提交参数
        $storeID = I('post.store_id', 0, 'int');
        $typeID = I('post.type_id', 0, 'int');
        $storeName = I('post.sname');
        $pinyinShort = I('post.pyshort');
        $logoResId = I('post.logo');
        $floor = I('post.floor');
        $address = I('post.adress');
        $note = trim($_POST['note']);
        $hoticoResId = I('post.hotico');
        $videoResId = I('post.store_video');
        $classid = I('post.classid');
        $storeNo = I('post.store_no');
        $storeShortname = I('post.short_sname');
        $storeManager = I('post.store_manager');
        $storeContact = I('post.contact');
        $hasLogoAsCover = I('post.has_logo_as_cover', 0, 'int');
        $spotType = I('post.spottype', 'shop');
        
        if(empty($typeID)) {
            $this->error('操作失败！[原因]：未指定商铺分类！');
        }
        
        if (empty($storeName)) {
            $this->error('操作失败！[原因]：商铺名称不能为空！');
        }
        
        // 实例化商铺模型
        $storeModel = D('SCStore');
        $data['sname'] = $storeName;
        $data['logo'] = $logoResId;
        $data['adress'] = $address;
        $data['type_id'] = $typeID;
        $data['type'] = D('SCStoretype')->where(array('ID'=>$typeID))->getField('tName');
        $data['note'] = $note;
        $data['floor'] = $floor;
        $data['pyshort'] = $pinyinShort;
        $data['hotico'] = $hoticoResId;
        $data['store_no'] = $storeNo;
        $data['short_sname'] = $storeShortname;
        $data['store_manager'] = $storeManager;
        $data['contact'] = $storeContact;
        $data['store_cover'] = $hasLogoAsCover ? $logoResId : '';
        $data['store_video'] = $videoResId;
        $data['spottype'] = $spotType;

        // 执行操作
        $storeResult = null;
        if ($storeID) {
            $oldStoreResInfo = $storeModel->where(array('Id'=>$storeID))->find();
            $storeResult = $storeModel->where(array('Id'=>$storeID))->save($data);            
        } else {
            $data['classid'] = $classid;
            $storeResult = $storeModel->add($data);            
        }
        
        if ($storeResult !== false) {
            
            // 处理上传产生的冗余图片
            $tmpModel = M('ProgramsArticlesTemp');
            $mediaLibModel = D('MediaLib');
            $tmpIds = $tmpModel->where(array('tmp_article_classid'=>$classid, 'act'=>array('in', array('logo', 'hotico', 'video'))))->getField('res_id', true);
            if (count($tmpIds) > 0) {
                $oldIcoIds = $mediaLibModel->where(array('resourceid'=>array('in', array($oldStoreResInfo['logo'], $oldStoreResInfo['hotico'], $oldStoreResInfo['store_video']))))->getField('id', true);
                if ($oldIcoIds > 0) {
                    $tmpIds = array_unique(array_merge($tmpIds, $oldIcoIds));
                }
                $mediaLibModel->deleteMediaByParams(array('id'=>array('in', $tmpIds), 'resourceid'=>array('not in', array($logoResId, $hoticoResId, $videoResId))));
            }
            $tmpModel->where(array('tmp_article_classid'=>$classid))->delete();
            
           $this->success('操作成功！', U('Mall/stores', array('sc'=>$spotType == 'shop' ? 1 : 0)));
        } else {
           $this->error('操作失败！[原因]：' . $storeModel->getDBError());
        }
    }
    
    /**
     * 删除商铺
     */
    public function delStore() {
        $storeID = I('get.id', 0, 'int');
        if (!$storeID) {
            $this->error('非法操作！');
        }
        
        $storeModel = D('SCStore');
        $storeInfo = $storeModel->where(array('Id'=>$storeID))->find();
        if (!$storeInfo) {
            $this->error('非法操作！');
        }
        $this->assign('store', $storeInfo);
        
        // 不允许删除的条件一：关联了活动不能删除
        $hasActivities = D('SCActivities')->where(array('storeid'=>$storeID))->count();
        if ($hasActivities > 0) {
            $this->error('操作失败！[原因]：该商铺已关联了活动内容，不允许删除！');
        }
        
        // 不允许删除的条件一：关联了节目栏目不能删除
        if (!empty($storeInfo['program_dir_classid']) && M('ProgramsDirs')->where(array('classid'=>$storeInfo['program_dir_classid']))->find()) {
            $this->error('操作失败！[原因]：该商铺已关联到节目栏目，不允许删除！');
        }
        
        // 如果删除成功了，需要执行的垃圾处理操作：删除该商铺相关的媒体资源（主要指图片）
        $deleteResult = $storeModel->where(array('Id'=>$storeID))->delete();
        if ($deleteResult !== false) {
            
            $resourceIds = array();
            if (!empty($storeInfo['logo'])) {
                array_push($resourceIds, $storeInfo['logo']);
            }
            
            if (!empty($storeInfo['hotico'])) {
                array_push($resourceIds, $storeInfo['hotico']);
            }
            
            if (!empty($storeInfo['store_video'])) {
                array_push($resourceIds, $storeInfo['store_video']);
            }
            
            if (!empty($storeInfo['wx_erweima'])) {
                array_push($resourceIds, $storeInfo['wx_erweima']);
            }
            
            if(!empty($storeInfo['figurechart'])) {
                $resourceIds = array_merge($resourceIds, explode(',', trim($storeInfo['figurechart'], ',')));
            }
            
            D('MediaLib')->deleteMediaByParams(array('resourceid'=>array('in', $resourceIds)));
            deldir(rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/mall/' . $storeInfo['classid']);
            
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！[原因]：' . $storeModel->getError());
        }
    }
    
    /**
     * 商铺相册管理：可上传商铺形象照片
     */
    public function storeGallery() {
        
        $storeID = I('get.id', 0, 'int');
        if (!$storeID) {
            $this->error('非法操作！');
        }
        
        $storeModel = D('SCStore');
        $storeInfo = $storeModel->where(array('Id'=>$storeID))->find();
        if (!$storeInfo) {
            $this->error('非法操作！');
        }
        $this->assign('store', $storeInfo);
        
        $gallery = array();
        $figurechart = explode(',', trim($storeInfo['figurechart'], ','));
        if (!empty($figurechart)) {
            $gallery = D('MediaLib')->where(array('resourceid'=>array('in', $figurechart)))->field('id, resourceid, filepath')->order('id asc')->select();
        }
        
        $this->assign('gallery', $gallery);
        $this->display();
    }
    
    /**
     * 为商铺绑定一个微信账号
     */
    public function storeWeixin() {
        if (IS_POST) {
            $storeID = I('post.store_id', 0, 'int');
            $wxID = I('post.wx_id');
            $wxErweima = I('post.wx_erweima');
            if (!$storeID) {
                $this->error('非法操作！');
            }

            $storeModel = D('SCStore');
            $data = array();
            // 注解：商铺绑定微信号的操作是开关性功能，输入微信号则是绑定，置空则是解绑，输入新的微信号则是更新绑定。
            if (empty($wxID)) {
                $data['wx_id'] = '';
                $data['wx_erweima'] = '';
                $storeInfo = $storeModel->where(array('Id'=>$storeID))->find();
                if (!empty($storeInfo['wx_erweima'])) {
                    D('MediaLib')->deleteMediaByParams(array('resourceid'=>$storeInfo['wx_erweima']));
                }
            } else {
                $data['wx_id'] = $wxID;
                $data['wx_erweima'] = $wxErweima;
            }
            
            $bindWXResult = $storeModel->where(array('Id'=>$storeID))->save($data);
            if ($bindWXResult !== false) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！[原因]：' . $storeModel->getError());
            }
            
        } else {
            $storeID = I('get.id', 0, 'int');
            if (!$storeID) {
                $this->error('非法操作！');
            }
            
            $storeModel = D('SCStore');
            $storeInfo = $storeModel->where(array('Id'=>$storeID))->find();
            if (!$storeInfo) {
                $this->error('非法操作！');
            }
            
            if (!empty($storeInfo['wx_erweima'])) {
                $filepath = D('MediaLib')->where(array('resourceid'=>$storeInfo['wx_erweima']))->getField('filepath');
                $storeInfo['wx_img'] = str_replace('\\', '/', $filepath);
            }
            
            $this->assign('store', $storeInfo);
            $this->display();
        }
    }
    
    /**
     * 活动列表
     */
    public function activities() {}
    
    /**
     * 发布活动
     */
    public function addActivities() {}
    
    /**
     * 编辑活动内容
     */
    public function EditActivities() {}
    
    /**
     * 删除活动
     */
    public function delActivities() {}
    
    /**
     * 商场基本信息以及应用配置选项
     */
    public function mallSyscfg() {
        $mallSyscfgModel = D('SCSyscfg');
        $mallSyscfgInfo = $mallSyscfgModel->where(array('id'=>1))->find();
        if (IS_POST) {
            $mallID = I('post.mall_id', 0, 'int');
            $data['sc_name'] = I('post.sc_name');
            $data['sc_short_name'] = I('post.sc_short_name');
            $data['sc_logo'] = I('post.sc_logo');
            $data['floors'] = I('post.floors', 0, 'int');
            $data['address'] = I('post.address');
            $data['sc_manager'] = I('post.sc_manager');
            $data['contact'] = I('post.contact');
            $data['note'] = trim($_POST['note']);
            $data['hotico'] = I('post.hotico');
            
            $result = false;
            if ($mallID) {
                $result = $mallSyscfgModel->where(array('id'=>$mallID))->save($data);
            } else {
                $result = $mallSyscfgModel->add($data);
            }
            
            if ($result !== false) {
                
                $where = array();
                if ($mallSyscfgInfo['sc_logo'] != $data['sc_logo']) array_push($where, $mallSyscfgInfo['sc_logo']);
                if ($mallSyscfgInfo['hotico'] != $data['hotico']) array_push($where, $mallSyscfgInfo['hotico']);
                if (!empty($where)) {
                    D('MediaLib')->deleteMediaByParams(array('resourceid'=>array('in', $where)));
                }
                
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！[原因]：' . $mallSyscfgModel->getError());
            }
            
        } else {
            
            if ($mallSyscfgInfo) {
                $mallSyscfgInfo['note'] = stripslashes($mallSyscfgInfo['note']);
                $medias = D('MediaLib')->where(array('resourceid'=>array('in', array($mallSyscfgInfo['sc_logo'], $mallSyscfgInfo['hotico']))))->getField('resourceid, filepath');
                if (!empty($medias)) {
                    $mallSyscfgInfo['logoPath'] = str_replace('\\', '/', $medias[$mallSyscfgInfo['sc_logo']]);
                    $mallSyscfgInfo['hoticoPath'] = str_replace('\\', '/', $medias[$mallSyscfgInfo['hotico']]);
                }
            }
            
            $this->assign('mall', $mallSyscfgInfo);
            $this->display();
        }
    }
    
}