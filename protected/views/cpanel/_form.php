<?php
/* @var $this IndividualController */
/* @var $model Individual */
/* @var $form CActiveForm */
?>


<div class="">

	<?php $form = $this->beginWidget('ActiveForm', array(
		'id' => 'individual-form',
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

	<?php echo Notice::inline(Yii::t('notice', 'Fields with <span class="required">*</span> are required.')); ?>
	<?php if ($model->hasErrors()) : ?>
		<?php echo $form->bsErrorSummary($model); ?>
	<?php endif; ?>


	<div class="form-group <?php echo $model->profile->hasErrors('full_name') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model->profile, 'full_name'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsTextField($model->profile, 'full_name'); ?>
			<?php echo $form->bsError($model->profile, 'full_name'); ?>
		</div>
	</div>


	<div class="form-group <?php echo $model->profile->hasErrors('gender') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($model->profile, 'gender'); ?>
		<div class="col-sm-10">
			<?php echo $form->bsEnumDropDownList($model->profile, 'gender'); ?>
			<?php echo $form->bsError($model->profile, 'gender'); ?>
		</div>
	</div>


	<div class="form-group <?php echo $model->profile->hasErrors('image_avatar') ? 'has-error' : '' ?>">
		<label class=" control-label col-sm-2" for="Profile_image_avatar">Profile Photo</label>
		<div class="col-sm-10">
			<div class="row">
				<div class="col-sm-2 text-left">
					<?php echo Html::activeThumb($model->profile, 'image_avatar'); ?>
				</div>
				<div class="col-sm-8">
					<?php echo Html::activeFileField($model->profile, 'imageFile_avatar'); ?>
					<?php echo $form->bsError($model->profile, 'image_avatar'); ?>
				</div>
			</div>
		</div>
	</div>


	<div class="form-group <?php echo $model->profile->hasErrors('country_code') ? 'has-error' : '' ?>">
		<label class=" control-label col-sm-2" for="Individual_country_code">Country</label>
		<div class="col-sm-10">
			<?php echo $form->bsForeignKeyDropDownList($model->profile, 'country_code', array('nullable' => true)); ?>
			<?php echo $form->bsError($model->profile, 'country_code'); ?>
		</div>
	</div>

	<div class="form-group <?php echo $modelIndividual->hasErrors('inputPersonas') ? 'has-error' : '' ?>">
		<?php echo $form->bsLabelEx2($modelIndividual, 'inputPersonas'); ?>
		<div class="col-sm-10">
			<?php echo $form->dropDownList($modelIndividual, 'inputPersonas', Html::listData(Persona::getForeignReferList()), array('class' => 'js-multi-select js-states form-control', 'multiple' => 'multiple')); ?>
			<?php echo $form->bsError($modelIndividual, 'inputPersonas'); ?>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<?php echo $form->bsBtnSubmit(Yii::t('core', 'Save Changes')); ?>
		</div>
	</div>

	<?php $this->endWidget(); ?>

</div><!-- form -->