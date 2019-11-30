<!DOCTYPE html>
<html>
  <head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-139586901-4"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'UA-139586901-4');
    </script>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5ZXCL3S');</script>
    <!-- End Google Tag Manager -->

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5"/>
    <meta name="theme-color" content="#448AFF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#448AFF">
    <meta name="apple-mobile-web-app-title" content="What should I be doing">
    <meta name="description" content="What should I be doing right now.">

    <title>What should I be doing right now</title>

    <meta property="og:type" content="website" />
    <meta property="og:title" content="What should I be doing" />
    <meta property="og:description" content="What should I be doing right now." />
    <meta property="og:image" content="https://what.should-i.be/thumbnail.png" />
    <meta property="og:url" content="https://what.should-i.be/" />

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="What should I be doing">
    <meta name="twitter:description" content="What should I be doing right now.">
    <meta name="twitter:image" content="https://what.should-i.be/thumbnail.png">

    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel='stylesheet' href="https://fonts.googleapis.com/css?family=Roboto Mono">
    <link rel="stylesheet" href="index.css" type="text/css"/>
  </head>
  <body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5ZXCL3S"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <div>
      <img src="icon128x128.png" alt="logo">
      <h1>What should I be doing right now</h1>
    </div>

    <?php
    require_once __DIR__.'/vendor/autoload.php';

    session_start();

    $client = new Google_Client();
    $client->setAuthConfig('client_secret.json');
    $client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY);

    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
      $client->setAccessToken($_SESSION['access_token']);
      $drive = new Google_Service_Drive($client);
      $files = $drive->files->listFiles(array())->getFiles();
      echo json_encode($files);
    } else {
      $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
      header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    }
    ?>

    <noscript>Sorry, your browser does not support JavaScript!</noscript>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/cookie-bar/cookiebar-latest.min.js?theme=altblack"></script>

    <script>
      if ("serviceWorker" in navigator) {
        window.addEventListener("load", function() {
          navigator.serviceWorker.register("worker.js").then(function(registration) {
            console.log("ServiceWorker registration successful with scope: ", registration.scope);
          }, function(err) {
            console.log("ServiceWorker registration failed: ", err);
          });
        });
      }
    </script>
  </body>
</html>