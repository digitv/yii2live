<?php
/**
 * Minimal view
 * @var $content string
 * @var $this \digitv\yii2live\components\View
 */
?>
<?php $this->beginPage(); ?>
<!DOCTYPE html>
<html>
<head>
<?php $this->head(); ?>
</head>
<body>
<?php $this->beginBody(); ?>
<?= $content; ?>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(); ?>