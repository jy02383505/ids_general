<?php
class TemplateViewModel extends ViewModel {
	public $viewFields = array(
		'tpls'=>array('id', 'tplname', 'tplpx_width', 'tplclassid', 'tplpx_height', 'tpltype'),
		'TendpointType'=>array('typename', 'typecode', '_on'=>'TendpointType.typecode=tpls.tpltype'),
		'programs'=>array('program_name', '_on'=>'tpls.binding_program_classid=programs.program_classid'),
	);	
}