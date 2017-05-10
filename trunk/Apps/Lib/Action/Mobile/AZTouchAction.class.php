<?php

class AZTouchAction extends Action {
	
	private $articleSavepath = '';

    public function index() {
        $this->display();
    }
    
    public function articles() {
        $type = I('get.type');
        $columns = array(
            'xwtz'  =>  array('classId'=>'', 'title'=>'新闻通知'),
            'zytg'  =>  array('classId'=>'', 'title'=>'资源通告'),
            'jzxx'  =>  array('classId'=>'', 'title'=>'讲座信息'),
            'qqxw'  =>  array('classId'=>'', 'title'=>'全球新闻'),
            'tsjs'  =>  array('classId'=>'', 'title'=>'图书检索'),
            'xsll'  =>  array('classId'=>'', 'title'=>'新书浏览'),
            'wdjy'  =>  array('classId'=>'', 'title'=>'我的借阅'),
            'wtzx'  =>  array('classId'=>'', 'title'=>'热点问题')
        );
        
        $columnModel = M('ProgramsDirs');
        $columnClassId = $columnModel->where(array('dir_name'=>$columns[$type]['title']))->getField('classid');
        
        $articleModel = M('ProgramsArticles');
        
        if ($type == 'xsll') {
            
            /* $dataAPIModel = M('Dataapis');
            $apiID = '06176c99-c250-163a-7fb5-73edcb6eb564';
            $apiInfo = $dataAPIModel->where(array('api_unicode'=>$apiID))->find();
            if (!$apiInfo) {
                echoMsg('非法操作！');die();
            }
            $apiParams = json_decode(stripslashes($apiInfo['api_params']), true);
            $apiURL = $apiParams['requestURL'] . '?sign=' . $apiID . '&rows=10';
            
            $timeout = array(
                'http'  =>  array(
                    'timeout' => 180  //设置一个超时时间，单位为秒
                )
            );
            $ctx = stream_context_create($timeout);
            $booksData = file_get_contents($apiURL, 0, $ctx);
            if (!$booksData) {
                echoMsg('无数据！');die();
            }
            
            $books = json_decode($booksData, true);
            if (!$books['status']) {
                echoMsg('无数据！');die();
            } */
            
            //$articles = $books['datalist'];
            
        } else if ($type == 'wtzx') {
            
            $columns[$type]['title'] = '问题咨询';
            $childrenColumns = $columnModel->field(array('classid', 'dir_name'))->where(array('parent_classid'=>$columnClassId))->select();
            foreach ($childrenColumns as &$sc) {
                $sc['datalist'] = $articleModel->field(array('id', 'article_name', 'article_classid', 'article_ico', 'program_dir_classid', 'create_time', 'article_content'))->where(array('program_dir_classid'=>$sc['classid']))->order('create_time desc')->select();
            }
            //dump($childrenColumns);
            $articles = $childrenColumns;
            
        } else { // 分页列表展示类文章：讲座信息、新闻通知、资源通告、全球要闻
    		$where = array('program_dir_classid'=>$columnClassId);
    	
    		// 加载数据分页类
    		import('@.ORG.Page');
    	
    		// 数据分页
    		$totals = $articleModel->where($where)->count();
    		$Page = new Page($totals, 12);
    		$Page->setConfig('theme', '%upPage% %nowPage%/%totalPage% %downPage%');
    		if ($totals > 12) {
        		$show = $Page->show();
        		$this->assign('pager', $show);
    		}
    	
    		$articles = $articleModel->field(array('id', 'article_name', 'article_classid', 'article_ico', 'program_dir_classid', 'create_time'))->where($where)->order('create_time desc')->limit($Page->firstRow. ',' .$Page->listRows)->select();
    		foreach ($articles as &$arti) {
    			$articleName = stripslashes(stripslashes($arti['article_name']));
    			$arti['article_name'] = mb_strlen($articleName, 'UTF-8') > 25 ? mb_substr($articleName, 0, 25, 'UTF-8') . '....' : $articleName;
    			$arti['article_ico_path'] = str_replace('\\', '/', D('MediaLib')->where(array('resourceid'=>$arti['article_ico']))->getField('filepath'));
    		}
    
        }
        
		$this->assign('articles', $articles);
        $this->assign('column', $columns[$type]);
        $this->display();
    }
    
    public function contents() {
        $type = trim(I('get.type'));
        $articleClassID = trim(I('get.classId'));
        
        if (empty($articleClassID)) {
            $this->error('请求的资源不存在！');
        }
        
        $articleModel = M('ProgramsArticles');
        $articleInfo = $articleModel->field(array('id', 'article_name', 'article_content', 'create_time'))->where(array('article_classid'=>$articleClassID))->find();
        if (!$articleInfo) {
            $this->error('请求的资源不存在！');
        }
		$articleName = $articleInfo['article_name'] = stripslashes(stripslashes($articleInfo['article_name']));
        $articleInfo['article_name_short'] = mb_strlen($articleName, 'UTF-8') > 18 ? mb_substr($articleName, 0, 18, 'UTF-8') . '....' : $articleName;
        
        if ($type == 'qqxw') {
            $pictures = D('MediaLib')->where(array('resourceid'=>$articleClassID, 'filepath'=>array('notlike', '%.html')))->select();
            foreach ($pictures as &$pic) {
                $pic['filepath'] = str_replace('\\', '/', $pic['filepath']);
            }
            $this->assign('pictures', $pictures);
        } else {
            //$articleInfo['article_content'] = htmlspecialchars_decode(stripcslashes(stripcslashes($articleInfo['article_content'])));
			$confile = rtrim(C('UPLOAD_ROOT_PATH'), '/') . '/' . str_replace('\\', '/', ltrim(D('MediaLib')->where(array('resourceid'=>$articleClassID, 'filepath'=>array('like', '%.html')))->getField('filepath'), '/'));
			$contents = '';
			if (is_file($confile) && preg_match('/<body><div.*?<\/div>(.*?)<\/body>/i', gbkToUtf8(file_get_contents($confile)), $matches)) {
				$contents = $matches[1];
				//$contents = preg_replace('/(background:.*?;)|(background\-color:.*?;)/i', '', $contents);
				$programClassID = 'c1cff811-b8d9-a194-3fdf-46e9a0be1817';
				$this->articleSavepath = '/Uploads/medialib/program/' . $programClassID . '/article/' . $articleClassID . '/';
				$contents = preg_replace_callback('/<img\ssrc="(.*?)"\s/', array($this, 'replaceImgSrcOut'), $contents);
			}
            $articleInfo['article_content'] = $contents;
        }
        $this->assign('articleInfo', $articleInfo);
        $this->display();
    }
	
