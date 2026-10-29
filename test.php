<?php
$c = new mysqli('localhost','root','','event_db');
$res = $c->query('SELECT event_id, title, image FROM events');
while($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
