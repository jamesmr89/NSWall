#!/bin/php
<?php 

$pgtitle = array("Services", "Carp");
require("guiconfig.inc");

?>

<script type="text/javascript">    
     $(document).ready(function() {  
        clearInterval(refreshId);  
        $("#carptabs").tabs({ cache: false });  
     });  
    
</script>    

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="carptabs">  
    	<ul>
		<li><a href="firewall_carp.php">Configuration</a></li>  
       	<li><a href="firewall_carp_vid.php">Vertual Host ID's</a></li>  
       	<li><a href="firewall_pfsync.php">PFSync</a></li>  
       	<li><a href="firewall_carp_sync_hosts.php">Config Sync Hosts</a></li>
     	</ul>  
	</div>
</div>