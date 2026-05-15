<?php

use App\Core\Session;

$error = Session::getFlash('error');
$success = Session::getFlash('success');
?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= e($success) ?>
    </div>
<?php endif; ?>