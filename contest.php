<?php

function toast_contest ($mode) {
	global $post, $rsvp_options;
	$date = get_rsvp_date($post->ID);
	$date = strftime($rsvp_options['long_date'],strtotime($date));
		if($mode == 'dashboard')
	{
		$output = '<div id="scoring"><h1>Scoring Dashboard - '.$date.' Contest</h1>';
		$output .= toast_scoring_dashboard();
	}
	elseif($mode == 'voting')
	{
		$output = '<div id="scoring"><h1>Voting - '.$date.' Contest</h1>';
		$output .= toast_scoring_sheet();	
	}
	else
		return "<div>Error: scoring mode not recognized</div>";
	$output .= '</div>';// close wrapper		
return $output;
}

function toast_scoring_dashboard () {
ob_start();
global $post;
global $current_user;
$output = '';
$votinglink = $actionlink = get_permalink($post->ID);
if(strpos($actionlink,'?'))
{
$actionlink .= '&scoring=dashboard';
$votinglink .= '&scoring=voting';	
}
else {
$actionlink .= '?scoring=dashboard';
$votinglink .= '?scoring=voting';	
}
$link = sprintf('<div id="agendalogin"><a href="%s">'.__('Login','rsvpmaker-for-toastmasters').'</a></div>',site_url().'/wp-login.php?redirect_to='.urlencode($actionlink));

$contest_selection['International Speech Contest'] = array('Speech Development' => 20,'Effectiveness' => 15,'Speech Value' => 15,'Physical' => 10,'Voice' => 10,'Manner' => 10,'Appropriateness' => 10,'Correctness' => 10);
$contest_selection['Video Speech Contest'] = array('Speech Development' => 20,'Effectiveness' => 15,'Speech Value' => 15,'Physical' => 10,'Voice' => 10,'Manner' => 10,'Appropriateness' => 10,'Correctness' => 10);
$contest_selection['Humorous Speech Contest'] = array('Speech Development' => 15,'Effectiveness' => 10,'Speech Value' => 15,'Audience Response' => 15,'Physical' => 10,'Voice' => 10,'Manner' => 10,'Appropriateness' => 10,'Correctness' => 5);
$contest_selection['Table Topics Contest'] = array('Speech Development' => 30,'Effectiveness' => 25,'Physical' => 15,'Voice' => 15,'Appropriateness' => 10,'Correctness' => 5);
$contest_selection['Evaluation Contest'] = array('Analytical Quality' => 40,'Recommendations' => 30,'Technique' => 15,'Summation' => 15);
$contest_selection['Mini-Webinar Contest'] = array('Speech Development' => 15,'Audience Engagement' => 15,'Speech Value' => 15,'Call to Action' => 15,'Visual' => 15,'Voice' => 10,'Manner' => 5,'Appropriateness/Correctness' => 10);

$contest_timing['International Speech Contest'] = '5 to 7';
$contest_timing['Video Speech Contest'] = '5 to 7';
$contest_timing['Humorous Speech Contest'] = '5 to 7';
$contest_timing['Table Topics Contest'] = '1 to 2';
$contest_timing['Evaluation Contest'] = '2 to 3';
$contest_timing['Mini-Webinar Contest'] = '6 to 8';
	
$demo = get_post_meta($post->ID,'_contest_demo',true);
if($demo)
	$output .= '<p>This page is configured as a demo, no password required.</p>';
elseif( !is_user_logged_in() )
{
	return '<p>You must be logged in as the site administrator, chief judge, or ballot counter.</p>'.$link;
}
else
{
	$dashboard_users = get_post_meta($post->ID,'tm_contest_dashboard_users',true);
	if(!current_user_can('edit_others_posts'))
	{
	if(!is_array($dashboard_users))
		return '<p>Logged in user not recognized as a site administrator, chief judge, or ballot counter.</p>';
	if(!in_array($current_user->ID,$dashboard_users))
		return '<p>Logged in user not recognized as a site administrator, chief judge, or ballot counter.</p>';
	}
}

if(!empty($_POST['contest_scoring']))
{
	$scoring_index = $_POST['contest_scoring'];
	$contest_scoring = $contest_selection[$scoring_index];
	$timing = $contest_timing[$scoring_index];
	if(!empty($contest_scoring))
	{
		update_post_meta($post->ID,'toast_contest_scoring',$contest_scoring);
		update_post_meta($post->ID,'toast_timing',$timing);		
	}
}
elseif(!empty($_POST['scoring_label'])) {
	foreach($_POST['scoring_label'] as $index => $label)
	{
	$label = stripslashes($label);
	$score = (int) $_POST['scoring_score'][$index];
	if(!empty($label) && !empty($score))
	{
		$contest_scoring[$label] = $score;
		$foropt[] = $label.':'.$score;
	}
	}
	if(!empty($contest_scoring))
	{
		$custom = get_option('toast_custom_contest');
		$newcustom = sprintf('<option value="%s">%s</option>',implode(',',$foropt),stripslashes($_POST['contest_name']));
		update_option('toast_custom_contest',$custom . $newcustom);
		update_post_meta($post->ID,'toast_contest_scoring',$contest_scoring);
		update_post_meta($post->ID,'toast_timing',$_POST['toast_timing']);
	}
}
else
$contest_scoring = get_post_meta($post->ID,'toast_contest_scoring',true);
if(empty($contest_scoring) || isset($_GET['reset_scoring']))
{
$options = '<option value="">Select Contest</option>';

foreach($contest_selection as $contest => $score_array)
{
$options .= sprintf('<option value="%s">%s</option>',$contest,$contest);
}

if(isset($_GET['clear_custom_scoring']))
	delete_option('toast_custom_contest');
else
	$options .= get_option('toast_custom_contest');
$output .= '<p>Use this dashboard to pick a contest, add your list of contestants and judges, and generate personalized links to a page where each judge can score contestants and enter their votes.</p><p>As the judges vote, you will see their votes appear on the dashboard within seconds. If you have the Timer and Tiebreaking Judge record their work online, their input can also be factored in to show if any contestants were disqualified or how any ties were broken.</p>';
$output .= '<h1>Choose Contest</h1>'.sprintf('<form method="post" action="%s">
	<div><select name="contest_scoring">%s</select>
		<button>Set</button></div>
</form>
',$actionlink,$options);
	
$output .= '<h1>Custom Contest</h1>'.sprintf('<form method="post" action="%s" id="custom_contest"><p>Contest Name: <input type="input" name="contest_name" value="My Custom Contest" /></p>
',$actionlink);

for($i = 1; $i < 15; $i++)
	$output .= sprintf('<div>Label: <input type="text" name="scoring_label[%d]" /> Score: <input class="setscore" type="text" name="scoring_score[%d]" /></div>',$i,$i);
$output .= '<div>Timing <input type="text" name="toast_timing" value="5 to 7" size="10"> minutes</div>';
$output .= '<div id="readyprompt"></div><button>Set</button></div>
</form>';
ob_start();
?>
<p><a href="<?php echo $actionlink; ?>&reset_scoring=1&clear_custom_scoring">Clear Custom Scoring</a></p>
<script>
jQuery(document).ready(function($) {
	
$('form#custom_contest').submit(function(){
	var score = 0;
	$('.setscore').each(function() {
		score += Number($(this).val());
	});
	if(score != 100)
		{
       $('#readyprompt').html('<span style="color: red;">Scores total '+score + ' (must total 100)</span>');
		return false;			
		}
	else
		$('#readyprompt').html('Saving ...');
}); 

});
</script>
<?php
$output .= ob_get_clean();

return $output;
}


if(isset($_POST['track_role']))
{
	$track_role = $_POST['track_role'];
	if($track_role == 'cancel')
	{
		$track_role = '';
		delete_post_meta($post->ID,'tm_track_role');
	}
	else
		update_post_meta($post->ID,'tm_track_role',$track_role);
}
else
	$track_role = get_post_meta($post->ID,'tm_track_role',true);
	
if(isset($_POST['remove']))
{
	update_post_meta($post->ID,'tm_judges_removed',$_POST['remove']);
}
	
if(isset($_POST['tm_tiebreaker']))
{
	$tiebreaker = (int) $_POST['tm_tiebreaker'];
	update_post_meta($post->ID,'tm_scoring_tiebreaker',$tiebreaker);
}
else
	$tiebreaker = get_post_meta($post->ID,'tm_scoring_tiebreaker',true);
	
if(isset($_POST['tm_scoring_dashboard_users']))
{
	$dashboard_users = array(); //reset
	foreach($_POST['tm_scoring_dashboard_users'] as $id)
		if($id)
			$dashboard_users[] = $id;
	update_post_meta($post->ID,'tm_contest_dashboard_users',$dashboard_users);
}

if(!empty($track_role))
{
	$miss = 0;
	$field_base = '_'.preg_replace('/[^a-zA-Z0-9]/','_',$track_role);
	for($i = 1; $i <20; $i++)
	{
		$lookup = $field_base.'_'.$i;
		$contestant = get_post_meta($post->ID,$lookup,true);
		if(empty($contestant))
		{
			$miss++;
			if($miss > 5)
				break;
			else
				continue;
		}
		elseif(is_numeric($contestant))
		{
		$userdata = get_userdata($contestant);
		$contestant = $userdata->first_name.' '.$userdata->last_name;
		}
		elseif($contestant == '?')
			continue;
		$contestants[] = $contestant;
	}
}
elseif(isset($_POST['contestant']))
	foreach($_POST['contestant'] as $contestant)
		if(!empty($contestant))
			$contestants[] = $contestant;
if(!empty($contestants))
{
	update_post_meta($post->ID,'tm_scoring_contestants',$contestants);
	if(isset($_POST['make_order']) && !empty($_POST['contestant']))
	{
	foreach($_POST['contestant'] as $contestant)
		if(!empty($contestant))
			$order[] = $contestant;
		update_post_meta($post->ID,'tm_scoring_order',$order);
		delete_post_meta($post->ID,'tm_track_role');			
	}
}

if(isset($_POST['judge']))
{	
	foreach($_POST['judge'] as $index => $judge_id)
	{
		$judge_name = $_POST['judge_name'][$index];
		if(!empty($judge_name))
		{
			$judge[$index] = $judge_name;
		}
		elseif(!empty($judge_id))
			$judge[$index] = $judge_id;
	}
if(!empty($judge))
{
	print_r($judge);
	update_post_meta($post->ID,'tm_scoring_judges',$judge);	
}
}

if(empty($contestants))
	$contestants = get_post_meta($post->ID,'tm_scoring_contestants',true);
$judges = get_post_meta($post->ID,'tm_scoring_judges',true);
	
if(isset($_POST['resetit']))
{
	foreach($_POST['resetit'] as $reset)
	{
		if($reset == 'scores')
		{
			foreach($judges as $i => $value)
			{
				delete_post_meta($post->ID,'tm_scoring_vote'.$i);
				delete_post_meta($post->ID,'tm_subscore'.$i);	
			}
		}
		
		if($reset == 'judges')
			delete_post_meta($post->ID,'tm_scoring_judges');
		if($reset == 'order')
		{
		$order = $contestants;
		shuffle($order);
		update_post_meta($post->ID,'tm_scoring_order',$order);			
		}
	}
}

if(empty($order))
	$order = get_post_meta($post->ID,'tm_scoring_order',true);	

if(!empty($judges))
	echo '<div id="score_status"></div><div id="scores">'.toast_scoring_update_get($post->ID).'</div>';
	//<button id="scoreupdate">Update</button>
if(!empty($judges))
{
echo '<h2>Voting Links</h2><p>Share these personalized voting links with the judges. If judges report problems with the online voting, you can record votes on their behalf by following these same links.</p>';
foreach($judges as $key => $value)
{
	$v = $votinglink . '&judge='.$key;
		
	if(is_numeric($value))
	{
		$userdata = get_userdata($value);
		$name = $userdata->first_name.' '.$userdata->last_name;
		printf('<p>Use this link to record scores for %s <br /><a target="_blank" href="%s">%s</a></p>',$name,$v,$v);
	}
	else
	{
		$name = $value;
		printf('<p>Use this link to record scores for %s <br /><a target="_blank" href="%s">%s</a></p>',$name,$v,$v);
	}
}

$timer_code = get_post_meta($post->ID,'tm_timer_code',true);
if(empty($timer_code))
{
	$timer_code = time()+rand(1,99);
	add_post_meta($post->ID,'tm_timer_code',$timer_code);
}
$timer_link = add_query_arg( array(
    'timer' => 1,
    'contest' => $timer_code,
), get_permalink($post->ID) );

printf("<h3>Timer</h3><p>Use this link for the Timer's Report".'<br /><a target="_blank" href="%s">%s</a></p>',$timer_link,$timer_link);
	
}
if(empty($contestants))
	;
elseif(empty($order))
{
?>
<form method="post" action="<?php echo $actionlink; ?>">
	<h2>Set Speaking Order</h2>
	<p><em>Randomized speaking order has not been set.</em></p>
	<div><input type="hidden" name="resetit[]" value="order">
		<button>Set</button></div>
</form>
<?php
}
else
{
	echo '<h3>Speaking Order</h3>';
	foreach($order as $index => $next)
	{
		printf("<div>#%d %s</div>",$index + 1, $next);
	}
?>
<form method="post" action="<?php echo $actionlink; ?>">
	<h2>Reset</h2>
	<div><input type="checkbox" name="resetit[]" value="scores"> Scores</div>
	<div><input type="checkbox" name="resetit[]" value="order"> Speaking Order</div>
	<button>Reset</button>
</form>
<?php
}

?>
<div>
<form method="post" action="<?php echo $actionlink ?>" >
	<h2>Setup</h2>
	<h3>Contestants</h3>
<?php

if(strpos($post->post_content,'wp:wp4toastmasters'))
{
$data = wpt_blocks_to_data($post->post_content);
foreach($data as $item)
	if(!empty($item['role']))
		$roles[] = $item['role'];
}
else {
preg_match_all('/role="([^"]+)/',$post->post_content,$matches);
	$roles = (empty($matches[1])) ? array() : $matches[1];	
}
	
	
if(!empty($roles))
{
$track = '';
$track_top = '<option value="">Select Role</option>';

foreach($roles as $role)
{
$s = ($role == $track_role) ? ' selected="selected" ' : '';
if(strpos($role,'peaker') || strpos($role,'peech') || strpos($role,'ontest'))
	$track_top  .= sprintf('<option value="%s" %s>%s</option>',$role,$s,$role);
else
	$track .= sprintf('<option value="%s" %s>%s</option>',$role,$s,$role);
}

printf('<p>Sync With Agenda Role <select id="track_role" name="track_role" >%s<option value="cancel">Cancel Selection</option></select></p>',$track_top.$track);

}
?>
<div id="role_track_status"><?php if(!empty($track_role)) printf('<p>Names pulled from %s role signups on the agenda.</p>',$track_role); ?></div>
<div id="manual_contestants">
<?php
if(empty($track_role) && !empty($track))
	echo '<p>or enter names</p>';

if(is_array($contestants))
foreach($contestants as $value)
{
	//print_r($value);
	printf('<div><input type="text" name="contestant[]" value="%s"></div>',$value);	
}
$stop = 10;
if(is_array($contestants))
	$stop = $stop - sizeof($contestants);
if($stop <2)
	$stop = 2;
for($i= 0; $i < $stop; $i++)
	echo '<div><input type="text" name="contestant[]" value=""></div>';	
?>
<p><input type="checkbox" name="make_order" value="1"> Record this as the official speaking order</p>
</div>
	
<h3>Judges</h3>
<?php
if(is_array($judges))
foreach($judges as $index => $value)
{
	if(is_numeric($value)) {
		$user = $value;
		$name = '';
		$open = 'Open';
	}
	else {
		$user = 0;
		$name = $value;
		$open = 'Guest';
	}
	if(!empty($tiebreaker) && ($tiebreaker == $index))
		$s = ' checked="checked" ';
	else
		$s = '';
	$drop = awe_user_dropdown ('judge['.$index.']', $user, true, $open);
	printf('<p>%s <input type="text" name="judge_name[%d]" value="%s"><br /><input type="radio" name="tm_tiebreaker" value="%s" %s />Tiebreaker</p>',$drop,$index,$name,$index,$s);
}
$t = time();	
for($i= 0; $i < 10; $i++)
	{
	$index = $t+($i*100)+rand(1,99);
	$drop = awe_user_dropdown ('judge['.$index.']', 0, true);
	printf('<p>%s or guest: <input type="text" name="judge_name[%d]" value=""><br /><input type="radio" name="tm_tiebreaker" value="%s" />Tiebreaker</p>',$drop, $index,$index);
	}
?>
<p><em>You can assign up to 10 judges at a time. If you have more than 10 judges to assign, first submit the form with the first 10 assignments. When the page reloads, additional blanks will be displayed.</em></p>

<h3>Dashboard Users</h3>
	<p>Add chief judge, ballot counters</p>
<?php
for($i= 0; $i < 5; $i++)
{
	$user = empty($dashboard_users[$i]) ? 0 : $dashboard_users[$i];
	$drop = awe_user_dropdown ('tm_scoring_dashboard_users[]', $user, true);//, $open);
	echo '<div>'.$drop.'</div>';
}
?>
	<p><button>Submit</button></p>
</form>
</div>

<p style="margin-top: 200px;"><a href="<?php echo $actionlink. '&reset_scoring=1'; ?>">Change Contest Type</a></p>

<script>
jQuery(document).ready(function($) {

function refreshScores() {
$('#score_status').html('Checking for new scores ...');
$.get( "<?php echo site_url('?toast_scoring_update='.$post->ID); ?>", function( data ) {
  $( "#scores" ).html( data );
  $('#score_status').html('Updated');
});	
}
$('#scoreupdate').click(function() {
  refreshScores();
});

setInterval(function(){
  refreshScores();	
}, 10000);
	
$('#track_role').on('change', function(){
var role = $(this).val();
if(role == '')
	{
$('#role_track_status').html('');
$('#manual_contestants').show();		
	}
else {
$('#role_track_status').html('<p>Contestant names will be pulled from the '+role+' role on the agenda</p>');
$('#manual_contestants').hide();	
}
});

});
</script>

<?php
$output .= ob_get_clean();
return $output;
}

function toast_scoring_update_get($post_id) {
$judges = get_post_meta($post_id,'tm_scoring_judges',true);
if(empty($judges))
	return 'Judges not set';
$contestants = get_post_meta($post_id,'tm_scoring_contestants',true);
if(empty($contestants))
	return 'Contestants not set';

$tiebreaker = get_post_meta($post_id,'tm_scoring_tiebreaker',true);
$tiebreaker_vote = array();

$timer_report = get_post_meta($post_id,'_time_report',true);
$timer_report = (empty($timer_report)) ? "<p>Timer's report not submitted" : "<h3>Timer's Report</h3>".$timer_report;
$disqualified = get_post_meta($post_id,'_time_disqualified',true);
if(empty($disqualified)) $disqualified = array();

$output = '';
$novote = 0;
			$tiebreaker_status = '<h3>Tiebreaker Ranking</h3>';
	foreach($judges as $index => $judge) {
		$votes = get_post_meta($post_id,'tm_scoring_vote'.$index,true);
		if(is_numeric($judge))
		{
			$userdata = get_userdata($judge);
			$judge = $userdata->first_name.' '.$userdata->last_name;
		}
		if($index == $tiebreaker)
		{
			if(empty($votes))
				$tiebreaker_status = '<div style="color: red;">Tiebreaking judge has not yet voted</div>';
			else
			{
			$tiebreaker_vote = $votes;
			foreach($votes as $i => $vote)
			{
				$tiebreaker_status .= sprintf('<div>%s</div>',$vote);
			}
			
			}
				
		}
		elseif(empty($votes))
		{
			$output .= '<h3>'.$judge.'</h3><p>no votes recorded</p>';
			$novote++;
		}
		else
		{
			$output .= '<h3>'.$judge.'</h3>';
			$votes_recorded = true;
			foreach($votes as $i => $vote)
			{
				$points = 3 - $i;
				$output .= sprintf('<div>%s %d points</div>',$vote,$points);
				if(empty($ranking[$vote]))
					$ranking[$vote] = $points;
				else
					$ranking[$vote] += $points;
			}
		}
	}
if(!isset($votes_recorded))
	$output = 'No votes recorded';
else
{
	//print_r($ranking);
	$top =  '<div style="border: thin solid red; margin: 10px; padding: 10px;"><h2>Top Scores</h3>';
	$topscores = '<h3>Full Ranking with Scores</h3>'; 
	$topcount = 0;
	foreach($contestants as $c)
		if(empty($ranking[$c]))
			$ranking[$c] = 0;
	if(!empty($disqualified))
		{
		$order = get_post_meta($post_id,'tm_scoring_order',true);
		if(!empty($order))
			{
			foreach($disqualified as $id)
				{
				$c = $order[$id];
				$ranking[$c] -= 100;
				}
			}
		}
	arsort($ranking);
	$last_contestant = '';
	$last_points = 0;
	$inc = 0.4;
	$tie = false;
	foreach($ranking as $contestant => $points)
	{
		if(!empty($last_contestant) && ($points == $last_points))
			$tie = true;
	$last_contestant = $contestant;
	$last_points = $points;
	}
	if($novote)
		$top .= sprintf('<div style="color: red;">%s judges have not yet voted</div>',$novote);
	else
	{
		$top .= '<div style="background-color: red; color: white; font-weight: bold; padding: 5px;">All judges have now voted</div>';
		if($tie && !empty($tiebreaker_vote))
		{
			$top .= '<p>Tiebreaker vote applied</p>';
			foreach($tiebreaker_vote as $index => $contestant)
			{
				if(!empty($ranking[$contestant]))
					$ranking[$contestant] += $inc;
				$inc -= 0.01;
			}
			arsort($ranking);
		}		
	}

	foreach($ranking as $contestant => $points)
	{
		if(($topcount < 3) && ($points > 0))
			$top .= sprintf('<div>%s</div>',$contestant);
		if($points >= 0)
			$topscores .= sprintf('<div>%s (%d points)</div>',$contestant,$points);
		else
			$topscores .= sprintf('<div>%s (disqualified)</div>',$contestant,$points);			
		$topcount++;
	}
	$top .= $tiebreaker_status . $timer_report.'</div>';
	$output = $top . $topscores . $output;
}
return $output;
}

function toast_scoring_update () {
	if(!isset($_GET['toast_scoring_update']))
		return;
	$update = toast_scoring_update_get($_GET['toast_scoring_update']);
	die($update);
}

add_action('init','toast_scoring_update');

function toast_scoring_sheet() {
ob_start();
//$contestants = array('Adrienne Williams','John M. Quick','Carol Prahinski','Louis Brown','Norman Dowe','G. Murali');
global $post;
global $current_user;
$votinglink = $actionlink = get_permalink($post->ID);
if(strpos($actionlink,'?'))
{
$actionlink .= '&scoring=dashboard';
$votinglink .= '&scoring=voting';	
}
else {
$actionlink .= '?scoring=dashboard';
$votinglink .= '?scoring=voting';	
}

$contestants = get_post_meta($post->ID,'tm_scoring_contestants',true);
$judges = get_post_meta($post->ID,'tm_scoring_judges',true);
$tiebreaker = get_post_meta($post->ID,'tm_scoring_tiebreaker',true);

if(isset($_REQUEST['judge']))
{
	$id = (int) $_REQUEST['judge'];
	if(!isset($judges[$id]))
		return 'Invalid judge code';
	$name = $judges[$id];
	if(is_numeric($name))
	{
	$userdata = get_userdata($name);
	$name = $userdata->first_name.' '.$userdata->last_name;	
	}
}
elseif( is_user_logged_in() )
{
	$id = $judge_id = array_search($current_user->ID,$judges);
	$name = $current_user->display_name;
	if( !$id)
		echo '<div style="color: red;">Logged in user is not on the list of judges</div>';
}

$votinglink .= '&judge='.$id;

$link = sprintf('<div id="agendalogin"><a href="%s">'.__('Login','rsvpmaker-for-toastmasters').'</a></div>',site_url().'/wp-login.php?redirect_to='.urlencode($votinglink));

if(! $id )
	return sprintf('<p>Before voting, you must either log in as a user authorized as a judge or access this page with a code provided for guest judges. %s</p>',$link);

printf('<p><em>Voting Form for %s</em></p>',$name);

if(isset($_POST['vote']))
{
	update_post_meta($post->ID,'tm_scoring_vote'.$id,$_POST['vote']);
	update_post_meta($post->ID,'tm_subscore'.$id,$_POST['scores']);
}

$votes = get_post_meta($post->ID,'tm_scoring_vote'.$id,true);
if(is_array($votes) && !isset($_GET['judge_id']))
{
	echo '<h2>Recorded</h2>';
	foreach($votes as $index => $vote)
	{
		printf('<p>#%d %s',$index + 1, $vote);
		if(empty($vote))
			echo '<div style="color: red;">Vote left blank</div>';
	}

return ob_get_clean();
}

$order = get_post_meta($post->ID,'tm_scoring_order',true);
if(empty($order))
	$order = $contestants;

$scoring = get_post_meta($post->ID,'toast_contest_scoring',true);

if(isset($_GET["clear_scores"]))
	$tm_subscores = array();
else
	$tm_subscores = get_post_meta($post->ID,'tm_subscore'.$id,true);
echo '<style>th {font-size: 8px;}</style>';
echo '<form id="voting" method="post" action="'.$votinglink.'">';
echo '<table><tr><th>Criteria</th><th>Max&nbsp;&nbsp;</th>';
foreach($order as $index => $value)
	printf('<th><input type="hidden" id="contestant%d" value="%s">%s</th>',$index,$value,$value);
echo '</tr>';
$category_count = 0;
foreach($scoring as $category => $maxscore)
	{
	echo '<tr>';
	printf('<td>%s</td><td>%s</td>',$category,$maxscore);
	foreach($order as $index => $value)
		{
		$o = '';
		if(!isset($score_me[$index]))
			$score_me[$index] = 0;
		if(isset($tm_subscores[$category_count][$index]))
			$score_me[$index] += $tm_subscores[$category_count][$index];		   
		if(isset($_GET['demo'])) 
			$preselected = rand(2,$maxscore);
		else
			$preselected = 0;
		for($i =0; $i <= $maxscore; $i++)
		{
			$s = (isset($tm_subscores[$category_count][$index]) && ($tm_subscores[$category_count][$index] == $i)) ? 'selected="selected" ' : '';
			if($i == $preselected)
				$s = 'selected="selected" ';
			$o .= sprintf('<option value="%d" %s>%d </option>',$i,$s,$i);
		}
		printf('<td><select name="scores[%s][%s]" contestant="%d" class="score score%d">%s</select> </td>',$category_count,$index,$index,$index,$o);
		}
	echo '</tr>';
	$category_count++;
	}
echo '<tr><td></td><td><strong>Total<strong></td>';

foreach($order as $index => $value)
	printf('<td><strong><span id="sum%d">%s</span></strong></td>',$index,$score_me[$index]);

echo '</tr></table>';

?>

<div id="autorank"><button id="autorank_now">Show Ranking</button></div>

<p>Use the scoresheet above to guide your vote, according to our criteria. If two contestants have the same score, use your judgement to break the tie -- you must choose first, second, and third.</p>

<h2>Vote</h2>
<?php 
	
$vote_opt = '';
if(!empty($contestants))
{
	sort($contestants);
	foreach ($contestants as $contestant)
		$vote_opt .= sprintf('<option value="%s">%s</option>',$contestant,$contestant);	
}
	
$contestant_count = sizeof($contestants);
if($tiebreaker == $id)
	echo '<h3>As the tiebreaking judge, you should rank order ALL contestants.</h3>';
$max = ($tiebreaker == $id) ? $contestant_count : 3;

for($i= 1; $i <= $max; $i++)
	printf('<div><select class="voteselect" name="vote[]"><option value="">Vote #%d</option>%s</select></div>',$i,$vote_opt);
?>
<input type="checkbox" id="readytovote" value="1" /> Record these choices as the vote of <?php echo $name; ?>
<div id="readyprompt"></div>
<input type="hidden" name="judge_id" value="<?php echo $id; ?>" />
<input type="hidden" name="judge_code" value="<?php echo $code; ?>" />
<button>Vote</button>
</form>

<script>
jQuery(document).ready(function($) {

var scoreArr = [];

<?php
foreach($order as $index => $value)
	printf("
scoreArr[%d] = {
	'index' : %d,
	'score' : 0
}	
",$index,$index);
?>

$('#autorank_now').click(function() {
var autorank = '';
scoreArr.sort(function (a, b) {return b.score - a.score});

var arrayLength = scoreArr.length;
for (var i = 0; i < arrayLength; i++) {
	var name = $('#contestant' + scoreArr[i].index).val();
	autorank = autorank + '<br />'+name + ' '+scoreArr[i].score;
}
$('#autorank').html('<div style="width: 300px; margin-bottom: 10px; padding: 5px; border: thin solid #555;"><h3>Best Scores</h3>'+autorank+'</div>');

});
	
$('.score').on('change', function(){

var contestant = $(this).attr('contestant');
var score = 0;
$('.score' + contestant).each(function() {
	score += Number($(this).val());
});
$('#sum' + contestant).html(score);

scoreArr[contestant] = {
	'index' : contestant,
	'score' : score
}

scoreArr[contestant].score = score;

});
	
$('form#voting').submit(function(){
	var empty = false;
	$('.voteselect').each(function() {
		if($(this).val() == '')
		{
			empty = true;
		}
	});
	if(empty)
		{
       $("#readyprompt").html('<span style="color: red;">One or more votes left blank</span>');
		return false;			
		}

    if (! $('#readytovote').is(':checked')){
       $("#readyprompt").html('<span style="color: red;">You must check the checkbox first</span>');
       return false;
    }
}); 

});
</script>
<?php
return ob_get_clean();
}

function contest_demo ($atts) {
global $post;
$output = '';
if(isset($atts['result']))
{
if(isset($_POST['demo_title']))
{
	$demo["post_type"] = 'rsvpmaker';
	$demo["post_title"] = stripslashes($_POST['demo_title']);
	$demo["post_content"] = 'This is a contest demo post.
	
<a href="?scoring=dashboard">Scoring Dashboard</a>';
	$demo["post_author"] = 1;
	$demo["post_status"] = 'publish';
	$demo_id = wp_insert_post( $demo );
	add_post_meta($demo_id,'_contest_demo',$_SERVER['REMOTE_ADDR']);
	$link = get_permalink($demo_id).'?scoring=dashboard';
	$output .= sprintf('<h3>Contest Demo Created!</h3><p>Scoring Dashboard: <a href="%s">%s</a></p>',$link,$link);
}	
}

if(isset($atts['form']))
{
$output .= sprintf('<form action="%s" method="post"><h3>Create Contest Page</h3><p><input type="text" name="demo_title" value="My Demo"><br /><em>Give your page a name, such as Area Contest or Video Speech Contest</em></p><button>Create</button></form>',get_permalink($post->ID));
}
return $output;
}

?>