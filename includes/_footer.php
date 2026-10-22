<?php
/** includes/_footer.php — shared public storefront footer. */
$config = app_config();
?>
<footer class="site-footer">
    <div class="container">
        &copy; <?= date('Y') ?> <?= h($config['app']['name']) ?>. All rights reserved.
    </div>
</footer>
