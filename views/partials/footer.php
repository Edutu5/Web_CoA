<?php
?>
<footer><p>&copy; 2026 Crisis Containment Service</p></footer>
<script src="assets/js/ajax.js"></script>
<script>
document.getElementById('nav-toggle').addEventListener('click', function() {
  document.getElementById('nav-links').classList.toggle('open');
});
</script>
<?= $extra_js ?? '' ?>
</body>
</html>