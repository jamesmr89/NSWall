<?php
/* $Id: firewall_virtual_ip.php,v 1.1 2009/04/20 06:56:54 jrecords Exp $ */
/*
 firewall_virtual_ip.php
 part of pfSense (http://www.pfsense.com/)

 Copyright (C) 2005 Bill Marquette <bill.marquette@gmail.com>.
 All rights reserved.

 Includes code from m0n0wall which is:
 Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
 All rights reserved.

 Includes code from pfSense which is:
 Copyright (C) 2004-2005 Scott Ullrich <geekgod@pfsense.com>.
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

$pgtitle = array(gettext("Firewall"),gettext("Virtual IP Addresses"));

require("guiconfig.inc");

if (!is_array($config['virtualip']['vip'])) {
	$config['virtualip']['vip'] = array();
}
$a_vip = &$config['virtualip']['vip'];

if ($_POST) {
	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = services_proxyarp_configure();
			/* Bring up any configured CARP interfaces */
			/* setup   interfaces */
			reset_carp();
			$retval |= filter_configure();
			config_unlock();
			interfaces_ipalias_configure();

			/* reset carp states */
			reset_carp();
			interfaces_carp_configure();
			interfaces_carp_bring_up_final();
				
		}
		$savemsg = get_std_save_message($retval);
		unlink_if_exists($d_vipconfdirty_path);
	}
}

if ($_GET['act'] == "del") {
	if ($a_vip[$_GET['id']]) {
		/* make sure no inbound NAT mappings reference this entry */
		if (is_array($config['nat']['rule'])) {
			foreach ($config['nat']['rule'] as $rule) {
				if($rule['external-address'] <> "") {
					if ($rule['external-address'] == $a_vip[$_GET['id']]['ipaddr']) {
						$input_errors[] = gettext("This entry cannot be deleted because it is still referenced by at least one NAT mapping.");
						break;
					}
				}
			}
		}

		/* if this is an AJAX caller then handle via JSON */
		if(isAjax() && is_array($input_errors)) {
			input_errors2Ajax($input_errors);
			exit;
		}

		if (!$input_errors) {
			unset($a_vip[$_GET['id']]);
			write_config();
			touch($d_vipconfdirty_path);
			pfSenseHeader("firewall_virtual_ip.php");
			exit;
		}
	}
}

/* if ajax is calling, give them an update message */
if(isAjax())
print_info_box_np($savemsg);

include("head.inc");
/* put your custom HTML head content here        */
/* using some of the $pfSenseHead function calls */
echo $pfSenseHead->getHTML();

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC"
	onload="<?= $jsevents["body"]["onload"] ?>">
