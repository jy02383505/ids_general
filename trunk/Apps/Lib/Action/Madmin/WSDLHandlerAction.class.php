<?php
/**
 * WSDL操作类
 * @author Skeam TJ
 *
 */
class WSDLHandleAction extends CommonAction {
	
	// 命令参数对照表//'http://localhost:5249?wsdl'
	private $WSDLURL = "http://".C("TSERVER_IP").":".C("SOAP_PORT")."?wsdl";
	private $commandList = array(
			'PublishNewMainSoft'			=>	array('commandName'=>'Command_PublishNewMainSoft','commandNote'=>'请求更新系统版本'),
			'DownLoadDataFile'				=>	array('commandName'=>'Command_DownLoadDataFile','commandNote'=>'更新数据文件'),
			'RestartMain'					=>	array('commandName'=>'Command_RestartMain','commandNote'=>'请求重启终端主程序'),
			'ShutdownMain'					=>	array('commandName'=>'Command_ShutdownMain','commandNote'=>'请求关闭终端主程序'),
			'RestartSystem'					=>	array('commandName'=>'Command_RestartSystem','commandNote'=>'请求重启终端计算机'),
			'CloseSystem'					=>	array('commandName'=>'Command_CloseSystem','commandNote'=>'请求终端关闭计算机'),
			'CaptureScreen'					=>	array('commandName'=>'Command_CaptureScreen','commandNote'=>'请求终端截图'),
			'ChangeEndpointName'			=>	array('commandName'=>'Command_ChangeEndpointName','commandNote'=>'请求修改终端名称'),
			'ChangeTouchMainCloseTime'		=>	array('commandName'=>'Command_ChangeTouchMainCloseTime','commandNote'=>'请求修改关机时间'),
			'ChangeTouchMainExitCode'		=>	array('commandName'=>'Command_ChangeTouchMainExitCode','commandNote'=>'请求修改终端关机码'),
			'ChangeEndpointInterval'		=>	array('commandName'=>'Command_ChangeEndpointInterval','commandNote'=>'请求修改终端心跳间隙'),
			'ChangeTouchMainAdsDelayTime'	=>	array('commandName'=>'Command_ChangeTouchMainAdsDelayTime','commandNote'=>'请求修改广告延迟时间'),
            'RefreshList'                  =>	array('commandName'=>'Command_RefreshList','commandNote'=>'请求刷新终端列表')
	);
	
	private function initWSDL($errMsg = 'WSDL初始化失败……') {
		
		$clients = null;
		
		//  请求TServer的服务端口
		try {
			$options = array(
					'exceptions'=>true,
					'cache_wsdl'=>WSDL_CACHE_NONE
			);
			$clients = @new SoapClient($this->WSDLURL, $options);
		} catch (Exception $e) {
			//echo json_encode(array('stat'=>0, 'msg'=>$e->getMessage()));
			echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
			exit();
		} catch(SoapFault $f) {
			// echo json_encode(array('stat'=>0, 'msg'=>$f->getMessage()));
			echo json_encode(array('stat'=>0, 'msg'=>$errMsg));
			exit();
		}
		
		return $clients;
	}
	
	private function endpointTaskWSDL($wsdlObj, $params) {
		
	}
}