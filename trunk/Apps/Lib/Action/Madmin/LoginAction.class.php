<?php
class LoginAction extends Action {
	
	public function _initialize() {
		dbConfig();
	}
	
	// 登录页面
	public function index(){
		if ($_SESSION[C('USER_AUTH_KEY')]) {
			$this->success('已登陆，无需重复登录！', U('Index/index'));
		} else {
			$this->display('Public:login');
		}
	}
	
	// 登录信息验证
	public function authGateway() {
		if (IS_POST) {
			$account = I('post.account');
			$password = I('post.password');
			$vcode = I('post.vcode');
			
			if (C('s_login_vcode')) {
				// 验证验证码
				if ($_SESSION['verify'] != md5($vcode)) {
					// $this->error('验证码输入错误！');	
					echo json_encode(array('stat'=>-1, 'msg'=>'验证码错误！'));
					exit();
				}
			}
			
			if (empty($account) || empty($password)) {
				// $this->error('用户名、密码不合法！');	
				echo json_encode(array('stat'=>-2, 'msg'=>'用户名、密码不合法！'));
				exit();
			}
			
			// 数据库验证
			$userModel = M('users');
			$uInfo = $userModel->where(array('account'=>utf82gbk($account)))->find();
			if ($uInfo) {
				
				if (!$uInfo['status']) {
					echo json_encode(array('stat'=>-3, 'msg'=>'用户名已被禁用！请联系管理员查看！'));
					exit();
				}
				
				if ($uInfo['password'] == md5($password)) {
					
					session(C('USER_AUTH_KEY'), $uInfo['id']);
					session('username', gbk2utf8($uInfo['account']));
					session('role', $uInfo['role_id']);
					session('last_login_time', date('Y-m-d H:i:s', $uInfo['last_login_time']));
					// session('group_id', $uInfo['group_id']);
					
					// 如果用户是超级管理员，则可以进行一切操作
					if ($uInfo['account'] == C('ADMIN_AUTH_KEY')) {
						session(C('ADMIN_AUTH_KEY'), true);
					}
										
					// 登录成功，取出用户权限信息
					import('@.ORG.RBAC');
					Rbac::saveAccessList();
					
					// 更新最后登录时间
					$userModel->where(array('id'=>$uInfo['id']))->save(array('last_login_time'=>time()));
					
					//日志 START
					$logModel = D("Log");
					$logModel->writeLog(session(C('USER_AUTH_KEY')),session('username'),'用户登陆','','userLogin');
					//日志　END
					
					// $this->redirect(U('Index/index'));
					echo json_encode(array('stat'=>1, 'msg'=>'登录成功！'));
					exit();
				} else {
					// $this->error('用户名或密码错误！');
					echo json_encode(array('stat'=>0, 'msg'=>'用户名或密码错误！！'));
					exit();
				}			
			} else {
				// $this->error('用户名或密码错误！');
				echo json_encode(array('stat'=>0, 'msg'=>'用户名或密码错误！！'));
				exit();
			}
			
		} else {
			redirect(PHP_FILE.C('USER_AUTH_GATEWAY'));
		}
	}
	
	// 验证码
	public function verifyCode() {
		import('@.ORG.Image');
		Image::buildImageVerify2();
	}
	
	// 退出登录
	public function eout() {
		//日志 START
		$logModel = D("Log");
		$logModel->writeLog(session(C('USER_AUTH_KEY')),session('username'),'用户退出登陆','','userLogout');
		//日志　END
		
		$_SESSION = array(); // 清除SESSION值
		
		// 判断客户端的cookie文件是否存在，存在的话将其设置为过期
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-1, '/');
		}
		
		// 清除服务器的SESSION文件
		session_destroy();
		
		$this->redirect('Login/index');
	}
}