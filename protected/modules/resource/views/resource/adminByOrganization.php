<?php
/* @var $this ResourceController */
/* @var $model Resource */

if ($realm == 'backend') {
	$this->breadcrumbs = array(
		Yii::t('backend', 'Resources') => array('index'),
		Yii::t('backend', 'Manage'),
	);
	$this->renderPartial('/resource/_menu', array('model' => $model, 'organization' => $organization, 'realm' => $realm));
} elseif ($realm == 'cpanel') {
	$this->breadcrumbs = array(
		'Organization' => array('index'),
		$organization->title => array('organization/view', 'id' => $organization->id, 'realm' => $realm),
		Yii::t('backend', 'Resources')
	);
	$this->renderPartial('//cpanel/_menu', array('model' => $model, 'organization' => $organization, 'realm' => $realm));
}
?>

<?php if ($realm == 'backend'): ?><h1><?php echo Yii::t('backend', "Manage Resources for '{organizationName}'", array('{organizationName}' => $organization->title)); ?></h1><?php endif; ?>

<?php if ($realm == 'cpanel'): ?>
<div class="sidebard-panel left-bar">
 <div id="header" class="my-org-active">
 	<h3 class="my-org-name"><?php echo $organization->title?><span class="hidden-desk">
                <a class="container-arrow scroll-to" href="#">
                    <span><i class="fa fa-angle-down" aria-hidden="true"></i></span>
                </a></span></h3>
 	<a href="<?php echo Yii::app()->createUrl('/organization/select') ?>">
		<h4 class="change-org">Change Company</h4>
	</a>
 </div>
 <div id="content-services">
 <div class="header-org" class="margin-top-lg">
		<?php 

			$this->widget('zii.widgets.CMenu', array(
			'items' => array(
					array('label' => sprintf('%s', Yii::t('app', 'Overview')), 'url' => array('/organization/view', 'id' => $organization->id, 'realm' => 'cpanel'), 'active' => ($this->activeSubMenuCpanel == 'view') ? true : null, 'linkOptions' => array('class' => 'your-org-subMenu')),
					array('label' => sprintf('%s', Yii::t('app', 'Update')), 'url' => array('/organization/update', 'id' => $organization->id, 'realm' => 'cpanel'), 'active' => ($this->activeSubMenuCpanel == 'update') ? true : null, 'linkOptions' => array('class' => 'your-org-subMenu')),
					array('label' => sprintf('%s', Yii::t('app', 'Manage Products')), 'url' => array('/product/adminByOrganization', 'organization_id' => $organization->id, 'realm' => 'cpanel'), 'active' => ($this->activeSubMenuCpanel == 'product-adminByOrganization') ? true : null, 'linkOptions' => array('class' => 'your-org-subMenu')),
					array('label' => sprintf('%s', Yii::t('app', 'Create Product')), 'url' => array('/product/create', 'organization_id' => $organization->id, 'realm' => 'cpanel'), 'active' => ($this->activeSubMenuCpanel == 'product-create') ? true : null, 'linkOptions' => array('class' => 'your-org-subMenu')),
					array('label' => sprintf('%s', Yii::t('app', 'Manage Resources')), 'url' => array('/resource/resource/adminByOrganization', 'organization_id' => $organization->id, 'realm' => 'cpanel'), 'active' => ($this->activeSubMenuCpanel == 'resource-adminByOrganization') ? true : null, 'linkOptions' => array('class' => 'your-org-subMenu')),
					array('label' => sprintf('%s', Yii::t('app', 'Create Resource')), 'url' => array('/resource/resource/create', 'organization_id' => $organization->id, 'realm' => 'cpanel'), 'active' => ($this->activeSubMenuCpanel == 'resource-create') ? true : null, 'linkOptions' => array('class' => 'your-org-subMenu'))),
		));
		 ?>
 </div>
<!-- <div id="content-services">
        <div class="get-started">
            <h3>Getting Started</h3>
            <div class="guide-link">
                <ul>
                    <li><a id="abt-ctrl" href="#">About MaGIC Central</a></li>
                    <li><a id="abt-dash" href="#">About MaGIC Central Dashboard</a></li>
                    <li><a id="create-acc" href="#">Creating and Manage Account</a></li>
                    <li><a id="what-org" href="#">What is a Company?</a></li>
                    <li><a id="create-org" href="#">Creating and Manage Company</a></li>
                    <li><a href="#">Join Existing Company</a></li>
                    <li><a href="#">What is Product?</a></li>
                    <li><a href="#">What is Services?</a></li>
                    <li><a href="#">What is Activity Feed?</a></li>
                    <li><a href="#">Manage Activity History</a></li>
                </ul>
            </div>
        </div>
        
