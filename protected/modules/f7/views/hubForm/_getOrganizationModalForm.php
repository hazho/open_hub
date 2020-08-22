<a class="btn btn-link btn-xs btn-white" href="" data-toggle="modal" data-target="#<?php echo $modelId ?>"><?php echo $linkText ?></a><div id="startup-alert" style="color:red;display:none;">This name is taken. Choose another name.</div>

<!-- Modal -->
<div class="modal fade" id="<?php echo $modelId ?>" role="dialog">
    <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
        <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo Yii::t('f7', 'Add New') ?></h4>
        </div>
        <div class="modal-body">
            <div id="org-form-modal">
            <div id="org-alert-modal" style="display:none" class="alert alert-danger">
                <strong>Error!</strong> <?php echo Yii::t('f7', 'Organization / Company / Project name already exists. Please choose a different name.') ?>

            </div>
            <div class="form-group margin-bottom">
                    <label for="form-label org-name-modal"><?php echo Yii::t('f7', 'Organization / Company / Project Name') ?></label>
                    <input type="text" class="form-control" id="org-name-modal" name="org-name-modal" placeholder="Enter organization/company/project name">
            </div>
            <div class="form-group margin-bottom">
                    <label for="form-label org-url-modal"><?php echo Yii::t('f7', 'Website') ?></label>
                    <input type="text" class="form-control" id="org-url-modal" name="org-url-modal" placeholder="Enter website">
            </div>
            <div class="form-group margin-bottom">
                    <label for="form-label org-oneliner-modal"><?php echo Yii::t('f7', 'One Liner') ?></label>
                    <input type="text" class="form-control" id="org-oneliner-modal" name="org-oneliner-modal" placeholder="Enter oneliner">
            </div>
                <button name="org-button-modal" id="org-button-modal" class="btn btn-primary btn-block"><?php echo Yii::t('f7', 'Create') ?></button>
            </div>
        </div>
    </div>

    </div>
</div>