<?php
  if (array_key_exists ("redirect", $_GET) && $_GET['redirect'] != "") {
      header ("location: " . $_GET['redirect']);  // redirect 파라미터 값을 검증 없이 그대로 Location 헤더에 삽입
      exit;
  }
  
  http_response_code (500);
  ?>
  <p>Missing redirect target.</p>
  <?php
  exit;
?>
