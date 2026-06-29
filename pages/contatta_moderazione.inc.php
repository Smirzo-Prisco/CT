<?php
if (empty($_SESSION['login'])) return;
?>
<div id="contatta-moderazione-root"></div>
<script>
document.addEventListener('ct:ready', function() {
    CT.mount('ContattaModerazione', 'contatta-moderazione-root');
});
</script>
