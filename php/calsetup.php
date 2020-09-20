<?php

if (!$authenticated) exit('poof!');


$schedule['2020-01-01']=HOLIDAY;
$schedule['2020-01-20']=HOLIDAY;
$schedule['2020-02-17']=HOLIDAY;
$schedule['2020-05-25']=HOLIDAY;
$schedule['2020-07-03']=HOLIDAY;
$schedule['2020-07-04']=HOLIDAY;
$schedule['2020-09-07']=HOLIDAY;
$schedule['2020-10-12']=HOLIDAY;
$schedule['2020-11-11']=HOLIDAY;
$schedule['2020-11-26']=HOLIDAY;
$schedule['2020-12-25']=HOLIDAY;
$schedule['2021-01-01']=HOLIDAY;
$schedule['2021-01-18']=HOLIDAY;
$schedule['2021-02-15']=HOLIDAY;
$schedule['2021-05-31']=HOLIDAY;
$schedule['2021-07-04']=HOLIDAY;
$schedule['2021-07-05']=HOLIDAY;
$schedule['2021-09-06']=HOLIDAY;
$schedule['2021-10-11']=HOLIDAY;
$schedule['2021-11-11']=HOLIDAY;
$schedule['2021-11-25']=HOLIDAY;
$schedule['2021-12-24']=HOLIDAY;
$schedule['2021-12-25']=HOLIDAY;
$schedule['2021-12-31']=HOLIDAY;
$schedule['2022-01-01']=HOLIDAY;
$schedule['2022-01-17']=HOLIDAY;
$schedule['2022-02-21']=HOLIDAY;
$schedule['2022-05-30']=HOLIDAY;
$schedule['2022-07-04']=HOLIDAY;
$schedule['2022-09-05']=HOLIDAY;
$schedule['2022-10-10']=HOLIDAY;
$schedule['2022-11-11']=HOLIDAY;
$schedule['2022-11-24']=HOLIDAY;
$schedule['2022-12-25']=HOLIDAY;
$schedule['2022-12-26']=HOLIDAY;

$cal=array();

$cal_out = array();
$cix=0;
$ciy=0;
$today = date('Y-m-d');

for ($i=0;$i<12;$i++)
{

	if ($i==0)
	{
		$dt=time();
	} else {
		$dt = strtotime('+'.$i.' Months');
	}
	
	$num_days = date('t',$dt);
	$month_name = date('M',$dt);
	$year = date('Y',$dt);
	$month = date('m',$dt);
	$month_name = date('F',$dt);
	$first_day = date('N',strtotime($year.'-'.$month.'-01'));
	if ($first_day==7) $first_day = 0;
	$first_day_name = date('D',strtotime($year.'-'.$month.'-01'));


	$day = 1;
	for ($y=0;$y<6;$y++)
	{
		for ($x=0;$x<7;$x++)
		{

			if ($day<10) 
			{
				$date = $year.'-'.$month.'-0'.$day;
			} else {
				$date = $year.'-'.$month.'-'.$day;
			}

			$background = '';
			if ($date==$today) $background = ' class="bg-primary text-white"';
			if (is_array($schedule) && array_key_exists($date,$schedule)) $background = ' class="'.$schedule[$date].'"';
			if ($y==0) 
			{

				//echo $x."\t".$first_day."\n";
				if ($x<$first_day)
				{
					$this_day = '<td class="text-white">X</td>';
				} else {
					$this_day = '<td'.$background.'>'.$day.'</td>';
					$day++;
				}
			} else {
				if ($day<=$num_days)
				{
					$this_day = '<td'.$background.'>'.$day.'</td>';
					$day++;
				} else {
					$this_day = '<td class="text-white">X</td>';
				}
			}
			$cal[$year][$month][$y][$x]=$this_day;
		}
	}
	$cal[$year][$month]['cal']='<table class="table table-bordered table-sm">
<thead>
<tr class="thead-dark text-center"><th colspan="7">'.$month_name.' '.$year.'</th></tr>
<tr class="thead-light text-center">
<th class="w-14">Sun</th>
<th class="w-14">Mon</th>
<th class="w-14">Tue</th>
<th class="w-15">Wed</th>
<th class="w-14">Thu</th>
<th class="w-15">Fri</th>
<th class="w-14">Sat</th>
</tr>
</thead>
<tbody>
<tr class="text-center">'.join('',$cal[$year][$month][0]).'</tr>
<tr class="text-center">'.join('',$cal[$year][$month][1]).'</tr>
<tr class="text-center">'.join('',$cal[$year][$month][2]).'</tr>
<tr class="text-center">'.join('',$cal[$year][$month][3]).'</tr>
<tr class="text-center">'.join('',$cal[$year][$month][4]).'</tr>
<tr class="text-center">'.join('',$cal[$year][$month][5]).'</tr>
</tbody>
</table>';

	$cal_out[$ciy][$cix] = '<div class="col-lg-4">
'.$cal[$year][$month]['cal'].'
</div>
';
	$cix++;
	if ($cix==3)
	{
		$ciy++;
		$cix=0;
	}
	
//	echo $year."\t".$month."\t".$month_name."\t".$num_days."\t".$first_day."\t".$first_day_name."\n";
}


$calendar = '
<style>
.w-14 { width: 14% !important; }
.w-15 { width: 15% !important; }
</style>
';
foreach ($cal_out as $k=>$v)
{
	$calendar .= '<div class="row">
'.join('',$v).'</div>
';
}
//echo $calendar;
	

