<?php

$base = explode('staticbuilder', thisUrl())[0] . 'staticbuilder';

// Sort data
$pages  = [ 'main' => [], 'ignore' => []];
$assets = [ 'main' => [], 'ignore' => []];
foreach ($summary as $item) {
	$group = $item['status'] == 'ignore' ? 'ignore' : 'main';
	if ($item['type'] == 'page') {
		$pages[$group][] = $item;
	} else {
		$assets[$group][] = $item;
	}
}

// Count all the things
$activeCount = count($pages['main']) + count($assets['main']);
$ignoredCount = count($summary) - $activeCount;
$pagesCount = count($pages['main']) + count($pages['ignore']);
$assetsCount = count($assets['main']) + count($assets['ignore']);

function statusText($status) {
	if ($status == '') return 'Unknown';
	$plain = [
		'uptodate'  => 'Up to date',
		'outdated'  => 'Outdated version',
		'missing'   => 'Not generated',
		'generated' => 'Done',
		'done'      => 'Done',
		'ignore'    => 'Skipped'
	];
	if (array_key_exists($status, $plain)) {
		return $plain[$status];
	} else {
		return $status;
	}
}

function showFiles($files) {
	$text = '';
	if ($files === 1) {
		$text = "<br><small>+&nbsp;1&nbsp;file</small>";
	}
	elseif (is_int($files) and $files > 1) {
		$text = "<br><small>+&nbsp;$files&nbsp;files</small>";
	}
	elseif (is_array($files)) {
		foreach ($files as $file) {
			$text .= "<br><code>$file</code>\n";
		}
	}
	return $text;
}

/**
 * Templating function to render a log entry as a table row
 * @param array $info
 * @param string $base
 * @return string
 */
function makeRow($info, $base) {
	$cols   = [];
	$type   = a::get($info, 'type', '');
	$source = a::get($info, 'source', '');
	$dest   = a::get($info, 'dest', '');
	$status = a::get($info, 'status', '');
	$reason = a::get($info, 'reason', '');
	$title  = a::get($info, 'title', '');
	$uri    = a::get($info, 'uri', '');
	$size   = a::get($info, 'size', '');
	$files  = a::get($info, 'files', '');

	// Source column
	$sKey = 'source type-' . $type;
	if ($type == 'page' && $status != 'ignore') {
		$cols[$sKey] = "<a href=\"$base/$uri\"><span>$title</span><br><code>$source</code></a>";
	}
	elseif ($type == 'page') {
		$cols[$sKey] = "<code>$source</code>";
	}
	else {
		$cols[$sKey] = "<code>[$type] $source</code>";
	}

	// Destination column
	if ($status == 'ignore') {
		$cols['dest'] = "<em>$reason</em>";
	}
	else {
		$cols['dest'] = "<code>$dest</code>" . showFiles($files);
	}

	// Status column
	$cols['status'] = statusText($status);
	if (is_int($size)) $cols['status'] .= '<br><code>'.f::niceSize($size).'</code>';

	// Make the HTML
	$html = '';
	foreach ($cols as $key=>$content) {
		$html .= "<td class=\"$key\">$content</td>\n";
	}
	return "<tr class=\"$type $status\">\n$html</tr>\n";
}


?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Kirby StaticBuilder</title>
	<style><?php echo $styles; ?></style>
</head>
<body>

<header>
	<div class="header-col header-col--main">
		<h1>Kirby StaticBuilder</h1>
		<p class="<?php echo $error ? 'error' : 'info' ?>">
		<?php
			if (isset($error) and $error != '') echo $error;
			else {
				echo ($confirm ? 'Built' : 'Found') . " $activeCount elements";
				if ($ignoredCount > 0) echo " ($ignoredCount skipped)";
			}
		?>
		</p>
	</div>
	<div class="header-col header-col--side">
		<?php if ($mode == 'page'): ?>
			<a class="header-btn" href="<?php echo $base ?>">show all pages</a>
		<?php endif ?>
		<form method="post" action="">
			<input type="hidden" name="confirm" value="1">
			<button class="header-btn" type="submit">
				build <?= $mode == 'page' ? 'this page' : 'everything&thinsp;!' ?>
			</button>
		</form>
	</div>
</header>

<main>
<?php if (isset($errorDetails)): ?>
	<div class="error-msg">
		<?php if (isset($lastPage)): ?>
			<h2>
				Failed to build page:
				<?php echo $lastPage ? "<code>$lastPage</code>" : 'unknown'; ?>
			</h2>
		<?php endif ?>
		<blockquote>
			<?php echo $errorDetails ?>
		</blockquote>
	</div>
<?php endif ?>
<?php if ($assetsCount > 0): ?>
	<h2 class="section-header">Assets</h2>
	<table class="results results-assets">
		<thead>
		<tr>
			<th>Directory or file</th>
			<th><?php echo $confirm ? 'Copied to' : 'Copy target'; ?></th>
			<th class="short">Status</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach(array_merge($assets['main'], $assets['ignore']) as $item) {
			echo makeRow($item, $base);
		} ?>
		</tbody>
	</table>
<?php endif ?>
<?php if ($pagesCount > 0): ?>
	<?php if ($mode != 'page'): ?>
		<h2 class="section-header">Pages</h2>
	<?php endif ?>
	<?php if (isset($errorDetails)): ?>
	<p>
		The following pages and files were built without errors.<br>
		<strong>Important:</strong> the script was stopped, so the next pages in the queue were NOT built.
	</p>
	<?php endif ?>
	<table class="results results-pages">
		<thead>
		<tr>
			<th>Page source</th>
			<th><?php echo $confirm ? 'Output' : 'Output target'; ?></th>
			<th class="short">Status</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach(array_merge($pages['main'], $pages['ignore']) as $item) {
			echo makeRow($item, $base);
		} ?>
		</tbody>
	</table>
<?php endif ?>
</main>

<script>
<?php echo $script; ?>
</script>

</body>
</html>