<?php include("fbegin.inc"); ?>
<form action="firewall_virtual_ip.php" method="post">
<div id="inputerrors"></div>
<?php
if ($input_errors)
print_input_errors($input_errors);
else
if ($savemsg)
print_info_box($savemsg);
else
if (file_exists($d_vipconfdirty_path))
print_info_box_np("The VIP configuration has been changed.<br>You must apply the changes in order for them to take effect.");
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl"><?php
		/* active tabs */
		$tab_array = array();
		$tab_array[] = array(gettext("Virtual IPs"), true, "firewall_virtual_ip.php");
		$tab_array[] = array(gettext("CARP Settings"), false, "pkg_edit.php?xml=carp_settings.xml&amp;id=0");
		$tab_array[] = array(gettext("CARP Sync Hosts"), false, "pkg.php?xml=carp_sync_hosts.xml");
		display_top_tabs($tab_array);
		?></td>
	</tr>
	<tr>
		<td>
		<div id="mainarea">
		<table class="tabcont" width="100%" border="0" cellpadding="0"
			cellspacing="0">
			<tr>
				<td width="30%" class="listhdrr"><?=gettext("Virtual IP address");?></td>
				<td width="10%" class="listhdrr"><?=gettext("Type");?></td>
				<td width="40%" class="listhdr"><?=gettext("Description");?></td>
				<td width="10%" class="list"></td>
			</tr>
			<?php $i = 0; foreach ($a_vip as $vipent): ?>
			<?php if($vipent['subnet'] <> "" or $vipent['range'] <> "" or
			$vipent['subnet_bits'] <> "" or $vipent['range']['from'] <> "" or $vipent['mode'] == "carpdev-dhcp"): ?>
			<tr>
				<td class="listlr"
					ondblclick="document.location='firewall_virtual_ip_edit.php?id=<?=$i;?>';">
					<?php	if (($vipent['type'] == "single") || ($vipent['type'] == "network"))
					if($vipent['subnet_bits'])
					echo "{$vipent['subnet']}/{$vipent['subnet_bits']}";
					if ($vipent['type'] == "range")
					echo "{$vipent['range']['from']}-{$vipent['range']['to']}";
					?> <?php if($vipent['mode'] == "carpdev-dhcp") echo "DHCP"; ?> <?php if($vipent['mode'] == "carp" or $vipent['mode'] == "carpdev-dhcp") echo " (vhid {$vipent['vhid']})"; ?>
				</td>
				<td class="listlr" align="center"
					ondblclick="document.location='firewall_virtual_ip_edit.php?id=<?=$i;?>';">
					<?
					if($vipent['mode'] == "proxyarp")
					echo "<img src='./themes/".$g['theme']."/images/icons/icon_parp.gif' title='".gettext("Proxy ARP")."'>";
					else if($vipent['mode'] == "carp"  or $vipent['mode'] == "carpdev-dhcp")
					echo "<img src='./themes/".$g['theme']."/images/icons/icon_carp.gif' title='".gettext("CARP")."'>";
					else if($vipent['mode'] == "freebsdalias")
					echo "<img src='./themes/".$g['theme']."/images/icons/icon_alias.gif' title='".gettext("FreeBSD alias")."'>";
					else if($vipent['mode'] == "other")
					echo "<img src='./themes/".$g['theme']."/images/icons/icon_other.gif' title='".gettext("Other")."'>";
					else if($vipent['mode'] == "ipalias")
					echo "<img src='./themes/".$g['theme']."/images/icons/icon_ipalias.gif' title='".gettext("IP Alias")."'>";
					?></td>
				<td class="listbg"
					ondblclick="document.location='firewall_virtual_ip_edit.php?id=<?=$i;?>';">
				<font color="#FFFFFF"><?=htmlspecialchars($vipent['descr']);?>&nbsp;
				</td>
				<td class="list" nowrap>
				<table border="0" cellspacing="0" cellpadding="1">
					<tr>
						<td valign="middle"><a
							href="firewall_virtual_ip_edit.php?id=<?=$i;?>"><img
							src="./themes/<?= $g['theme']; ?>/images/icons/icon_images/e.gif"
							width="17" height="17" border="0" alt="" /></a></td>
						<td valign="middle"><a
							href="firewall_virtual_ip.php?act=del&amp;id=<?=$i;?>"
							onclick="return confirm('<?=gettext("Do you really want to delete this entry?");?>')"><img
							src="./themes/<?= $g['theme']; ?>/images/icons/icon_images/x.gif"
							width="17" height="17" border="0" alt="" /></a></td>
					</tr>
				</table>
				</td>
			</tr>
			<?php endif; ?>
			<?php $i++; endforeach; ?>
			<tr>
				<td class="list" colspan="3"></td>
				<td class="list">
				<table border="0" cellspacing="0" cellpadding="1">
					<tr>
						<td valign="middle"><a href="firewall_virtual_ip_edit.php"><img
							src="./themes/<?= $g['theme']; ?>/images/icons/icon_images/plus.gif"
							width="17" height="17" border="0" alt="" /></a></td>
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td colspan="4">
				<p><span class="vexpl"><span class="red"><strong><?=gettext("Note");?>:<br />
				</strong></span><?=gettext("The virtual IP addresses defined on this page may be used in");?>
				<a href="firewall_nat.php"><?=gettext("NAT");?></a> <?=gettext("mappings");?>.<br />
				<?=gettext("You can check the status of your CARP Virtual IPs and interfaces");?>
				<a href="carp_status.php"><?=gettext("here");?></a>.</span></p>
				</td>
			</tr>
		</table>
		</div>

</table>
</form>
				<?php include("fend.inc"); ?>
</body>
</html>
