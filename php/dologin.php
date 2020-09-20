<?php

include('db.php');

$err=array();

$email = substr(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL),0,255);
$pwd = substr(filter_var($_POST['pwd'], FILTER_SANITIZE_STRING),0,255);
$hostname = '';
$base_dn = '';


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
<form method="post" action="/dologin.php">
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
	$content = '<h1>Login Success</h1><p><a href="/">Continue</a></p>';

	$sql = "UPDATE logins SET logout='".time()."' WHERE sessionid='".
		mysqli_real_escape_string($conn,$sessionid)."'";
	mysqli_query($conn,$sql);

	$sql = "INSERT INTO logins (idx,sessionid,ip,sequence,logout) ".
		"VALUES (NULL,'".
		mysqli_real_escape_string($conn,$sessionid)."','".
		mysqli_real_escape_string($conn,$_SERVER['REMOTE_ADDR'])."','".
		time()."',0)";
	mysqli_query($conn,$sql);

	/* if they don't have the user list, go fetch it */

	if (!isset($_COOKIE['rxhk']))
	{
		$lst = array();

		$has_admin = false;
                $ops=array();
                $ops['expires']=time()+(84600*90);
                $ops['path']='/';
                $ops['domain']='relax.finance';
                $ops['secure']=TRUE;
                $ops['httponly']=TRUE;
                $ops['samesite']='Lax';

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
		}
	}
}


ldap_unbind($ds);
echo output($content,$layout);
mysqli_close($conn);

