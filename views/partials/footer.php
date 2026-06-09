<?php
// footer.php - Inchide pagina: footer, hamburger JS, script-uri extra
// $extra_js se seteaza in fiecare pagina pt script-uri specifice
// footer.php - Footer comun + hamburger menu JS + include script-uri aditionale
?>
<footer><p>&copy; 2026 Crisis Containment Service</p></footer>
<script src="assets/js/ajax.js?v=<?= time() ?>"></script>
<script>
document.getElementById('nav-toggle').addEventListener('click', function() {
  document.getElementById('nav-links').classList.toggle('open');
});
</script>
<?= $extra_js ?? '' ?>
</body>
</html>