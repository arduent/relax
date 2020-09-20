<?php

define('XCLASS','bg-danger text-white');
define('BIRTHDAY','bg-success text-white');
define('HOLIDAY','bg-warning text-white');

$schedule = array();
$schedule['2020-04-20']=BIRTHDAY;
$schedule['2020-05-13']=XCLASS;

include('db.php');

if (!$authenticated)
{
	$content = file_get_contents('hm.html');
	require __DIR__ . '/vendor/autoload.php';
	$parser = new \cebe\markdown\GithubMarkdown();
	$docs = $parser->parse(file_get_contents('/www/j/nc/data/waitman/files/Documents/relax.md'));
	$docs = str_replace("\\\n","<br>\n",$docs);
	$xd = explode('<img src="',$docs);
	array_shift($xd);
	foreach ($xd as $k=>$v)
	{
		$n=explode('alt="',$v);
		$pre = array_shift($n).'alt="';
		$docs = str_replace($pre,'',$docs);
	}
	
	$content = '
<div class="p-3" id="dol"><button class="btn btn-primary btn-sm" onclick="togl();">Log In</button></div>
<div class="p-3" style="display:none;" id="login">
<form method="post" action="/dologin.php">
<div style="width:400px;max-width:100%;">
<table class="table">
<tbody>
<tr><td>Email</td><td><input type="email" class="form-control" name="email" required></td></tr>
<tr><td>Password</td><td><input type="password" class="form-control" name="pwd" required"></td></tr>
<tr><td> </td><td><button type="submit" class="btn btn-primary">Log In</button></td></tr>
</tbody>
</table>
</div>
</form>
</div>
<script>
function togl()
{
$("#dol").toggle();
$("#login").toggle();
}
</script>
'.$content.'
<div style="padding-top:50px;" id="docu"><a name="docs"></a><h1>Documentation</h1>'.$docs.'</div>';
} else {

	$lst = array();
	$ops = array();
	$emps = array();
	$mikey = '';
	if ((isset($_COOKIE['rxhk']))&&(isset($_COOKIE['mxid'])))
	{
		$mxid = substr(filter_var($_COOKIE['mxid'],FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),0,16);
		$rxhk = substr(filter_var($_COOKIE['rxhk'],FILTER_SANITIZE_STRING),0,255);

		if (($mxid!='')&&($rxhk!=''))
		{
			$sql = "SELECT * FROM mcache WHERE secid='".
				mysqli_real_escape_string($conn,$mxid)."'";
			$res = mysqli_query($conn,$sql);
			if (mysqli_num_rows($res)>0)
			{
				$row = mysqli_fetch_array($res);
				$mhash = base64_decode($row['mhash']);
				$ciphertext = base64_decode($row['mxd']);
				$nonce = substr($mhash,strlen($mhash)-SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
				$hash_key = substr($mhash,0,strlen($mhash)-SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
				$key = sodium_crypto_generichash (base64_decode($rxhk), $hash_key, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
				$plain = sodium_crypto_secretbox_open($ciphertext,$nonce,$key);		
				$lst = json_decode($plain,true);
				foreach ($lst as $k=>$v)
				{
					$rt=explode("\t",$v);
					if ($rt[1]!='')
					{
						$ops[]='<option value="'.join('|',$rt).'">'.$rt[0].'</option>';
					} else {
						/* these people need Stellar Keys */
						$emps[]='<option value="'.join('|',$rt).'">'.$rt[0].'</option>';
					}
				}
			}
			mysqli_free_result($res);
		}
	}

	include('calsetup.php');

	$bal = '';
	$bal_button = '';

	if (isset($_COOKIE['misi']))
	{
		$mikey = substr(filter_var($_COOKIE['misi'],FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),0,56);
		if (strlen($mikey)==56)
		{
			$bala = json_decode(file_get_contents('https://obitcoin.org/x-misty-accntbal.php?pk='.$mikey),true);
			if (count($bala)>0)
			{
				$bal_button = '<a href="javascript:void(0);" class="btn btn-sm btn-primary" onclick="bala();"><i class="fas fa-coins"></i> Balance</a>';
				$bal = '
<div id="bala" class="p-3">
<span class="small">Stellar Account Id: '.htmlentities($mikey).'</span>
<table class="table" id="balt"><thead><tr><th class="text-right">Balance</th><th class="text-center">Asset</th></tr></thead><tbody>';
				foreach ($bala as $k=>$v)
				{
					$asset = 'XLM';
					if (isset($v['asset_code'])) $asset=$v['asset_code'];
					$bal .= '<tr><td class="text-right">'.htmlentities($v['balance']).'</td><td class="text-center">'.htmlentities($asset).'</td></tr>';
				}
				$bal .= '</tbody></table> <div class="text-right"><button id="sbb" class="btn btn-sm btn-success" onclick="syncbal();"><i class="fas fa-sync-alt"></i> Sync Balance</button></div></div>';
			}
		}
	}
	
	$content = '
<style>
#ah { display: none; }
#sy { display: none; }
#bala { display: none; }
#shox { display: none; }
</style>
<script>
function dsh() {
$("#ah").toggle();
}
function dosy() {
$("#sy").toggle();
}
function bala() {
$("#bala").toggle();
}
function showadd() {
$("#shox").toggle();
}

function syncbal() { 
$("#sbb").attr("disabled",true);
$.getJSON( "https://obitcoin.org/x-misty-accntbal.php?pk='.htmlentities($mikey).'", function( data ) {
var items = [];
$.each( data, function( key, val ) {
var bal = this.balance;
var asset = "XLM";
if (typeof this.asset !== "undefined") {
asset = this.asset;
}
items.push( "<tr><td class='."'text-right'".'>" + bal + "</td><td class='."'text-center'".'>" + asset + "</td></tr>" );
});
$("#balt tbody").html(items.join( "" ));
$("#sbb").removeAttr("disabled");
}); 
}


</script>
<div class="container">
<div class="p-3"><a href="javascript:void(0);" class="btn btn-sm btn-primary" onclick="dsh();"><i class="fas fa-globe-americas"></i> Asset</a> 
<a href="javascript:void(0);" class="btn btn-sm btn-primary" onclick="dosy();"><i class="fas fa-sync-alt"></i> Sync Contacts</a> 
<a href="javascript:void(0);" class="btn btn-sm btn-primary" onclick="showadd();"><i class="fas fa-users"></i> Employee</a>
'.$bal_button.'</div>
'.$bal.'
<div id="sy" class="p-3">
<p><em>Enter your login information to sync contacts</em></p>
<form method="post" action="/sync.php">
<table class="table">
<tbody>
<tr><td>Email</td><td><input type="email" class="form-control" name="email" required></td></tr>
<tr><td>Password</td><td><input type="password" class="form-control" name="pwd" required"></td></tr>
<tr><td> </td><td><button type="submit" class="btn btn-success btn-sm">Sync Contacts</button>
<button onclick="dosy();" class="btn btn-danger btn-sm"><i class="fas fa-window-close"></i> Cancel</button></td></tr>
</tbody>
</table>
</form>
</div>
<div id="ah" class="p-3">
<form method="post" action="/sendasset.php">
<table class="table">
<tbody>
<tr><td>Recipient</td><td><select name="reciptient" class="form-control"><option></option>
'.join("\n",$ops).'
</select></td></tr>
<tr><td>Date</td><td><input type="date" name="date" value="'.date('m/d/Y').'" class="form-control"> <em><small>Note: Dates in the future will go to escrow</small></em></td></tr>
<tr><td>Amount</td><td>
<div class="input-group">
<input type="text" name="amt" class="form-control">
<span class="input-group-addon">-</span>
<select name="asset" class="form-control"><option value="Vacation">Vacation</option><option value="Health">Health</option></select>
</div>
</td></tr>
<tr><td> </td><td><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-share-square"></i> Submit Transaction</button>
<button onclick="dsh();" class="btn btn-danger btn-sm"><i class="fas fa-window-close"></i> Cancel</button></td></tr>
</table>
</form>
</div>
<div id="shox" class="p-3">
<form method="post" action="/addemployee.php">
<table class="table">
<tbody>
<tr><td>Employee</td><td><select name="reciptient" class="form-control"><option></option>
'.join("\n",$emps).'
</select></td></tr>
<tr><td>Funding Stellar Secret Key</td><td><input type="password" name="sk" class="form-control"> <small>Will transfer 2 XLM to employee account</small></td></tr>
<tr><td>Email</td><td><input type="email" class="form-control" name="email" required></td></tr>
<tr><td>Password</td><td><input type="password" class="form-control" name="pwd" required"></td></tr>
<tr><td> </td><td><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-share-square"></i> Submit</button>
<button onclick="showadd();" class="btn btn-danger btn-sm"><i class="fas fa-window-close"></i> Cancel</button></td></tr>
</table>
</form>
</div>
<div class="container">
'.$calendar.'
</div>
';

}

echo output($content,$layout);
mysqli_close($conn);

