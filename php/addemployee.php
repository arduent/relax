<?php

include('db.php');

$err=array();

if (!$authenticated) exit('Not Authorized');

$email = substr(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL),0,255);
$pwd = substr(filter_var($_POST['pwd'], FILTER_SANITIZE_STRING),0,255);
$adminsk = substr(filter_var($_POST['sk'],FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),0,56);
$recipient = substr(filter_var($_POST['recipient'], FILTER_SANITIZE_STRING),0,255);

$hostname = '';
$base_dn = '';

/* same thing as a sync to make sure they have admin group access */


if (strlen($email)<7)
{
	$err[]='<li>Invalid email address.</li>';
}
if (strlen($pwd)<7)
{
	$err[]='<li>Invalid password.</li>';
}

if (count($err)<1)
{
	$hostname = get_ldap_host($email);
	$nx = explode('.',$hostname);
	$ox = array();
	foreach ($nx as $v)
	{
		$ox[]='DC='.$v;
	}
	$base_dn = join(',',$ox);
	$ds = ldap_connect($hostname) or 
		$err[]='<li>Could not connect to authentication server on that domain.</li>';
}


if (count($err)<1)
{
	ldap_bind($ds, $email, $pwd) or
		$err[]='<li>Login Failed.</li>';
}

if (count($err)>0)
{

	$content = '<h1>OOpS</h1>
<p>Authentication failed.</p>
<ul>'.join('',$err).'</ul>
<form method="post" action="/sync.php">
<table class="table">
<tbody>
<tr><td>Email</td><td><input type="email" class="form-control" name="email" required></td></tr>
<tr><td>Password</td><td><input type="password" class="form-control" name="pwd" required"></td></tr>
<tr><td> </td><td><button type="submit" class="btn btn-primary">Log In</button></td></tr>
</tbody>
</table>
</form>
';
} else {

	if ((isset($_COOKIE['rxhk']))&&(isset($_COOKIE['mxid'])))
        {
		$mxid = substr(filter_var($_COOKIE['mxid'],FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),0,16);

		$sql = "DELETE FROM mcache WHERE secid='".
			mysqli_real_escape_string($conn,$mxid)."'";
		mysqli_query($conn,$sql);
	}

	$has_admin = false;
        $ops=array();
        $ops['expires']=time()+(84600*90);
        $ops['path']='/';
        $ops['domain']='relax.finance';
        $ops['secure']=TRUE;
        $ops['httponly']=TRUE;
        $ops['samesite']='Lax';

	$lst = array();
	$filter = LDAP_FILTER;
	$sr = ldap_search($ds, $base_dn, $filter);
	$entry = ldap_first_entry($ds,$sr);
	do
	{
		$dn = ldap_get_dn($ds, $entry);
		$v = getStellarId($ds,$entry);
		$lst[$dn] = $v;
		$rt=explode("\t",$v);
		if ($rt[2]==$email)
		{
			setcookie('misi',$rt[1],$ops);
			if (strstr($rt[3],HRADMIN)) $has_admin = true;
		}
	} while ($entry = ldap_next_entry($ds, $entry));

	if ($has_admin)
	{
		$plain = json_encode($lst);

		$hash_key = sodium_crypto_generichash_keygen();
		$secret_key = sodium_crypto_generichash_keygen();
		$key = sodium_crypto_generichash ($secret_key, $hash_key, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$ciphertext = sodium_crypto_secretbox($plain, $nonce, $key);

		$mxid = bin2hex(random_bytes(8));

		$sql = "INSERT INTO mcache (idx,mhash,mxd,secid,sequence) VALUES (NULL,'".
			mysqli_real_escape_string($conn,base64_encode($hash_key.$nonce))."','".
			mysqli_real_escape_string($conn,base64_encode($ciphertext))."','".
			mysqli_real_escape_string($conn,$mxid)."','".time()."')";
		mysqli_query($conn,$sql);
		
		setcookie('rxhk',base64_encode($secret_key),$ops);
		setcookie('mxid',$mxid,$ops);

		//HERE

		$status_log = '';

		$kp = file('https://obitcoin.org/x-relax-genkey.php');
		$sk = $kp[0];
		$pk = $kp[1];

		$status_log = '<p>New Secret Key: '.$sk.'<br>New Stellar Public Id: '.$pk.'</p>';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://obitcoin.org/x-relax-adfund.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			"sk=".$adminsk.
			"&pk=".$pk);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close ($ch);
		
		$ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,"https://obitcoin.org/x-relax-trust-vacation.php");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,
                        "sk=".$sk.
                        "&pk=".$pk);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close ($ch);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,"https://obitcoin.org/x-relax-trust-health.php");
                curl_setopt($ch, CURLOPT_POST, 1); 
                curl_setopt($ch, CURLOPT_POSTFIELDS,
                        "sk=".$sk.
                        "&pk=".$pk);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close ($ch);
		
		$x=explode('|',$recipient);
		$cn = array_shift($x);
		$attr=array();
		$attr['stellarAccountId']=$pk;
		ldap_mod_replace($ldap_connection,'CN='.$cn.',CN=Users,'.$base_dn,$attr);

		$content = '<h1>Success</h1>'.$status_log.'<a href="/">Click here to continue</a></p>';
	} else {
		$content = '<h1>OOpS</h1><p>You are not a member of HR administrators.</p>';
	}
}

ldap_unbind($ds);
echo output($content,$layout);
mysqli_close($conn);

