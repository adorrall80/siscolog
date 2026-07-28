<?php

use Core\Session;
use Core\View;

$success = Session::flash('success');
$error = Session::flash('error');
?>

<?php if ($success): ?>
    <div class="flash success"><?= View::escape($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="flash error"><?= View::escape($error) ?></div>
<?php endif; ?>
