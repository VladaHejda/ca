<?php declare(strict_types = 1);

$download = isset($_GET['download']);

$rule = isset($_GET['rule']) && $_GET['rule'] !== '' && ctype_digit($_GET['rule'])
	? min((int) $_GET['rule'], 255)
	: random_int(0, 255);

$rows = isset($_GET['rows']) && $_GET['rows'] !== '' && ctype_digit($_GET['rows'])
	? min((int) $_GET['rows'], $download ? 100000 : 10000)
	: 100;

$state = '';
$columns = 0;
if (isset($_GET['state']) && $_GET['state'] !== '') {
	$stateParts = explode(',', $_GET['state']);
	foreach ($stateParts as $statePart) {
		$statePart = trim($statePart);
		if (preg_match('/^[01]+$/', $statePart) !== 0) {
			$state .= $statePart;
		} elseif (preg_match('/^([1-9][0-9]*)x([01])$/', $statePart, $matches) !== 0) {
			$state .= str_repeat($matches[2], (int) $matches[1]);
		} else {
			// custom state malformed
			$state = '';
			break;
		}
	}

	$state = substr($state, 0, 1000);
	$columns = strlen($state);
}

if ($state === '') {
	$columns = isset($_GET['columns']) && $_GET['columns'] !== '' && ctype_digit($_GET['columns'])
		? min((int) $_GET['columns'], 1000)
		: 100;

	$i = $columns;
	while ($i > 0) {
		$state .= random_int(0, 1);
		$i--;
	}
}

// means to fluctuate every Nth cell on the average
$fluctuate = isset($_GET['fluctuate']) && $_GET['fluctuate'] !== '' && ctype_digit($_GET['fluctuate'])
	? min($_GET['fluctuate'], 9999999)
	: 0;
$maxIntHalf = PHP_INT_MAX / 2;
$nextFluctuation = 0;
$generateNextFluctuation = function () use ($fluctuate, $maxIntHalf, &$nextFluctuation): void {
	$randFraction = random_int(0, PHP_INT_MAX) / $maxIntHalf;
	$nextFluctuation = (int) ceil($randFraction * $fluctuate);
};
if ($fluctuate > 0) {
	$generateNextFluctuation();
	$nextFluctuation += $columns; // let first row untouched
}

if (!$download) { ?>
	<style>
		body {
			font-family: "Courier New", monospace;
		}
		.grid {
			font-size: 0.1em;
		}
		.help {
			border-bottom: 1px dotted #000;
			cursor: help;
		}
	</style>

	<form method="get" action="">
		<label>Rule: <input type="number" style="width: 4em" name="rule" value="<?php echo htmlspecialchars($_GET['rule'] ?? ''); ?>" placeholder="rand"></label>
		<label>Columns: <input type="number" style="width: 4em" name="columns" value="<?php echo htmlspecialchars($_GET['columns'] ?? ''); ?>" placeholder="100"></label>
		<label>Rows: <input type="number" style="width: 5em" name="rows" value="<?php echo htmlspecialchars($_GET['rows'] ?? ''); ?>" placeholder="100"></label>
		<label><span class="help" title="e.g.: 99x0, 1, 49x0, 1, 50x0">Initial state</span>: <input type="text" name="state" value="<?php echo htmlspecialchars($_GET['state'] ?? ''); ?>" size="20" placeholder="rand"></label>
		<label>Fluctuate: <input type="number" style="width: 4em" name="fluctuate" value="<?php echo htmlspecialchars($_GET['fluctuate'] ?? ''); ?>" placeholder="none"></label>
		<label><input type="checkbox" name="download"> download</label>
		<input type="submit" value="Set">
		<a href="https://github.com/VladaHejda/ca">Github</a>
	</form><?php

	echo "Rule $rule<br>\n";

	?><div class="grid"><?php

	echo "$state<br>\n";
}

$ruleBin = str_pad(decbin($rule), 8, '0', STR_PAD_LEFT);
$options = ['111', '110', '101', '100', '011', '010', '001', '000'];

$file = null;
$fileName = '';

if ($download) {
	$fileName = sprintf(
		'%s/output/%d-%dx%d-%s.txt',
		__DIR__,
		$rule,
		$columns,
		$rows,
		bin2hex(random_bytes(3)),
	);
	$file = fopen($fileName, 'wb');
}

do {
	$output = str_replace(['1', '0'], ['█', '░'], $state);
	if ($download) {
		fwrite($file, "$output\n");
	} else {
		echo "$output<br>\n";
	}

	$newState = '';
	for ($i = 0; $i < $columns; $i++) {
		$current = $state[$i - 1] . $state[$i] . $state[($i + 1) % $columns];
		$option = array_search($current, $options, true);
		$newCell = $ruleBin[$option];

		if ($fluctuate > 0) {
			$nextFluctuation--;
			if ($nextFluctuation === 0) {
				$newCell = $newCell === '1' ? '0' : '1';
				$generateNextFluctuation();
			}
		}

		$newState .= $newCell;
	}
	$state = $newState;

	$rows--;
} while ($rows > 0);

if ($download) {
	fclose($file);

	header('Content-Type: text/plain');
	header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
	header('Content-Length: ' . filesize($fileName));
	ob_clean();
	flush();
	readfile($fileName);

} else { ?>
	</div>
<?php }
