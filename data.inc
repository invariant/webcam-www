<?php
function timeData($start, $end) {
	$data=array();
	$query="SELECT * FROM `pictures` WHERE `time` >= '$start' && `time` < '$end' && `status` > 0 ORDER BY `time`;";
	$result=mysql_query($query);
	if ($result) {
		while ($row=mysql_fetch_assoc($result)) {
			$data['points'][$row['time']]=array('i'=>$row['id'], 'd'=>$row['difference'], 'b'=>$row['brightness']);
		}
	}
	$data['start']=$start;
	$data['end']=$end;	
	return $data;
}
?>