<?php

include 'SpellCorrector.php';
include 'simple_html_dom.php';
//echo SpellCorrector::correct('obame');

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
header("Access-Control-Allow-Origin: *"); 
//echo SpellCorrector::correct('spots');

$limit =10;
//$query =isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
if(isset($_REQUEST['q'])){
   $query = "";
   $newQuery = "";
   $arr = explode(' ', $_REQUEST['q']);
   foreach($arr as $item){
       $query = $query.$item.' ';
       $newQuery = $newQuery.SpellCorrector::correct($item).' ';
   }
   $query = trim($query);
   $newQuery = trim($newQuery);
}

$results =false;
$correct = false;


if($query&&$newQuery)
{
// The Apache Solr Client library should be on the include path
// which is usually most easily accomplished by placing in the
// same directory as this script ( . or current directory is a default
// php include path entry in the php.ini)
require_once('solr-php-client/Apache/Solr/Service.php');

// create a new solr service instance -host, port, and corename
// path (all defaults in this example)
$solr =new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

// if magic quotes is enabled then stripslashes will be needed
if(get_magic_quotes_gpc() ==1)
{
$query =stripslashes($query);
$newQuery =stripslashes($newQuery);
}

//$newQuery = SpellCorrector::correct($query);
// in production codeyou'll always want to use a try /catch for any
// possible exceptions emitted  by searching (i.e. connection
// problems or a query parsing error)
try
{
$additionalParameters = array(
  'sort'=>'pageRankFile desc'
);

if($_GET['RankAlgorithm']=='RankFile') 
{
if($query==$newQuery){
$results=$solr->search($query, $start, $rows, $additionalParameters);
}
else{
$correct = true;
if($_GET['Change']){
$correct = false;
$results=$solr->search($query, $start, $rows, $additionalParameters);
}
else{
$results=$solr->search($newQuery, $start, $rows, $additionalParameters);
}
}
}
else if($_GET['RankAlgorithm']=='Lucene')
{
if($query==$newQuery){
$results =$solr->search($query, 0, $limit);
}
else{
$correct = true;
if($_GET['Change']){
$correct = false;
$results =$solr->search($query, 0, $limit);
}
else{
$results =$solr->search($newQuery, 0, $limit);
}
}
}
}
catch(Exception$e)
{
// in production you'd probably log or email this error to an admin
// and then show a special message to the user but for this example
// we're going to show the full exception
die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
}
}
?>
<html>
<head>
<title>PHP Solr Client Example</title>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://code.jquery.com/jquery-2.2.2.js"   integrity="sha256-4/zUCqiq0kqxhZIyp4G0Gk+AOtCJsY1TA00k5ClsZYE="   crossorigin="anonymous"></script>
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"   integrity="sha256-DI6NdAhhFRnO2k51mumYeDShet3I8AKCQf/tf7ARNhI="   crossorigin="anonymous"></script>


</head>
<body>
<form accept-charset="utf-8" method="get">
<label for="q">Search:</label>
<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
<input type="submit"/>
<label><input name="RankAlgorithm" type="radio" value="Lucene" <?php if($_GET['RankAlgorithm']=='Lucene'){ ?> checked=true <?php ;} ?> />Lucene</label>
<label><input name="RankAlgorithm" type="radio" value="RankFile" <?php if($_GET['RankAlgorithm']=='RankFile'){ ?> checked=true <?php ;} ?> />RankFile</label>
</form>