</div> -->
</div>
</div>
<div class="wrapper wrapper-content content-bg content-left-padding">
	<div class="manage-pad">
			<?php $this->widget('application.components.widgets.GridView', array(
	'id' => 'resource-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		//array('name'=>'id', 'cssClassExpression'=>'id', 'value'=>$data->id, 'headerHtmlOptions'=>array('class'=>'id')),
		// array('name'=>'typefor', 'cssClassExpression'=>'enum', 'value'=>'$data->formatEnumTypefor($data->typefor)', 'headerHtmlOptions'=>array('class'=>'enum'), 'filter'=>$model->getEnumTypefor(false, true)),
		'title',
		array('name' => 'is_featured', 'cssClassExpression' => 'boolean', 'type' => 'raw', 'value' => 'Html::renderBoolean($data->is_featured)', 'headerHtmlOptions' => array('class' => 'boolean'), 'filter' => $model->getEnumBoolean()),
		array('name' => 'is_active', 'cssClassExpression' => 'boolean', 'type' => 'raw', 'value' => 'Html::renderBoolean($data->is_active)', 'headerHtmlOptions' => array('class' => 'boolean'), 'filter' => $model->getEnumBoolean()),
		array('name' => 'date_added', 'cssClassExpression' => 'date', 'value' => 'Html::formatDateTime($data->date_added, \'medium\', false)', 'headerHtmlOptions' => array('class' => 'date'), 'filter' => false),

		array(
			'class' => 'application.components.widgets.ButtonColumn',
			'buttons' => array(
				'view' => array('url' => 'Yii::app()->controller->createUrl(\'resource/view\', array(\'id\'=>$data->id, \'organizationId\'=>$_GET[organizationId], \'realm\'=>$_GET[realm]))'),
				'update' => array('url' => 'Yii::app()->controller->createUrl(\'resource/update\', array(\'id\'=>$data->id, \'organizationId\'=>$_GET[organizationId], \'realm\'=>$_GET[realm]))'),
				'delete' => array('visible' => false)
			),
		),
	),
)); ?>
	</div>
</div>
<?php endif; ?>

<?php if ($realm == 'backend'): ?>
<div class="">
<?php $this->widget('application.components.widgets.GridView', array(
	'id' => 'resource-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		//array('name'=>'id', 'cssClassExpression'=>'id', 'value'=>$data->id, 'headerHtmlOptions'=>array('class'=>'id')),
		array('name' => 'typefor', 'cssClassExpression' => 'enum', 'value' => '$data->formatEnumTypefor($data->typefor)', 'headerHtmlOptions' => array('class' => 'enum'), 'filter' => $model->getEnumTypefor(false, true)),
		'title',
		array('name' => 'is_featured', 'cssClassExpression' => 'boolean', 'type' => 'raw', 'value' => 'Html::renderBoolean($data->is_featured)', 'headerHtmlOptions' => array('class' => 'boolean'), 'filter' => $model->getEnumBoolean()),
		array('name' => 'is_active', 'cssClassExpression' => 'boolean', 'type' => 'raw', 'value' => 'Html::renderBoolean($data->is_active)', 'headerHtmlOptions' => array('class' => 'boolean'), 'filter' => $model->getEnumBoolean()),
		array('name' => 'date_added', 'cssClassExpression' => 'date', 'value' => 'Html::formatDateTime($data->date_added, \'medium\', false)', 'headerHtmlOptions' => array('class' => 'date'), 'filter' => false),

		array(
			'class' => 'application.components.widgets.ButtonColumn',
			'buttons' => array(
				'view' => array('url' => 'Yii::app()->controller->createUrl(\'resource/view\', array(\'id\'=>$data->id, \'organizationId\'=>$_GET[organizationId], \'realm\'=>$_GET[realm]))'),
				'update' => array('url' => 'Yii::app()->controller->createUrl(\'resource/update\', array(\'id\'=>$data->id, \'organizationId\'=>$_GET[organizationId], \'realm\'=>$_GET[realm]))'),
				'delete' => array('visible' => false)
			),
		),
	),
)); ?>
</div>
<?php endif; ?>