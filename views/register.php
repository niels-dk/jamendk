<?php $title = 'Register'; ob_start(); ?>
<h2>Create Account</h2>
<?php if (!empty($error)) echo '<p style="color:red">'.$error.'</p>'; ?>
<form method="post">
    <label>Name <input type="text" name="name" required></label><br>
    <label>Email <input type="email" name="email" required></label><br>
    <label>Password <input type="password" name="password" required></label><br>
    <button type="submit">Register</button>
</form>
<?php $content = ob_get_clean(); include __DIR__.'/layout.php'; ?>