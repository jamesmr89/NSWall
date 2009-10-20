#!/bin/php
<?php 
/*
	$Id: firewall_rules.php,v 1.4 2009/04/20 06:14:15 jrecords Exp $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

$pgtitle = array("Firewall", "Rules");
require("guiconfig.inc");

if (isset($_POST['rulesetid']) || isset($_GET['rulesetid'])) {

 	$id = $_GET['rulesetid'];
        if (isset($_POST['rulesetid']))
                $id = $_POST['rulesetid'];

        $rulesetid = $_GET['rulesetid'];
        if (isset($_POST['rulesetid']))
                $rulesetid = $_POST['rulesetid'];


	if (!is_array($config['grouppolicies']['ruleset'][$id]['rule'])) {
       		$config['grouppolicies']['ruleset'][$id]['rule'] = array();
	}

	filter_rulesets_sort();

	$a_ruleset = &$config['grouppolicies']['ruleset'];

	if (isset($id) && $a_ruleset[$id]) {
        	$pconfig['name'] = $a_ruleset[$id]['name'];
        	$pconfig['descr'] = $a_ruleset[$id]['descr'];
        
		filter_rulesets_rules_sort($id);

		$a_filter = $a_ruleset[$id]['rule'];

        }

	$if = $_GET['if'];

        if ($_POST['if'])
                $if = $_POST['if'];

	$iflist = array("wan" => "WAN","lan" => "LAN");

	if ($config['pptpd']['mode'] == "server")
 	       $iflist['pptp'] = "PPTP VPN";

	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
        	if ($config['interfaces']['opt' . $i]['wireless']['ifmode'] != 'lanbridge' && $config['interfaces']['wireless']['ifmode'] != 'dmzbridge')
        	        $iflist['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
	}


} else {

	if (!is_array($config['filter']['rule'])) {
		$config['filter']['rule'] = array();
	}
	filter_rules_sort();
	$a_filter = &$config['filter']['rule'];

	$if = $_GET['if'];

	if ($_POST['if'])
		$if = $_POST['if'];
	
	$iflist = array("wan" => "WAN","lan" => "LAN");

	if ($config['pptpd']['mode'] == "server")
		$iflist['pptp'] = "PPTP VPN";

	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		if ($config['interfaces']['opt' . $i]['wireless']['ifmode'] != 'lanbridge' && $config['interfaces']['wireless']['ifmode'] != 'dmzbridge')
			$iflist['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
	}
}


if (!$if || !isset($iflist[$if]))
	$if = "wan";

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = filter_configure();
			config_unlock();
			push_config('rules');
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_natconfdirty_path))
				unlink($d_natconfdirty_path);
			if (file_exists($d_filterconfdirty_path))
				unlink($d_filterconfdirty_path);
		}
	}
	header("Location: firewall_rules.php?if={$if}");
}

if (isset($_POST['del_x'])) {
	/* delete selected rules */
	if (is_array($_POST['rule']) && count($_POST['rule'])) {
		foreach ($_POST['rule'] as $rulei) {
	                if (isset($rulesetid)) {
                                  unset($config['grouppolicies']['ruleset'][$id]['rule'][$rulei]);
                        } else {
                                  unset($a_filter[$rulei]);
                        }
		}
		write_config();
		if (isset($rulesetid)) {
			header("Location: firewall_rules.php?if={$if}&rulesetid={$rulesetid}");
		} else {
			touch($d_filterconfdirty_path);
			header("Location: firewall_rules.php?if={$if}");
		}
		exit;
	}
} else if ($_GET['act'] == "toggle") {
	if ($a_filter[$_GET['id']]) {
		$a_filter[$_GET['id']]['disabled'] = !isset($a_filter[$_GET['id']]['disabled']);
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if={$if}");
		exit;
	}
} else {
	/* yuck - IE won't send value attributes for image buttons, while Mozilla does - 
	   so we use .x/.y to fine move button clicks instead... */
	unset($movebtn);
	foreach ($_POST as $pn => $pd) {
		if (preg_match("/move_(\d+)_x/", $pn, $matches)) {
			$movebtn = $matches[1];
			break;
		}
	}
	/* move selected rules before this rule */
	if (isset($movebtn) && is_array($_POST['rule']) && count($_POST['rule'])) {
		$a_filter_new = array();
		
		/* copy all rules < $movebtn and not selected */
		for ($i = 0; $i < $movebtn; $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		/* copy all selected rules */
		for ($i = 0; $i < count($a_filter); $i++) {
			if ($i == $movebtn)
				continue;
			if (in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		/* copy $movebtn rule */
		if ($movebtn < count($a_filter))
			$a_filter_new[] = $a_filter[$movebtn];
		
		/* copy all rules > $movebtn and not selected */
		for ($i = $movebtn+1; $i < count($a_filter); $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		$a_filter = $a_filter_new;
		write_config();
	
		if (isset($rulesetid)) {
                        header("Location: firewall_rules.php?if={$if}&rulesetid={$rulesetid}");
                } else {
                        touch($d_filterconfdirty_path);
                        header("Location: firewall_rules.php?if={$if}");
                }
		exit;
	}
}

?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
function fr_toggle(id) {
	var checkbox = document.getElementById('frc' + id);
	checkbox.checked = !checkbox.checked;
	fr_bgcolor(id);
}
function fr_bgcolor(id) {
	var row = document.getElementById('fr' + id);
	var checkbox = document.getElementById('frc' + id);
	var cells = row.getElementsByTagName("td");
	
	for (i = 2; i <= 6; i++) {
		cells[i].style.backgroundColor = checkbox.checked ? "#FFFFBB" : "#FFFFFF";
	}
	cells[7].style.backgroundColor = checkbox.checked ? "#FFFFBB" : "#D9DEE8";
}
function fr_insline(id, on) {
	var row = document.getElementById('fr' + id);
	var prevrow;
	if (id != 0) {
		prevrow = document.getElementById('fr' + (id-1));
	} else {
		if (<?php if (($if == "wan") && isset($config['interfaces']['wan']['blockpriv'])) echo "true"; else echo "false"; ?>) {
			prevrow = document.getElementById('frrfc1918');
		} else {
			prevrow = document.getElementById('frheader');
		}
	}
	
	var cells = row.getElementsByTagName("td");
	var prevcells = prevrow.getElementsByTagName("td");
	
	for (i = 2; i <= 7; i++) {
		if (on) {
			prevcells[i].style.borderBottom = "3px solid #999999";
			prevcells[i].style.paddingBottom = (id != 0) ? 2 : 3;
		} else {
			prevcells[i].style.borderBottomWidth = "1px";
			prevcells[i].style.paddingBottom = (id != 0) ? 4 : 5;
		}
	}
	
	for (i = 2; i <= 7; i++) {
		if (on) {
			cells[i].style.borderTop = "2px solid #999999";
			cells[i].style.paddingTop = 2;
		} else {
			cells[i].style.borderTopWidth = 0;
			cells[i].style.paddingTop = 4;
		}
	}
}
// -->
</script>
<?php if (isset($rulesetid)): ?>
             <form action="firewall_rulesets.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Name</td>
                  <td width="78%" class="vtable">
                    <input name="name" type="text" class="formfld" id="name" size="20" value="<?=htmlspecialchars($pconfig['name']);?>">
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable">
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Interface List</td>
                  <td width="78%" class="vtable">
                    <input name="iflist" type="text" class="formfld" id="iflist" size="40" value="<?=htmlspecialchars($pconfig['iflist']);?>">
                    <br> <span class="vexpl">Interfaces to be used in this Ruleset</span></td>
                </tr>
                <tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id)): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                    <input name="rulesetid" type="hidden" value="<?=$rulesetid;?>">
                  </td>
                </tr>
                </tr>
              </table>
             </form>
<?php endif; ?>


<form action="firewall_rules.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_filterconfdirty_path)): ?><p>
<?php print_info_box_np("The firewall rule configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php $i = 0; foreach ($iflist as $ifent => $ifname):
        if ($ifent == $if): ?>
    <li class="tabact"><?=htmlspecialchars($ifname);?></li>
<?php else: ?>
        <?php if (isset($rulesetid)): ?>
    <li class="<?php if ($i == 0) echo "tabinact1"; else echo "tabinact";?>"><a href="firewall_rules.php?if=<?=$ifent;?>&rulesetid=<?=$id;?>"><?=htmlspecialchars($ifname);?></a></li>
        <?php else: ?>
    <li class="<?php if ($i == 0) echo "tabinact1"; else echo "tabinact";?>"><a href="firewall_rules.php?if=<?=$ifent;?>"><?=htmlspecialchars($ifname);?></a></li>
        <?php endif; ?>
<?php endif; ?>
<?php $i++; endforeach; ?>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr id="frheader">
                  <td width="3%" class="list">&nbsp;</td>
                  <td width="5%" class="list">&nbsp;</td>
                  <td width="22%" class="listhdr">Name</td>
		  <td width="60%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
				</tr>
				<?php $nrules = 0; for ($i = 0; isset($a_filter[$i]); $i++):
					$filterent = $a_filter[$i];
					if ($filterent['interface'] != $if)
						continue;
				?>
                <tr valign="top" id="fr<?=$nrules;?>">
                  <td class="listt"><input type="checkbox" id="frc<?=$nrules;?>" name="rule[]" value="<?=$i;?>" onClick="fr_bgcolor('<?=$nrules;?>')" style="margin: 0; padding: 0; width: 15px; height: 15px;"></td>
                  <td class="listt" align="center">
				  <?php if ($filterent['type'] == "block")
				  			$iconfn = "block";
						else if ($filterent['type'] == "reject") {
							if ($filterent['protocol'] == "tcp" || $filterent['protocol'] == "udp")
								$iconfn = "reject";
							else
								$iconfn = "block";
						} else
							$iconfn = "pass";
						if (isset($filterent['disabled'])) {
							$textss = "<span class=\"gray\">";
							$textse = "</span>";
							$iconfn .= "_d";
						} else {
							$textss = $textse = "";
						}
				  ?>
				  <a href="?if=<?=$if;?>&act=toggle&id=<?=$i;?>"><img src="images/<?=$iconfn;?>.gif" width="11" height="11" border="0" title="click to toggle enabled/disabled status"></a>
				  <?php if (isset($filterent['log'])):
							$iconfn = "log_s";
						if (isset($filterent['disabled']))
							$iconfn .= "_d";
				  	?>
				  <br><img src="images/<?=$iconfn;?>.gif" width="11" height="15" border="0">
				  <?php endif; ?>
				  </td>
                  <td class="listlr" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?php if (isset($filterent['name'])) echo strtoupper($filterent['name']); else echo "*"; ?><?=$textse;?>
                  </td>
                  <td class="listbg" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?=htmlspecialchars($filterent['descr']);?>&nbsp;<?=$textse;?>
                  </td>
                  <td valign="middle" nowrap class="list">
				    <table border="0" cellspacing="0" cellpadding="1">
					<tr>
					  <td><input name="move_<?=$i;?>" type="image" src="images/left.gif" width="17" height="17" title="move selected rules before this rule" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"></td>
				
		 <?php if (isset($rulesetid)): ?>
        		<td><a href="firewall_rules_edittabs.php?id=<?=$i;?>&rulesetid=<?=$rulesetid;?>""><img src="images/e.gif" title="edit rule" width="17" height="17" border="0"></a></td>
		<?php else: ?>
        		<td><a href="firewall_rules_edittabs.php?id=<?=$i;?>"><img src="images/e.gif" title="edit rule" width="17" height="17" border="0"></a></td>
		<?php endif; ?>
					</tr>
					<tr>
					  <td align="center" valign="middle"></td>
					<?php if (isset($rulesetid)): ?>  
						<td><a href="firewall_rules_edittabs.php?dup=<?=$i;?>&rulesetid=<?=$rulesetid;?>"><img src="images/plus.gif" title="add a new rule based on this one" width="17" height="17" border="0"></a></td>
					<?php else: ?>
						<td><a href="firewall_rules_edittabs.php?dup=<?=$i;?>"><img src="images/plus.gif" title="add a new rule based on this one" width="17" height="17" border="0"></a></td>
					<?php endif; ?>
					</tr>
					</table>
				  </td>
				</tr>
			  <?php $nrules++; endfor; ?>
			  <?php if ($nrules == 0): ?>
              <td class="listt"></td>
			  <td class="listt"></td>
			  <td class="listlr" colspan="6" align="center" valign="middle">
			  <span class="gray">
			  No rules are currently defined for this interface.<br>
			  All incoming connections on this interface will be blocked until you add pass rules.<br><br>
			  Click the
			  <?php if (isset($rulesetid)): ?>
                         	<a href="firewall_rules_edittabs.php?if=<?=$if;?>&rulesetid=<?=$rulesetid;?>"> 
                          <?php else: ?>
                          	<a href="firewall_rules_edittabs.php?if=<?=$if;?>">
                          <?php endif; ?>
			  <img src="images/plus.gif" title="add new rule" border="0" width="17" height="17" align="absmiddle"></a> button to add a new rule.</span>
			  </td>
			  <?php endif; ?>
                <tr id="fr<?=$nrules;?>"> 
                  <td class="list"></td>
                  <td class="list"></td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">
				    <table border="0" cellspacing="0" cellpadding="1">
					<tr>
				      <td>
					  <?php if ($nrules == 0): ?><img src="images/left_d.gif" width="17" height="17" title="move selected rules to end" border="0"><?php else: ?><input name="move_<?=$i;?>" type="image" src="images/left.gif" width="17" height="17" title="move selected rules to end" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"><?php endif; ?></td>
					  <td></td>
				    </tr>
					<tr>
					<td>
					<?php if ($nrules == 0): ?>
						<img src="images/x_d.gif" width="17" height="17" title="delete selected rules" border="0">
					<?php else: ?><input name="del" type="image" src="images/x.gif" width="17" height="17" title="delete selected rules" onclick="return confirm('Do you really want to delete the selected rules?')">
					<?php endif; ?>
					</td>
					<?php if (isset($rulesetid)): ?>
        					<td><a href="firewall_rules_edittabs.php?if=<?=$if;?>&rulesetid=<?=$rulesetid
;?>"><img src="images/plus.gif" title="add new rule" width="17" height="17" border="0"></a></td>
					<?php else: ?>
        					<td><a href="firewall_rules_edittabs.php?if=<?=$if;?>"><img src="images/plus.gif" title="add new rule" width="17" height="17" border="0"></a></td>
					<?php endif; ?>
					</tr>
				    </table>
				  </td>
				</tr>
              </table>
			  <table border="0" cellspacing="0" cellpadding="0">
                <tr> 
                  <td width="16"><img src="images/pass.gif" width="11" height="11"></td>
                  <td>pass</td>
                  <td width="14"></td>
                  <td width="16"><img src="images/block.gif" width="11" height="11"></td>
                  <td>block</td>
                  <td width="14"></td>
                  <td width="16"><img src="images/log.gif" width="11" height="11"></td>
                  <td>log</td>
                </tr>
                <tr>
                  <td colspan="5" height="4"></td>
                </tr>
              </table>
    </td>
  </tr>
</table><br>
  <strong><span class="red">Hint:<br>
  </span></strong>Rules are evaluated on a first-match basis (i.e. 
  the action of the first rule to match a packet will be executed). 
  This means that if you use block rules, you'll have to pay attention 
  to the rule order. Everything that isn't explicitly passed is blocked 
  by default.
  <input type="hidden" name="if" value="<?=$if;?>">
  <?php if (isset($rulesetid)): ?>
  	<input type="hidden" name="rulesetid" value="<?=$rulesetid;?>"> 
  <?php endif; ?>

</form>
<?php include("fend.inc"); ?>
