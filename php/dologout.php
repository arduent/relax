<?php

include('db.php');

if ($authenticated)
{
        $sql = "UPDATE logins SET logout='".time()."' WHERE sessionid='".
                mysqli_real_escape_string($conn,$sessionid)."'";
        mysqli_query($conn,$sql);

	$content = '<h1>Thank You</h1>
<p>You are logged out.</p>
<p><a href="/">continue</a></p>
';
	$authenticated=false;
	$layout = file_get_contents('layout.html');
} else {
	$content = '<h1>OOpS</h1><p>You are already logged out.</p><p><a href="/">continue</a></p>';
}

echo output($content,$layout);
mysqli_close($conn);

