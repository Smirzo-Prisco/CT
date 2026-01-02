<?php
session_start();
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');
$handleDBConnection = gdrcd_connect();

// --- Gestione Eliminazione Singolo Pezzo via AJAX ---
if(isset($_POST['action']) && $_POST['action'] == 'delete_piece') {
    $id = (int)$_POST['id'];
    $nome = gdrcd_filter('in', $_POST['nome']);
    $ruolo = gdrcd_filter('in', $_POST['ruolo']);

    gdrcd_query("DELETE FROM chess WHERE id = $id AND nome = '$nome' AND ruolo = '$ruolo'");
    exit;
}

// Upload immagine sfondo
if(isset($_FILES['img'])){
    $upload_percorso = '../themes/crystal/imgs/chess/';
    $file_tmp = $_FILES['img']['tmp_name'];
    $file_nome = $_SESSION['luogo'].".png";
    move_uploaded_file($file_tmp, $upload_percorso.$file_nome);
}

// Reset pezzi scacchiera
if($_GET['reset']=="1"){
    $id_to_remove = $_GET['id'];
    gdrcd_query("DELETE from chess WHERE id='$id_to_remove'");
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CT Board</title>
    <style>
        body {background-color: #111423;}
        .piece {display:inline-block;width:12.5%;font-size:2em;text-align:center;z-index:100; cursor:pointer; position:absolute;top:15px;color:darkred}
        .piece.b {top:-85px;color:darkslategray; z-index: 100;}
        .blank {color:#FFF;border: 1px dashed black;display:inline-block;width:12.5%;font-size:2em;color:transparent;}
        .g {background-color:transparent;color:transparent}
        .g.blank {color:transparent;}
        @media screen and (min-width: 480px) {
            .chessboard {
                background-repeat: no-repeat;
                height: 500px;
                background-size: cover;
                background-position: center;
                max-width:500px;
                margin:0 auto;
                margin-top: 10px;
            }
            .blank {font-size:3em;}
            .piece {font-size:1em; color: white; top:50px;}
            .piece.b {top:-120px;}
        }
        .chess {
            border:1px dashed #000000;
            border-collapse:collapse;
            padding:5px;
            height: 500px;
            width: 500px;
        }
        .chess td {
            border:1px dashed #000000;
            padding:5px;
        }
        
        .menu_chess {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 20px;
    font-family: Lato, sans-serif;
    color: #ce846f;
}

.menu_chess .row_chess {
    margin: 10px 0;
    text-align: center;
}

.menu_chess button, 
.menu_chess input[type="submit"] {
    background-color: #1f2235;
    color: #ce846f;
    border: 1px solid #ce846f;
    padding: 5px 10px;
    margin: 3px;
    font-size: 12px;
    cursor: pointer;
    border-radius: 3px;
    transition: background 0.3s;
}

.menu_chess button:hover, 
.menu_chess input[type="submit"]:hover {
    background-color: #2c304a;
}

.menu_chess select, 
.menu_chess input[type="text"], 
.menu_chess input[type="file"] {
    background-color: #14172a;
    color: #ce846f;
    border: 1px solid #ce846f;
    padding: 5px;
    font-size: 12px;
    border-radius: 3px;
}

.delete-piece {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #1f2235;
    color: #ce846f;
    border: 1px solid #ce846f;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 12px;
    line-height: 16px;
    text-align: center;
    cursor: pointer;
    padding: 0;
    transition: background 0.3s, color 0.3s;
}

.delete-piece:hover {
    background-color: #2c304a;
    color: #ffffff;
}

    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.0/jquery-ui.min.js"></script>
</head>
<body>

<div class="chessboard">
    <table class="chess">
        <tbody>
        <?php for($i=0; $i<8; $i++) { echo "<tr>"; for($j=0; $j<8; $j++) { echo "<td>&nbsp;</td>"; } echo "</tr>"; } ?>
        </tbody>
    </table>

    <?php
    $divs = gdrcd_query('SELECT * FROM chess WHERE id='.$_GET['id'].'', "result");
while($div = gdrcd_query($divs, 'fetch')) {
    $ruolo = $div['ruolo'];
    $nome = $div['nome'];
    $left = (int)$div['left_cd'];
    $top = (int)$div['top_cd'];

    // Se è un PG, prendo l'avatar dal DB
    if ($ruolo == 'pg') {
        $avatar = gdrcd_query("SELECT url_img_chat FROM personaggio WHERE nome = '".gdrcd_filter('in', $nome)."'");
        $img_src = $avatar['url_img_chat'] ?: '../themes/crystal/imgs/chess/magia.png'; // fallback se nullo
    } else {
        $img_src = "../themes/crystal/imgs/chess/$ruolo.png";
    }

    echo "
    <div class='piece ui-draggable ui-draggable-handle' data-nome='$nome' data-ruolo='$ruolo' style='left:$left; top:$top;'>
        <img src='$img_src' style='width:50px;height:50px;'>
        <br><span>$nome</span>
        <button class='delete-piece'>X</button>
    </div>
    ";
}
    ?>
</div>

<?php if($_SESSION['master']==1 || $_SESSION['admin']==1 || $_SESSION['moderatore']==1) { ?>
<div class="menu_chess">

   <!-- Prima riga: Scelta PG o PNG -->
    <div class="row_chess">
        <label><input type="radio" name="tipo_personaggio" value="pg" checked> PG in Chat</label>
        <label><input type="radio" name="tipo_personaggio" value="png"> PNG Manuale</label>

        <div id="sezione_pg">
            <select name="pg_in_chat" id="pg_in_chat">
                <option value="">-- Seleziona PG --</option>
                <?php
                $luogo = $_SESSION['luogo'];
                $pg_presenti = gdrcd_query("
                    SELECT DISTINCT p.nome, p.url_img_chat
                    FROM chat c
                    INNER JOIN personaggio p ON c.mittente = p.nome
                    WHERE c.stanza = '".$luogo."'
                      AND c.tipo = 'P'
                ", 'result');

                while($pg = gdrcd_query($pg_presenti, 'fetch')) {
                    echo '<option value="'.$pg['nome'].'" data-img="'.$pg['url_img_chat'].'">'.$pg['nome'].'</option>';
                }
                ?>
            </select>
            <button id="add_pg_btn">Aggiungi PG</button>
        </div>

        <div id="sezione_png" style="display:none;">
            <input name="nome_png" id="nome_png" type="text" placeholder="Nome PNG">
            <button id="add_png_btn">Aggiungi PNG</button>
        </div>
    </div>

    <!-- Seconda riga: Bottoni -->
    <div class="row_chess">
        <button class="save-img">SALVA</button>
        <button onclick='location.reload(false);'>Aggiorna</button>
        <form action="chess.inc.php?id=<?php echo $luogo?>&reset=1" method="post" style="display:inline;">
            <input type="submit" name="reset" value="Reset" />
        </form>
    </div>

    <!-- Terza riga: Upload immagine -->
    <div class="row_chess">
        <form action="chess.inc.php?id=<?php echo $luogo?>" method="post" enctype="multipart/form-data">
            <input name="img" type="file" />
            <input type="submit" name="carica" value="Carica" />
        </form>
    </div>

</div>
<?php } ?>

<script>
$(document).ready(function(){

    // Gestione eliminazione singolo pezzo
$(".chessboard").on('click', '.delete-piece', function(e) {
    e.stopPropagation();  // Evita conflitti con draggable
    var pezzo = $(this).closest('.piece');
    var nome = pezzo.data('nome');
    var ruolo = pezzo.data('ruolo');
    var url = window.location.search.substring(1);
    var id_stanza = url.split('=')[1];

    if(confirm("Vuoi davvero eliminare " + nome + " dalla scacchiera?")) {
        $.ajax({
            type: "POST",
            url: "chess.inc.php",
            data: { action: 'delete_piece', id: id_stanza, nome: nome, ruolo: ruolo },
            success: function() {
                pezzo.remove();  // Rimuove il pezzo dalla scacchiera
            },
            error: function() {
                alert("Errore durante l'eliminazione. Riprova.");
            }
        });
    }
});


    $(".piece").draggable();

    $('input[name="tipo_personaggio"]').change(function(){
        if($(this).val() == 'pg') {
            $('#sezione_pg').show();
            $('#sezione_png').hide();
        } else {
            $('#sezione_pg').hide();
            $('#sezione_png').show();
        }
    });

    $("#add_pg_btn").click(function() {
    var selected = $("#pg_in_chat option:selected");
    var nome_pg = selected.val();
    var img_pg = selected.data('img');
   
    if(nome_pg !== "") {
        $(".chessboard").append(
    "<div class='piece ui-draggable ui-draggable-handle' data-nome='"+nome_pg+"' data-ruolo='pg'>" +
    "<img src='"+img_pg+"' style='width:50px;height:50px;'>" +
    "<br><span>"+nome_pg+"</span>" +
    "<button class='delete-piece' style='position:absolute; top:0; right:0; background:red; color:white; border:none; cursor:pointer; padding:2px;'>X</button>" +
    "</div>"
);
        $(".piece").draggable();
    } else {
        alert("Seleziona un personaggio!");
    }
});


    $("#add_png_btn").click(function() {
        var nome_png = $("#nome_png").val();
        if(nome_png === "") {
            alert("Inserisci il nome del PNG!");
            return;
        }
        var immagini_png = ['capofamiglia', 'cattivo', 'scagnozzo', 'magia', 'corpoacorpo'];
        var random = immagini_png[Math.floor(Math.random() * immagini_png.length)];
        $(".chessboard").append(
    "<div class='piece ui-draggable ui-draggable-handle' data-nome='"+nome_png+"' data-ruolo='"+random+"'>" +
    "<img src='../themes/crystal/imgs/chess/"+random+".png' name='"+random+"' style='width:50px;height:50px;'>" +
    "<br><span>"+nome_png+"</span>" +
    "<button class='delete-piece' style='position:absolute; top:0; right:0; background:red; color:white; border:none; cursor:pointer; padding:2px;'>X</button>" +
    "</div>"
);

        $(".piece").draggable();
    });

    $(".save-img").click(function() {
        var board = $(".chessboard");
        board.children("div").each(function() { 
            var left = $(this).css('left');
            var top = $(this).css('top'); 
            var role = $(this).find('img').attr('name') || 'pg';
            var name = $(this).find('span').text();
            var url = window.location.search.substring(1);
            var params = url.split('=');
            $.ajax({
                type: "POST",
                url: "storerole.php",
                data: {id:params[1],left:left, top:top, role:role, name:name}
            });
        }); 
    });

    // Gestione sfondo con cache buster
    var url = window.location.search.substring(1);
    var params = url.split('=');
    $('.chessboard').css("background","url(../themes/crystal/imgs/chess/"+params[1]+".png?"+new Date().getTime()+")");
});


$(document).ready(function(){

    var modificheEffettuate = false;

    // Ogni volta che sposti un pezzo, segna la modifica
    $(".chessboard").on("dragstop", ".piece", function() {
        modificheEffettuate = true;
    });

    // Quando aggiungi un PG
    $("#add_pg_btn").click(function() {
        modificheEffettuate = true;
    });

    // Quando aggiungi un PNG
    $("#add_png_btn").click(function() {
        modificheEffettuate = true;
    });

    // Quando clicchi su SALVA, resetti la variabile
    $(".save-img").click(function() {
        modificheEffettuate = false;
    });

    // Prima di uscire dalla pagina
    window.addEventListener("beforeunload", function (e) {
        if(modificheEffettuate) {
            e.preventDefault();
            e.returnValue = '';  // Alcuni browser mostrano un messaggio di default
        }
    });

});

</script>

</body>
</html>
