<?php
/** @var $component \digitv\yii2live\Yii2Live */
$modalSizeClass = !empty($component->modalDefaultSize) ? ' modal-' . $component->modalDefaultSize : '';
?>
<!-- Default Yii2Live modal -->
<div class="modal fade" id="<?= $component->modalDefaultId ?>" data-loading-text="<?= Yii::t('yii2live', 'Loading...') ?>"
     data-title="<?= Yii::t('yii2live', 'Details') ?>">
    <div class="modal-dialog<?= $modalSizeClass ?>">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?= Yii::t('yii2live', 'Details') ?></h4>
            </div>
            <div class="modal-body"></div>
            <?php if ($component->modalDefaultWithFooterClose): ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-block" data-dismiss="modal"><?= Yii::t('yii2live', 'Close') ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
