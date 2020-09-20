<?php

define('HRADMIN','CN=relax.finance.hradmin');
define('LDAP_FILTER',"(&(objectCategory=person))");

$conn = mysqli_connect('127.0.0.1','USER','PASSWORD','DBNAME');

function get_ldap_host($email)
{
	//return ('example.com');
	$hx=explode('@',$email);
	$hostname = array_pop($hx);
	return ($hostname);
}

function output($content,$layout)
{
        return (str_replace('<!--CONTENT-->',$content,$layout));
}

$sessionid='';

if (isset($_COOKIE['rxid']))
{
        $sessionid = substr(filter_var($_COOKIE['rxid'],FILTER_SANITIZE_STRING),0,128);
} else {
        $ops=array();
        $ops['expires']=time()+(84600*90);
        $ops['path']='/';
        $ops['domain']='relax.finance';
        $ops['secure']=TRUE;
        $ops['httponly']=TRUE;
        $ops['samesite']='Lax';
        $chk = base64_encode(sodium_crypto_generichash_keygen());
        setcookie('rxid',$chk,$ops);
}

$authenticated = false;
if ($sessionid!='')
{
	$sql = "SELECT idx FROM logins WHERE sessionid='".mysqli_real_escape_string($conn,$sessionid)."' AND logout=0";
	$res = mysqli_query($conn,$sql);
	if (mysqli_num_rows($res)>0)
	{
		$authenticated = true;
	}
	mysqli_free_result($res);
}

function getStellarId($ds,$entry)
{
	$attrs = ldap_get_attributes($ds, $entry);
	$cn ='';
	if ($attrs!=false)
	{
		for ($j = 0; $j < $attrs["count"]; $j++)
		{
			$attr_name = $attrs[$j];

			if ($attr_name=='stellarAccountId')
			{
				return ($attrs['cn'][0]."\t".$attrs['stellarAccountId'][0]."\t".$attrs['userPrincipalName'][0]."\t".join(',',$attrs['memberOf']));
			}
		}
		$cn=$attrs['cn'][0]."\t".$attrs['stellarAccountId'][0]."\t".$attrs['userPrincipalName'][0]."\t".join(',',$attrs['memberOf']);
	}
	return $cn."\t";
}

if ($authenticated)
{
	$layout = file_get_contents('li-layout.html');
} else {
	$layout = file_get_contents('layout.html');
}
