
<?php
if(isset($_GET['autocomplete'])&&isset($_GET['oneQuery'])){
   $input = $_GET['oneQuery'];
   $url = "http://localhost:8983/solr/myexample/suggest?indent=on&q=".$input."&wt=json";
   $file = file_get_contents($url);
   echo $file;
}
?>
