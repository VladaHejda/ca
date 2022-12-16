<?php declare(strict_types = 1); ?>

<style>
	body {
		font-family: "Courier New", monospace;
	}
	.grid {
		font-size: 0.1em;
	}
</style>

<?php
$rule = isset($_GET['rule']) && $_GET['rule'] !== '' && ctype_digit($_GET['rule'])
	? min((int) $_GET['rule'], 255)
	: random_int(0, 255);

$rows = isset($_GET['rows']) && $_GET['rows'] !== '' && ctype_digit($_GET['rows'])
	? min((int) $_GET['rows'], 10000)
	: 100;

if (isset($_GET['state']) && $_GET['state'] !== '' && preg_match('/^[01]+$/', $_GET['state']) !== 0) {
	$state = substr($_GET['state'], 0, 1000);
	$columns = strlen($state);
} else {
	$columns = isset($_GET['columns']) && $_GET['columns'] !== '' && ctype_digit($_GET['columns'])
		? min((int) $_GET['columns'], 1000)
		: 100;

	$state = '';
	$i = $columns;
	while ($i > 0) {
		$state .= random_int(0, 1);
		$i--;
	}
}

?>
<form method="get" action="">
	<label>Rule: <input type="number" style="width: 4em" name="rule" value="<?php echo htmlspecialchars($_GET['rule'] ?? ''); ?>"></label>
	<label>Columns: <input type="number" style="width: 4em" name="columns" value="<?php echo htmlspecialchars($_GET['columns'] ?? ''); ?>"></label>
	<label>Rows: <input type="number" style="width: 5em" name="rows" value="<?php echo htmlspecialchars($_GET['rows'] ?? ''); ?>"></label>
	<label>Initial state: <input type="text" name="state" value="<?php echo htmlspecialchars($_GET['state'] ?? ''); ?>" size="20" placeholder="rand"></label>
	<input type="submit" value="Set">
</form>
<?php

echo "Rule $rule<br>\n";
echo "$state<br>\n";

?><div class="grid"><?php

$ruleBin = str_pad(decbin($rule), 8, '0', STR_PAD_LEFT);
$options = ['111', '110', '101', '100', '011', '010', '001', '000'];

do {
	$output = str_replace(['1', '0'], ['█', '░'], $state);
	echo "$output<br>\n";

	$newState = '';
	for ($i = 0; $i < $columns; $i++) {
		$current = $state[$i - 1] . $state[$i] . $state[($i + 1) % $columns];
		$option = array_search($current, $options, true);
		$newState .= $ruleBin[$option];
	}
	$state = $newState;

	$rows--;
} while ($rows > 0);

?></div>
