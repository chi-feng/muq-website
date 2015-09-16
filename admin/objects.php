<?php

class Announcement extends Object {
  public function __construct($arg1, $arg2 = '') {
    $this->name = 'Announcement';
    $this->json_path = '../json/announcements.json';
    $this->fields = array(
      'id' => array('label' => 'ID', 'type' => 'hidden'),
      'date' => array('label' => 'Date', 'type' => 'date'),
      'content' => array('label' => 'Content', 'type' => 'textarea')
    );
    $this->sort_by = 'id';
    $this->list_field = array('content', 'date');
    parent::__construct($arg1, $arg2);
  }
}

register_object('Announcement');

class Person extends Object {
  public function __construct($arg1, $arg2 = '') {
    $this->name = 'Person';
    $this->json_path = '../json/people.json';
    $this->fields = array(
      'id' => array('label' => 'ID', 'type' => 'hidden'),
      'name' => array('label' => 'Name', 'type' => 'text'),
      'type' => array('label' => 'Type', 'type' => 'person_type'),
      'email' => array('label' => 'Email (athena username)', 'type' => 'text'),
      'url' => array('label' => 'Photo URL', 'type' => 'text'),
      'www' => array('label' => 'Website URL', 'type' => 'text'),
      'bio' => array('label' => 'Bio', 'type' => 'textarea')
    );
    $this->sort_by = 'name';
    $this->list_field = array('name', 'type');
    parent::__construct($arg1, $arg2);
  }

  public function sort() {
    $arr = json_decode(file_get_contents($this->json_path), true);
    usort($arr, array($this, 'sortfun'));
    $json = format_json(json_encode($arr));
    file_put_contents($this->json_path, $json);
  }

  public function sortfun($a, $b) {
    $a_tok = explode(' ', $a['name']);
    $a_lname = $a_tok[1];
    $b_tok = explode(' ', $b['name']);
    $b_lname = $b_tok[1];
    return $a_lname > $b_lname;
  }

}

register_object('Person');

?>