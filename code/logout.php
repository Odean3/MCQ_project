<?php

session_start();

$_SESSION = [];

session_destroy();

header('Location: login.php');
exit();
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
