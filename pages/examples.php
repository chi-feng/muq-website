<?php
$tpl['page_name'] = 'Examples';
$tpl['tab'] = 'Examples';

$examples = json_decode(file_get_contents('json/examples.json'), true);

// helper function for deciding whether to hide a section
function count_examples($topic) {
  global $examples;
  $count = 0;
  foreach($examples as $ex) {
    if ($ex['topic'] == $topic) 
      $count++;
  }
  return $count;
}

function example_filter($topic) {
  echo '<div class="list-group">';
  
  global $examples;
  $count = 0;
  foreach($examples as $ex) {
    if ($ex['topic'] == $topic) {
      echo '<a href="'.$ex['url'].'" class="list-group-item">';
      echo '<h4 class="list-group-item-heading">'.$ex['title'].'</h4>';
      echo '<p class="list-group-item-text">'.$ex['desc'].'</p>';
      echo '</a>';
    }
  }
  echo '</div>';

}

?>


	<h2>Introductory Examples</h2>
	<?php example_filter('intro'); ?>

	<h2>Modelling Examples</h2>
	<?php example_filter('model'); ?>
	
	<h2>Transport Map Examples</h2>
	<?php example_filter('transport_maps'); ?>

