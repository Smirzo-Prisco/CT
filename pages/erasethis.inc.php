<?php
      $result_exp = gdrcd_query("SELECT count_exp, last_date_exp, last_date_mestiere from personaggio WHERE nome = '".$_SESSION['login']."'");
      $last_date_exp = $result_exp['last_date_exp'];
      
//setto gli estremi per il px//
          $last_power = 'yesterday 7:00:01';
          $current = new DateTime('now');  //('now') 
          $new_day = new DateTime('today 7:00');  // può essere passato o futuro
          $date_power = new DateTime($last_date_exp); // setto formato
          
          if($current < $new_day){ // prima delle 7
          $new_day->modify('-1day'); // setto il range
          }
          
          $check_actions = gdrcd_query("SELECT * FROM chat WHERE stanza = 400 && mittente = '". $_SESSION['login'] ."' && (tipo = 'P' || tipo = 'M') && CHAR_LENGTH(testo) > 99 && DATE_ADD(ora, INTERVAL 12 HOUR)  >= NOW()", 'result');
          if((gdrcd_query($check_actions, 'num_rows') == 4) && ($date_power < $new_day)) {
          
          echo 'ok';
          
          } else {
          
          echo 'no';
          
          }