<?php
/* @var $this EventOrganizationController */
/* @var $model EventOrganization */
/* @var $form CActiveForm */
?>


<div class="">

<?php $form = $this->beginWidget('ActiveForm', array(
	'id' => 'event-organization-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation' => false,
	'htmlOptions' => array(
		'class' => 'form-horizontal crud-form',
		'role' => 'form',
		'enctype' => 'multipart/form-data',
	)
)); ?>

<?php echo Notice::inline(Yii::t('core', 'Fields with <span class="required">*</span> are required.')); ?>
<?php if ($model->hasErrors()): ?>
	<?php echo $form->bsErrorSummary($model); ?>
<?php endif; ?>


	<div class="form-group <?php echo $model->hasErrors('event_id') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model, 'event_id'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsForeignKeyDropDownList($model, 'event_id', array('class' => 'chosen', 'params' => array('mode' => 'idAsKey'))); ?>
			<?php echo $form->bsError($model, 'event_id'); ?>
		</div>
	</div>

	<div class="form-group <?php echo $model->hasErrors('organization_id') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model, 'organization_id'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsForeignKeyDropDownList($model, 'organization_id'); ?>
			<?php echo $form->bsError($model, 'organization_id'); ?>
		</div>
	</div>

	<div class="form-group <?php echo $model->hasErrors('as_role_code') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model, 'as_role_code'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsTextField($model, 'as_role_code'); ?>
			<?php echo $form->bsError($model, 'as_role_code'); ?>
		</div>
	</div>

	<div class="form-group <?php echo $model->hasErrors('registration_code') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model, 'registration_code'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsTextField($model, 'registration_code'); ?>
			<?php echo $form->bsError($model, 'registration_code'); ?>
		</div>
	</div>



	<div class="form-group <?php echo $model->hasErrors('date_action') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model, 'date_action'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsDateTextField($model, 'date_action', array('nullable' => true)); ?>
			<?php echo $form->bsError($model, 'date_action'); ?>
		</div>
	</div>




		
	<!-- many2many -->
	<!-- /many2many -->

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<?php echo $form->bsBtnSubmit($model->isNewRecord ? Yii::t('core', 'Create') : Yii::t('core', 'Save')); ?>
		</div>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

