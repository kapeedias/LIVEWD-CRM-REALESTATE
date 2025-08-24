 <?php if (!empty($success)): ?>
 <div class="alert alert-success">
     <?= implode('<br>', $success) ?>
 </div>
 <?php endif; ?>

 <?php if (!empty($errors)): ?>
 <div class="alert alert-danger">
     <?= implode('<br>', $errors) ?>
 </div>
 <?php endif; ?>