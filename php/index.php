<?php
define('XCLASS','bg-danger text-white');
define('BIRTHDAY','bg-success text-white');
define('HOLIDAY','bg-warning text-white');

$layout = file_get_contents('layout.html');

function output($content,$layout,$title='')
{
        $layout = str_replace('</title>',$title.'</title>',$layout);
        return (str_replace('<!--Content-->',$content,$layout));
}


$schedule = array();
$schedule['2020-04-20']=BIRTHDAY;
$schedule['2020-05-13']=XCLASS;

include('calsetup.php');
$content = '
<div class="container">
'.$calendar.'
</div>
';

echo output($content,$layout);
