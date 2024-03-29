<?php

header('Content-Type: text/html; charset=utf-8');

if(!isset($_GET['search_string'])) {
	die('No search string given');
}

$searchString = preg_replace('#[^A-Za-z0-9 -]#', '', $_GET['search_string']);
$searchString = str_replace(' ', '+', $searchString);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://kat.cr/usearch/$searchString/?field=seeders&sorder=desc");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

if(!$result = curl_exec($curl)) {
	die('Error: ' . curl_error($curl) . '<br>Response code: ' . curl_getinfo($curl, CURLINFO_HTTP_CODE));
}
curl_close($curl);

// Trim useless whitespace
$result = preg_replace("/>[\t\s\n]+</", "><", $result);

// Suppress ugly HTML formatting warnings in error log
libxml_use_internal_errors(true);

$doc = new DOMDocument();
$doc->loadHTML($result);

$xpath = new DOMXPath($doc);

$resultsQuery = $xpath->query('//table[@id="mainSearchTable"]//table');

if(empty($resultsQuery)) {
	die('Sorry, no results!');
}

$resultsObj = $resultsQuery->item(0);

$resultsArray = array();

foreach($resultsObj->childNodes as $tr) {
	if($tr->getAttribute('class') == 'firstr') {
		continue;
	}

	$resultItem = array();
	$mainInfoObj = $tr->firstChild;
	$links = $mainInfoObj->firstChild->childNodes;
	
	$resultItem['name'] = $mainInfoObj->lastChild->lastChild->firstChild->nodeValue;
	$resultItem['url'] = $links->item($links->length-2)->getAttribute('href');
	$resultItem['size'] = $tr->childNodes->item(1)->nodeValue;
	$resultItem['seeds'] = $tr->childNodes->item(4)->nodeValue;

	$resultsArray[] = $resultItem;
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Kickass.so Search</title>
</head>
<body>
<h1>Results for "<?php echo $searchString; ?>"</h1>
<table>
	<thead>
		<th>Name</th>
		<th>Size</th>
		<th>Seeds</th>
		<th>Magnet URI</th>
	</thead>
	<tbody>
	<?php foreach($resultsArray as $result) { ?>
		<tr>
			<td><?php echo htmlspecialchars($result['name']); ?></td>
			<td><?php echo htmlspecialchars($result['size']); ?></td>
			<td align="right"><?php echo htmlspecialchars($result['seeds']); ?></td>
			<td><a href="<?php echo htmlspecialchars($result['url']); ?>">Magnet</a></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
</body>
</html>
