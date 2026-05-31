<?php
    // Dynamically generate base href from the actual script location.
    // dirname($_SERVER['SCRIPT_NAME']) always returns the directory of the executed
    // script file regardless of URL rewriting, so it works for any install path.
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/').'/';
    // Project root URL (one level above admin/)
    $root = rtrim(dirname(rtrim($base, '/')), '/').'/';

    $config = file_exists('../settings/config.php') ? include '../settings/config.php' : [];
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base href="<?php echo htmlspecialchars($base, ENT_QUOTES); ?>">
  <title><?php echo htmlspecialchars(($config['web_title'] ?? 'Admin System').' — Admin', ENT_QUOTES); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($config['web_description'] ?? 'Admin panel to manage site contents and settings.', ENT_QUOTES); ?>">

  <!-- Framework CSS -->
  <link rel="stylesheet" href="<?php echo $root; ?>Now/dist/now.core.min.css?v=<?php echo $config['reversion'] ?>">
  <link rel="stylesheet" href="<?php echo $root; ?>Now/css/fonts.css?v=<?php echo $config['reversion'] ?>">
  <!-- Rich Text Editor CSS -->
  <link rel="stylesheet" href="<?php echo $root; ?>Now/dist/richtext-editor.min.css?v=<?php echo $config['reversion'] ?>">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/styles.css?v=<?php echo $config['reversion'] ?>">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <main id="main" role="main">
    <!-- Main Content -->
  </main>

  <!-- Framework Scripts -->
  <script src="<?php echo $root; ?>Now/dist/now.core.min.js?v=<?php echo $config['reversion'] ?>"></script>
  <script src="<?php echo $root; ?>Now/dist/now.table.min.js?v=<?php echo $config['reversion'] ?>"></script>
  <script src="<?php echo $root; ?>Now/dist/now.graph.min.js?v=<?php echo $config['reversion'] ?>>"></script>
  <!-- Rich Text Editor -->
  <script src="<?php echo $root; ?>Now/dist/richtext-editor.min.js?v=<?php echo $config['reversion'] ?>"></script>

  <!-- App Scripts -->
  <script src="js/main.js?v=<?php echo $config['reversion'] ?>"></script>
  <script src="js/admin.js?v=<?php echo $config['reversion'] ?>"></script>
  <script src="<?php echo $root; ?>js/global.js?v=<?php echo $config['reversion'] ?>"></script>

  <!-- Module & Widget Scripts (auto-discovered) -->
  <?php
      foreach (['modules', 'widgets'] as $dir) {
          $path = __DIR__.'/../'.$dir;
          if (is_dir($path)) {
              foreach (scandir($path) as $name) {
            if ($name[0] !== '.' && $name[0] !== 'index') {
                if (is_file($path.'/'.$name.'/admin.js')) {
                    echo '<script src="'.$root.$dir.'/'.$name.'/admin.js?v='.$config['reversion'].'"></script>'."\n";
                }
                if (is_file($path.'/'.$name.'/admin.css')) {
                    echo '<link rel="stylesheet" href="'.$root.$dir.'/'.$name.'/admin.css?v='.$config['reversion'].'">'."\n";
                }
            }
              }
          }
      }
  ?>
</body>

</html>