<?php

Yii::import('zii.widgets.grid.CButtonColumn');

class ButtonColumn extends CButtonColumn
{
	public $htmlOptions=array('class'=>'button-column');
	public $viewButtonOptions=array('class'=>'view btn btn-xs btn-primary');
	public $updateButtonOptions=array('class'=>'update btn btn-xs btn-primary');
	public $deleteButtonOptions=array('class'=>'delete btn btn-xs btn-danger');
	public $template='{view} {update} {delete}';
	
	public $viewButtonIconClass; 
	public $updateButtonIconClass; 
	public $deleteButtonIconClass;
	
	public $isResetFilterEnabled = true;
	
	public $filterHtmlOptions;
	
	public function init()
	{
		$this->filterHtmlOptions = array('class'=>'text-center');
		return parent::init();
	}
	
	protected function renderFilterCellContent()
	{
		if($this->isResetFilterEnabled)
		{
			echo Html::link(sprintf('%s %s', Html::faIcon('fa-undo'), Yii::t('core', 'Reset Filter')), Yii::app()->createUrl(Yii::app()->controller->route, array('clearFilters'=>'1')), array('class'=>'btn btn-xs btn-warning'));
		}
	}
	
	protected function initDefaultButtons()
	{
		if($this->viewButtonIconClass===null)
			$this->viewButtonIconClass="fa fa-search";
		if($this->updateButtonIconClass===null)
			$this->updateButtonIconClass="fa fa-edit"; 
		if($this->deleteButtonIconClass===null)
			$this->deleteButtonIconClass="fa fa-times"; 

		// add group effect to the buttons
		if(!empty($this->template) && !strstr($this->template, "<div"))
		{
			$this->template = '<div class="btn-group btn-group-xs">'.$this->template.'</div>';
		}
		
		parent::initDefaultButtons();
	}
	
	protected function renderButton($id,$button,$row,$data)
	{
		if (isset($button['visible']) && !$this->evaluateExpression($button['visible'],array('row'=>$row,'data'=>$data)))
  			return;
		$label=isset($button['label']) ? $button['label'] : $id;
		$url=isset($button['url']) ? $this->evaluateExpression($button['url'],array('data'=>$data,'row'=>$row)) : '#';
		$options=isset($button['options']) ? $button['options'] : array();
		if(!isset($options['title']))
			$options['title']=strip_tags($label);
		
		$iconClass = $this->id2iconClass($id);
		if(!empty($iconClass))
		{
			$tmp = "<i class=\"{$iconClass}\"></i>";
			if($button['showLabelWithIcon']) $tmp .= ' '.$label;
			echo CHtml::link($tmp,$url,$options);
		}
		elseif(isset($button['imageUrl']) && is_string($button['imageUrl']))
		{
			echo CHtml::link(CHtml::image($button['imageUrl'],$label),$url,$options);
		}
		else
		{
			echo CHtml::link($label,$url,$options);
		}
	}
	
	private function id2iconClass($id)
	{
		if($id == "view")
		{
			return $this->viewButtonIconClass;
		}
		elseif($id == "update")
		{
			return $this->updateButtonIconClass;
		}
		elseif($id == "delete")
		{
			return $this->deleteButtonIconClass;
		}
	}
}
