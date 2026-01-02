<link rel="stylesheet" href="../themes/crystal/anagrafe.css">



<div class="pagina_servizi_prenotazioni">
<!-- Box principale -->
<div class="page_body">  


<table class="customTable">
    <tr class="second_header" style="filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <td>
                                        <div>
                                        ANAGRAFE
                                        </div>
                                    </td>
    </tr>
    </table>
    <br><br>

<a href='main.php?page=anagrafe&op=A'>[A]</a>
		<a href='main.php?page=anagrafe&op=B'>[B]</a>
		<a href='main.php?page=anagrafe&op=C'>[C]</a>
		<a href='main.php?page=anagrafe&op=D'>[D]</a>
		<a href='main.php?page=anagrafe&op=E'>[E]</a>
		<a href='main.php?page=anagrafe&op=F'>[F]</a>
		<a href='main.php?page=anagrafe&op=G'>[G]</a>
		<a href='main.php?page=anagrafe&op=H'>[H]</a>
		<a href='main.php?page=anagrafe&op=I'>[I]</a>
		<a href='main.php?page=anagrafe&op=J'>[J]</a>
		<a href='main.php?page=anagrafe&op=K'>[K]</a>
		<a href='main.php?page=anagrafe&op=L'>[L]</a>
		<a href='main.php?page=anagrafe&op=M'>[M]</a>
		<a href='main.php?page=anagrafe&op=N'>[N]</a>
		<a href='main.php?page=anagrafe&op=O'>[O]</a>
		<a href='main.php?page=anagrafe&op=P'>[P]</a>
		<a href='main.php?page=anagrafe&op=Q'>[Q]</a>
		<a href='main.php?page=anagrafe&op=R'>[R]</a>
		<a href='main.php?page=anagrafe&op=S'>[S]</a>
		<a href='main.php?page=anagrafe&op=T'>[T]</a>
		<a href='main.php?page=anagrafe&op=U'>[U]</a>
		<a href='main.php?page=anagrafe&op=V'>[V]</a>
		<a href='main.php?page=anagrafe&op=W'>[W]</a>
		<a href='main.php?page=anagrafe&op=X'>[X]</a>
		<a href='main.php?page=anagrafe&op=Y'>[Y]</a>
		<a href='main.php?page=anagrafe&op=Z'>[Z]</a>

        <!--
		Ora dovrebbe esserci il controllo e la stampa della lettera 
		selezionata e dei nomi da visualizzare in base alla scelta fatta.
		-->
        
        <?php /* inizio del codice php */

		$op=gdrcd_filter_get($_REQUEST['op']);
        
        if(empty($op)==FALSE){ 
        $query = "SELECT  personaggio.nome, personaggio.*, razza.*, clgpersonaggioruolo.*, clgpersonaggiomestiere.* FROM personaggio 
                  
                  LEFT JOIN clgpersonaggiomestiere ON personaggio.id_ruolo_mestiere = clgpersonaggiomestiere.id_ruolo        
                  LEFT JOIN clgpersonaggioruolo ON personaggio.id_ruolo_gilda = clgpersonaggioruolo.id_ruolo
                  LEFT JOIN razza ON personaggio.id_razza = razza.id_razza
                  
                  WHERE personaggio.nome LIKE '".$op."%' && (personaggio.esilio < NOW() || personaggio.esilio IS NULL) GROUP BY personaggio.nome ORDER BY personaggio.nome ASC";
                  
                  
        #$query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.online_status, personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.posizione, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, mappa.stanza_apparente, mappa.nome as luogo, mappa_click.nome as mappa FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN mappa_click ON personaggio.ultima_mappa = mappa_click.id_click LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE personaggio.nome LIKE '".$op."%' ORDER BY nome ASC";
        #$query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.online_status, personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.posizione, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, mappa.stanza_apparente, mappa.nome as luogo, mappa_click.nome as mappa FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN mappa_click ON personaggio.ultima_mappa = mappa_click.id_click LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE personaggio.nome LIKE '".$op."%' ORDER BY personaggio.nome ASC";
   
/** * eseguo la query salvata nella var $query
*/
		$result=gdrcd_query($query, 'result');?>
        
        <!-- Dovrebbe darmi il risultato sperato -->
        <br><br>
        <table class="customTable">
  <thead>
    <tr>
      <th colspan="8">LISTA UTENTI</th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>NOME E COGNOME</td>
      <td>FAMIGLIA</td>
      <td>LAVORO</td>
      <td>RAZZA</td>  
      <td>ULTIMO ACCESSO</td>
    </tr>
    
<?php
/** * Eseguo un ciclo while 
*/

while($row=gdrcd_query($result, 'fetch')){?>
<tr class="presente">
<td><a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out',$row['nome']);?>">
				  		<?php echo gdrcd_filter('out',$row['nome']).' '.gdrcd_filter('out',$row['cognome']); ?>
				  		</a>
</td>
<td>
<!-- famiglia -->

<?php $id_famiglia = $row['id_ruolo_gilda'];
      $gilda = "SELECT * FROM ruolo WHERE id_ruolo = '$id_famiglia'";
      $row_gilda = gdrcd_query($gilda);
      echo '<img width="25" height="25" src="imgs/guilds/'.$row_gilda['immagine'].'"/>';		
 ?>		  		
</td>
<td>
<!-- mstiere -->

<?php $id_mestiere = $row['id_ruolo_mestiere'];
      $mestiere = "SELECT * FROM ruolo_mestiere WHERE id_ruolo = '$id_mestiere'";
      $row_mestiere = gdrcd_query($mestiere);
      echo '<img width="25" height="25" src="imgs/mestieri/'.$row_mestiere['immagine'].'"/>';		
 ?>		  		
</td>

<td>
<!-- razza -->

<?php $id_razza = $row['id_razza'];
      $razza = "SELECT * FROM razza WHERE id_razza = '$id_razza'";
      $row_razza = gdrcd_query($razza);
      echo '<img width="25" height="25" src="imgs/races/'.$row_razza['immagine'].'"/>';		
 ?>		  		
</td>

<td>
<?php 
echo gdrcd_format_date($row['ultimo_refresh']);
 ?>
				  		
</td>
<?php       

            }
?>
</tr>
</tbody>
</table>
<?php } ?>
  <div class="panels_link">
               <br><br> <a href="main.php?page=uffici">Torna indietro</a>
            </div>
</div>
</div>