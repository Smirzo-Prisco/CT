<?php
include_once('../header.inc.php');
?>

<div class="pagina_messages_center">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']); ?></h2>
    </div>
    <div class="page_body">
		<?php
		
		if((isset($_POST['op']))=== true ){	
			include ('msg/msg_do.inc.php');
		
		} else {	
							
			switch(gdrcd_filter_get($_GET['op'])) {
				case 'create': 
					include ('msg/msg_new1.inc.php');
					break;
                case 'create_single': 
					include ('msg/msg_new_single.inc.php');
					break;
				case 'read':
				case 'reply':
					include ('msg/msg_read.inc.php');
					break;
				case false: 
				default:
					include ('mex_privati/index.inc.php');
					break;
			}
			
		}
		
        ?>
    </div><!-- page_body -->
</div><!-- Pagina -->
