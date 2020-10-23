<?php
/* @var $this CvJobposGroupController */
/* @var $model CvJobposGroup */

$this->breadcrumbs=array(
	'Cv Jobpos Groups'=>array('index'),
	$model->title,
);

$this->menu=array(
	array('label'=>Yii::t('app','Manage CvJobposGroup'), 'url'=>array('/cv/cvJobposGroup/admin')),
	array('label'=>Yii::t('app','Create CvJobposGroup'), 'url'=>array('/cv/cvJobposGroup/create')),
	array('label'=>Yii::t('app','Update CvJobposGroup'), 'url'=>array('/cv/cvJobposGroup/update', 'id'=>$model->id)),
	array('label'=>Yii::t('app','Delete CvJobposGroup'), 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id), 'csrf'=>Yii::app()->request->enableCsrfValidation, 'confirm'=>Yii::t('core', 'Are you sure you want to delete this item?'))),
);
?>


<h1><?php echo Yii::t('backend', 'View Cv Jobpos Group'); ?></h1>

<div class="crud-view">
<?php $this->widget('application.components.widgets.DetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'title',
		'ordering',
		array('name'=>'is_active', 'type'=>'raw', 'value'=>Html::renderBoolean($model->is_active)), 
		array('label'=>$model->attributeLabel('date_added'), 'value'=>Html::formatDateTime($model->date_added, 'long', 'medium')),
		array('label'=>$model->attributeLabel('date_modified'), 'value'=>Html::formatDateTime($model->date_modified, 'long', 'medium')),
	),
)); ?>



</div>