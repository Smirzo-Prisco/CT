<?php
/*carico dati pg*/
$result_pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '". $_SESSION['login'] ."'");
$id_gilda = $result_pg['id_gilda'];
$id_mestiere = $result_pg['id_mestiere'];

/*carico dati pg in mestiere*/
$result_pg_mestiere = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio = '". $_SESSION['login'] ."'");
$con_job = $result_pg_mestiere['conferma_mestiere'];

/*Carico l'elenco dei forum*/
if($_SESSION['admin']!=1){
    
    $result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE tipo != 12 ORDER BY tipo, id_araldo", 'result');
    
    
    //NON GILDATO ma MESTIERATO
	if($id_gilda <= 0 && $id_mestiere != 0 && $con_job != 0){
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=10 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
//master
if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['capomestiere'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capomestiere'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR tipo = 6) AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=10 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //capogilda
elseif ($_SESSION['capogilda'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
}

//capomestiere
elseif ($_SESSION['capomestiere'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capomestiere'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capomestiere'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capomestiere'] == 0 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
}
//moderatore
else if ($_SESSION['moderatore'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['moderatore'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //guida
else if ($_SESSION['guida'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_mestiere') AND (tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
}
}
//Gildato ma NON mestierato
else if($id_gilda!=0 && $id_mestiere==0){
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=10 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');

//master
if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //capogilda
elseif ($_SESSION['capogilda'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} //moderatore
else if ($_SESSION['moderatore'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['moderatore'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //guida
else if ($_SESSION['guida'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
}

}
//GILDATO E MESTIERATO
else if($id_gilda!=0 && $id_mestiere!=0){
     if ($con_job !=0) {
$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');

//master
if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['capomestiere'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capomestiere'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=10 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capomestiere'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (tipo=9 AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //capogilda
elseif ($_SESSION['capogilda'] == 1 AND $_SESSION['capomestiere'] == 0 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['capomestiere'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=8 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} //moderatore
else if ($_SESSION['moderatore'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['moderatore'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //guida
else if ($_SESSION['guida'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['capomestiere'] == 0 AND $_SESSION['moderatore'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (proprietari = '$id_mestiere' AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['guida'] == 1 AND $_SESSION['capomestiere'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['guida'] == 1 AND $_SESSION['capomestiere'] == 1 AND $_SESSION['master'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE ((proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5) OR (tipo=9 AND tipo=6)) AND (tipo !=12 AND tipo !=13 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
}
//NON CONFERMA MESTIERE
                 } else if(($id_gilda == 0 OR $id_gilda < 0) && $id_mestiere != 0 AND $con_job == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=10 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
    
//master
if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //capogilda
elseif ($_SESSION['capogilda'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} //moderatore
else if ($_SESSION['moderatore'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['moderatore'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //guida
else if ($_SESSION['guida'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
}









//NON CONFERMA MESTIERE
                 } else if(($id_gilda != 0) && $id_mestiere != 0 AND $con_job == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1 OR proprietari = '$id_gilda') AND (tipo!=6 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=10 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
    
//master
if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //capogilda
elseif ($_SESSION['capogilda'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} //moderatore
else if ($_SESSION['moderatore'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['moderatore'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //guida
else if ($_SESSION['guida'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) OR (proprietari = '$id_gilda' AND tipo=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
}


    
    
    
    

    }
}
else{
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=10 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');



//master
if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['capogilda'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['master'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //capogilda
elseif ($_SESSION['capogilda'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['moderatore'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['moderatore'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['capogilda'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7) ORDER BY tipo, id_araldo", 'result');
} //moderatore
else if ($_SESSION['moderatore'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['guida'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=9 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} else if ($_SESSION['moderatore'] == 1 AND $_SESSION['guida'] == 1) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
} //guida
else if ($_SESSION['guida'] == 1 AND $_SESSION['master'] == 0 AND $_SESSION['capogilda'] == 0 AND $_SESSION['moderatore'] == 0) {
	$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo!=6 AND tipo!=5 AND tipo !=12 AND tipo !=13 AND tipo !=11 AND tipo !=8 AND tipo !=7 AND tipo !=10) ORDER BY tipo, id_araldo", 'result');
}
}
}

else if ($_SESSION['login'] != 'Lii' AND $_SESSION['login'] != 'Acamar') {
$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE tipo !=13 ORDER BY tipo, id_araldo", 'result');
}

else{
$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo ORDER BY tipo, id_araldo", 'result');
}
$ultimotipo = -1;
?>
<!-- Elenco forum -->
        <table class="customTable">
            <?php
            while($row = gdrcd_query($result, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result, 'free');
            ?>
        </table>
        <?php //Pulsante segna tutto come letto ?>
        <div class="panels_box">
            <div class="form_gioco">
                <form action="main.php?page=forum" method="post">
                        <input type="hidden" name="op" value="readall" />
                        <input type="submit" style="width:170px;" name="dummy" value="Segna tutto come letto" />
                </form>
            </div>
        </div>
