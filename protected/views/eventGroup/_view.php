<?php
/* @var $this EventGroupController */
/* @var $data EventGroup */
?>

<div class="view panel panel-default">
<div class="panel-heading">
	<b><?php echo Html::encode($data->getAttributeLabel('id')); ?>:</b>
	#<?php echo Html::link(Html::encode($data->id), array('view', 'id' => $data->id)); ?>
	<br />

</div>
<div class="panel-body">

	<b><?php echo Html::encode($data->getAttributeLabel('code')); ?>:</b>
	<?php echo Html::encode($data->code); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('title')); ?>:</b>
	<?php echo Html::encode($data->title); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('text_oneliner')); ?>:</b>
	<?php echo Html::encode($data->text_oneliner); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('text_short_description')); ?>:</b>
	<?php echo Html::encode($data->text_short_description); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('url_website')); ?>:</b>
	<?php echo Html::encode($data->url_website); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('slug')); ?>:</b>
	<?php echo Html::encode($data->slug); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('genre')); ?>:</b>
	<?php echo Html::encode($data->genre); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('funnel')); ?>:</b>
	<?php echo Html::encode($data->funnel); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('date_started')); ?>:</b>
	<?php echo Html::encode(Html::formatDateTime($data->date_started, 'long', 'medium')); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('date_ended')); ?>:</b>
	<?php echo Html::encode(Html::formatDateTime($data->date_ended, 'long', 'medium')); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('is_active')); ?>:</b>
	<?php echo Html::encode(Yii::t('core', Yii::app()->format->boolean($data->is_active))); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('json_extra')); ?>:</b>
	<b><?php echo Html::encode($data->getAttributeLabel('date_added')); ?>:</b>
	<?php echo Html::encode(Html::formatDateTime($data->date_added, 'long', 'medium')); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('date_modified')); ?>:</b>
	<?php echo Html::encode(Html::formatDateTime($data->date_modified, 'long', 'medium')); ?>
	<br />


</div>
</div>