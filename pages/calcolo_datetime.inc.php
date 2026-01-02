<?php

$result_award = gdrcd_query("SELECT last_date_exp FROM personaggio WHERE nome = '".$_SESSION['login']."'");
$last_date_exp = $result_award['last_date_exp'];

//setto gli estremi per il px//
            $now = time(); 
            $this_morning = strtotime('today 6:00'); 
            $last_action = strtotime($last_date_exp);
            $last_mestiere = strtotime($last_date_mestiere);
            
echo "$now <br>";
echo "$this_morning <br>";
echo "$last_action <br>";

if ($now - $last_action >= (24 * 60 * 60) || ($last_action < $this_morning && $now >= $this_morning))
            { echo "sì"; } else { echo "no"; }