	private function replaceImgSrcOut($matches) {
		//$newPath = '<img src="' . str_replace('./', $this->articleSavepath, $matches[1]) . '" ';
		$newPath = '<img src="' . $this->articleSavepath . basename($matches[1]) . '" ';
		return $newPath;
	}
	
	public function iborrows() {
	    $this->assign('columnTitle', '我的借阅');
	    $this->display();
	}
	
	public function searchBooks() {
	    
	    /* $apiID = 'f18ac237-da72-2c94-32f1-ed4bb612bb73';
	    $dataAPIModel = M('Dataapis');
	    $apiInfo = $dataAPIModel->where(array('api_unicode'=>$apiID))->find();
	    if (!$apiInfo) {
	        die(json_encode(array('stat'=>0, 'msg'=>'网络数据接口错误！')));
	    }
	    $apiParams = json_decode(stripslashes($apiInfo['api_params']), true);
	    $this->assign('apiParams', $apiParams['params']); */
	    
	    $this->assign('columnTitle', '图书检索');
	    $this->display('search');
	}
	
	public function searchResults() {
	    $queryKeywords = I('get.qkeywords');
	    $queryType = I('get.qtype');
	    $queryWays = I('get.qways');
	    $currPage = I('get.p', 0, 'int');
	    
	    $this->assign('searKeywords', $queryKeywords);
	    $this->assign('searType', $queryType);
	    $this->assign('searWays', $queryWays);
	    $this->assign('currPage', $currPage);
	    $this->display();    
	}
	
	public function bookInfo() {
	    $this->display('book');
	}
	
	public function getApiData() {
	    
	    //sleep(8);
		set_time_limit(0); 
	    $type = I('post.type');
	    $apiID = I('post.apid');
	    $isbn = I('post.isbn');
	    $borrowNum = I('post.borrowNum');
	    $searKeywords = I('post.sear_keywords');
	    $searType = I('post.sear_type');
	    $searWay = I('post.sear_way');
	    $pageNum = I('post.page_num', 0, 'int');
	    
	    if (empty($type) || empty($apiID)) {
	        die(json_encode(array('stat'=>0, 'msg'=>'网络参数错误！')));
	    }
	    
	    $dataAPIModel = M('Dataapis');
	    $apiInfo = $dataAPIModel->where(array('api_unicode'=>$apiID))->find();
	    if (!$apiInfo) {
	        die(json_encode(array('stat'=>0, 'msg'=>'网络数据接口错误！')));
	    }
	    $apiParams = json_decode(stripslashes($apiInfo['api_params']), true);
	    $apiURL = $apiParams['requestURL'] . '?sign=' . $apiID;
	    
	    if ($type == 'xsll') {
	        $apiURL .= '&rows=16';
	    } elseif ($type == 'new_book') {
	        $apiURL .= '&isbn=' . $isbn;
	    } elseif ($type == 'iborrow') {
	        $apiURL .= '&icd=' . $borrowNum;
	    } elseif ($type == 'searcher') {
	        $apiURL .= '&keywords=' . strtoupper($searKeywords) . '&type=' . $searType . '&queryWay=' . $searWay . '&rows=15';
	        $pageNum > 0 && $apiURL .= '&p=' . $pageNum;
	    }
	    
	    $timeout = array(
	        'http'  =>  array(
	            'timeout' => 180  //设置一个超时时间，单位为秒
	        )
	    );
	    $ctx = stream_context_create($timeout);
	    $apiData = file_get_contents($apiURL, 0, $ctx);
	    if (!$apiData) {
	        die(json_encode(array('stat'=>0, 'msg'=>'没有获取到数据！')));
	    }
	    
	    $apiDataNew = json_decode($apiData, true);
	    if (!$apiDataNew['status']) {
	        die(json_encode(array('stat'=>0, 'msg'=>'没有获取到数据！')));
	    }
	    
	    $returnData = null;
	    if ($type == 'xsll') {
	        $returnData = $apiDataNew['datalist'];
	    } elseif ($type == 'new_book') {
	        //$returnData = $apiDataNew['bookinfo'];
	        unset($apiDataNew['status']);
	        $returnData = $apiDataNew;
	    } else {
	        unset($apiDataNew['status']);
	        $returnData = $apiDataNew;
	    }
	    
        die(json_encode(array('stat'=>1, 'books'=>$returnData)));
	}
}