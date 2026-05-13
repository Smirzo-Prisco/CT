<div class="pagina_presenti">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['logged_users']['plur']); ?></h2>
    </div>
    <div id="online-users-container"></div>
</div>
<script>
document.addEventListener('ct:ready', function() {
    CT.mount('OnlineUsers', 'online-users-container', {});
});
</script>