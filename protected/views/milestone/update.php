<?php
/* @var $this MilestoneController */
/* @var $model Milestone */

$this->breadcrumbs = array(
	'Milestones' => array('index'),
	$model->title => array('view', 'id' => $model->id),
	Yii::t('backend', 'Update'),
);

$this->menu = array(
	array(
		'label' => Yii::t('app', 'Manage Milestone'), 'url' => array('/milestone/admin'),
		'visible' => HUB::roleCheckerAction(Yii::app()->user->getState('rolesAssigned'), Yii::app()->controller, 'admin')
	),
	array(
		'label' => Yii::t('app', 'Create Milestone'), 'url' => array('/milestone/create'),
		'visible' => HUB::roleCheckerAction(Yii::app()->user->getState('rolesAssigned'), Yii::app()->controller, 'create')
	),
	array(
		'label' => Yii::t('app', 'View Milestone'), 'url' => array('/milestone/view', 'id' => $model->id),
		'visible' => HUB::roleCheckerAction(Yii::app()->user->getState('rolesAssigned'), Yii::app()->controller, 'view')
	),
);
?>

<h1><?php echo Yii::t('backend', 'Update Milestone'); ?></h1>

<?php $this->renderPartial('_form', array('model' => $model)); ?>