<script type="text/javascript">
$(function(){
$('#q').autocomplete({
   source: function(request,response){
      var previous = "";
      var current = "";
      if(request.term.lastIndexOf(" ")==-1){
         current = request.term.toLowerCase();
      }
      else{
         previous = request.term.substr(0,request.term.lastIndexOf(" ")).toLowerCase();
         current = request.term.substr(request.term.lastIndexOf(" ")+1).toLowerCase();
      }
      $.ajax({
         type: "GET",
         dataType: "json",
         url: "request.php",
         data: {
             autocomplete: "on",
             oneQuery: current
         },
         success: function(data){
             var suggestList = new Array();
             var jsonData = data;
             for(var i=0; i<jsonData.suggest.suggest[current].numFound;i++){
                 var suggestWord = jsonData.suggest.suggest[current].suggestions[i].term;
                 suggestList.push(previous + " " + suggestWord);
             }
  
           response(suggestList);
         },
         error: function(){
                response();
         }

      });
    },
    minLength:1
});
});
</script>


<?php

// display results
if($results)
{
$total =(int) $results->response->numFound;
$start =min(1, $total);
$end =min($limit, $total);
$snippet = "";

if($correct==true){
?>
<p style='font-size:18px'>Showing results for <a href='http://localhost/~yilinwang/solr.php?q=<?php echo $newQuery ?>&RankAlgorithm=<?php echo $_GET['RankAlgorithm'] ?>'> <?php echo $newQuery ?> </a></p>
<p style='font-size:15px'>Search instead for <a href='http://localhost/~yilinwang/solr.php?q=<?php echo $query ?>&RankAlgorithm=<?php echo $_GET['RankAlgorithm'] ?>&Change=false'>  <?php echo $query ?> </a></p>
<?php
}
if($correct==false && $_GET['Change']){
?>
<p style='font-size:18px'>Are you search results for: <a href='http://localhost/~yilinwang/solr.php?q=<?php echo $newQuery ?>&RankAlgorithm=<?php echo $_GET['RankAlgorithm'] ?>'> <?php echo $newQuery ?> </a></p>
<?php
}
?>
<div>Results <?php echo$start; ?> -<?php echo $end;?> of <?php echo $total; ?>:</div>
<ol>
<?php
$f=fopen('mapABCNewsDataFile.csv', 'r') or die("can't open file");
$result = array();
while($line=fgetcsv($f))
{
$result[$line[0]]=$line[1];
}
//print_r($result);
// iterate result documents
foreach($results->response->docs as $doc)
{
?>
<li>
<?php
// iterate document fields / values
foreach($doc as $field =>$value)
{
if($field=='title')
{
$title= htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
}
//else if($field=='og_url')
//{
//$url=htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
//}
else if($field=='id')
{
$id=htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
$i=strlen($id)-1;
$count=0;
while($id[$i]!='/' && $i>=0)
{
$count++;
$i--;
}
$key=substr($id, $i+1, $count);
$url=$result[$key];
$html = file_get_html("crawl_data/".$key);
if($html){
$html_content = preg_replace("/(\x20{2,})/"," ",$html->plaintext);
if($correct==true){
$pos = stripos($html_content,$newQuery);
if($pos != false){
  $snippet = substr($html_content,$pos, 156);
  //echo $snippet;
  $replace = "<b>".$newQuery."</b>";
  $snippet = " ... ".str_ireplace($newQuery, $replace, $snippet)." ... "; 
}
}
else{
$pos = stripos($html_content,$query);
if($pos != false){
  $snippet = substr($html_content,$pos, 400);
  //echo $snippet;
  $replace = "<b>".$query."</b>";
  $snippet =  " ... ".str_ireplace($query, $replace, $snippet)." ... ";
}
}
}
}
else if($field=='description')
{
$description=htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
}
}
echo '<a href=';
echo $url;
echo '>';
echo $title;
echo '</a>';
echo '<br>';
echo '<br>';
echo '<a href=';
echo $url;
echo '>';
echo $url;
echo '</a>';
echo '<br>';
echo '<br>';
echo $id;
echo '<br>';
echo '<br>';
echo $description;
echo '<br>';
echo '<br>';
//echo $newQuery;
echo $snippet;
echo '<br>';
echo '<br>';
echo '<br>';
?>
</li>
<?php
}
?>
</ol>
<?php
}
?>
</body>
</html>


