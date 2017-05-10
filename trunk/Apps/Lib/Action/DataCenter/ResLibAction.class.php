<?php
/**
 * 数据资源包控制器
 * @author MQY
 *
 */
class ResLibAction extends CommonAction {
	
	public function weather () {
		
		// 获取天气数据
		$reslibWeatherModel = D('ReslibWeather');
		$weaherInfo = $reslibWeatherModel->select();
		foreach ($weaherInfo as &$item) {
			$item['date'] = gbk2utf8($item['date']);
			$item['weather'] = gbk2utf8($item['weather']);
			$item['wind'] = gbk2utf8($item['wind']);
			$item['temperature'] = gbk2utf8($item['temperature']);
		}
		
		$this->assign('weathers', $weaherInfo);
		$this->display();
	}
	
	public function getWeatherInfo () {
	   
		$city = I('get.city');
		//header('Content-Type:text/html;charset=utf-8');
		$re = connection($city);
       // dump($re['results'][0]['weather_data']);exit;
		$reslibWeatherModel = D('ReslibWeather');
		if ($re['results'][0]['weather_data']) {
			$rse = $reslibWeatherModel->where(array('id'=>array('gt', 0)))->delete();
			foreach ($re['results'][0]['weather_data'] as $item) {
				$data = array();
				$data['date'] = utf82gbk($item['date']);
				$data['dayPictureUrl'] = utf82gbk(transf_url($item['dayPictureUrl']));
				$data['nightPictureUrl'] = utf82gbk(transf_url($item['nightPictureUrl']));
				$data['weather'] = utf82gbk($item['weather']);
				$data['wind'] = utf82gbk($item['wind']);
				$data['temperature'] = utf82gbk($item['temperature']);
                $data['cdatetime'] = date();
                
				$addRe = $reslibWeatherModel->add($data);
				if ($addRe === false) {
					$this->error('操作失败，请重新获取……');
				}
			}
			$this->success('操作成功', U('ResLib/weather'));
		} else {
			$this->error('没有获取到网络天气信息数据，请检查……');
		}
	}
    
    
    
    
    
    public function ExcelOut(){

        $model= D("ReslibWeather");  
        $OrdersData= $model->select();  //查询数据得到$OrdersData二维数组  
  
        import('@.ORG.PHPExcel', '', '.php');
  
        // Create new PHPExcel object  
        $objPHPExcel = new PHPExcel();  
        // Set properties  
        $objPHPExcel->getProperties()->setCreator("ctos")  
            ->setLastModifiedBy("ctos")  
            ->setTitle("Office 2007 XLSX Test Document")  
            ->setSubject("Office 2007 XLSX Test Document")  
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
            ->setKeywords("office 2007 openxml php")  
            ->setCategory("Test result file");  
  
        //set width  
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(18);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(40);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12); 
  
        //设置行高度  
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);  
  
       // $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);  
  
        //set font size bold  
       // $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:F2')->getFont()->setBold(true);  
  
        $objPHPExcel->getActiveSheet()->getStyle('A2:F2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:F2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  
  
        //设置水平居中  
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);  
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
  
        //合并cell  
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');  
  
        // set table header content  
        $objPHPExcel->setActiveSheetIndex(0)  
            ->setCellValue('A1', '天气预报') 
            ->setCellValue('A2', '日期')  
            ->setCellValue('B2', '天气')  
            ->setCellValue('C2', '白天天气图片')  
            ->setCellValue('D2', '晚上天气图片')
             ->setCellValue('E2', '风力')  
            ->setCellValue('F2', '温度');  
  
        // Miscellaneous glyphs, UTF-8  
        for($i=0;$i<count($OrdersData);$i++){  
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), trim(gbk2utf8($OrdersData[$i]['date'])));  
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), trim(gbk2utf8($OrdersData[$i]['weather'])));  
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3), trim(gbk2utf8($OrdersData[$i]['dayPictureUrl'])));  
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3), trim(gbk2utf8($OrdersData[$i]['nightPictureUrl'])));
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3), trim(gbk2utf8($OrdersData[$i]['wind'])));  
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3), trim(gbk2utf8($OrdersData[$i]['temperature'])));    
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':F'.($i+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':F'.($i+3))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  
           // $objPHPExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(16);  
        }  
  
  
        //  sheet命名  
        $objPHPExcel->getActiveSheet()->setTitle('天气预报表');  
  
  
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet  
        $objPHPExcel->setActiveSheetIndex(0);  
  
  
        // excel头参数  
        //header('Content-Type: application/vnd.ms-excel');  
       // header('Content-Disposition: attachment;filename="天气预报表('.date('Ymd-His').').xls"');  //日期为文件名后缀  
       // header('Cache-Control: max-age=0');  
  
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'excel5');  //excel5为xls格式，excel2007为xlsx格式 
       
        $objWriter->save('Uploads/reslib/download/天气预报'.date('Ymd-His').'.xls'); 
        	$this->success('操作成功', U('ResLib/weather'));
    }  
  
  
}