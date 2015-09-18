<?php
$tpl['page_name'] = 'About';
$tpl['tab'] = 'About';

$people = json_decode(file_get_contents('json/people.json'), true);

function people_filter($type) {
  echo '<div class="row">';
  global $people;
  foreach($people as $person) {
    if ($person['type'] == $type) {
      echo '<div class="col-xs-12 col-sm-6 col-md-4">';
      echo '<h3 class="text-center">'.$person['name'].'</h3>';
      echo '<img src="/images/people/'.$person['url'].'.png" alt="'.$person['name'].'" title="'.$person['name'].'" class="img-responsive center-block" />';
      echo '</div>';
    }
  }
  echo '</div>';
}

?>

<h2>Who are we?</h2>
<p> MUQ is primarily developed by graduate students and postdocs in Professor Youssef Marzouk's uncertainty quantification group at MIT.  MUQ was originally started internally around 2011 to provide a usable outlet for our research.  Since then, we have grown substantially and now provide a much larger framework for developing new algorithms and coupling them with challenging scientific applications.  We are also part of the <a href="http://www.quest-scidac.org/">QUEST</a> SciDAC institute.</p>

<h2>Core Team</h2>
<?php people_filter('core'); ?>
<h2>Contributors</h2>
<?php people_filter('contributor'); ?>

<hr />
